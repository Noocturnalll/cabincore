<?php

namespace App\Services\Dja;

use App\Models\Aircraft;
use App\Models\DmiLog;
use App\Models\NsrdiLog;
use App\Models\SheetSyncLog;
use App\Models\WoLog;
use App\Services\GoogleSheetsReader;
use App\Services\Leader\LeaderReportParser;
use App\Services\Leader\UnplannedSheetWriter;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Reads the planner workbook's Unplanned tabs (WO / DMI / NSRDI) into the logs, so unplanned work typed into the
 * Google Sheet appears in the web app. Read-only on the sheet.
 *
 * Rules: a document is one record. New documents are created as unplanned (no dja_id); an existing record only gets
 * blanks filled and is closed when the sheet says Closed, so edits made in the web are never overwritten. A document
 * that also exists as a planned (DJA) job is not duplicated; differing statuses are written to the sync log.
 */
class UnplannedSheetPuller
{
    private const MODEL = ['WO' => WoLog::class, 'DMI' => DmiLog::class, 'NSRDI' => NsrdiLog::class];

    private const NUMBER_COLUMN = ['WO' => 'wo_number', 'DMI' => 'dmi_number', 'NSRDI' => 'nsrdi_number'];

    /** extra headers (not known by the writer) per field */
    private const EXTRA = [
        'pn_required' => ['PN REQUIRED', 'PN'],
        'remarks' => ['REMARK', 'REMARKS'],
        'dmi_category' => ['DMI CAT', 'DMI CATEGORY'],
        'status_wo' => ['STATUS WO'],
        'part_number' => ['PART NUMBER'],
        'part_description' => ['PART DESCRIPTION'],
    ];

    public function __construct(private ?GoogleSheetsReader $reader = null, private ?UnplannedSheetWriter $writer = null)
    {
        $this->reader ??= app(GoogleSheetsReader::class);
        $this->writer ??= new UnplannedSheetWriter($this->reader);
    }

    /** @return array{created: int, updated: int, skipped: int, conflicts: int} */
    public function pull(string $spreadsheetId): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'conflicts' => 0];
        if (! $this->reader->isReady()) {
            return $stats;
        }

        $titles = $this->reader->tabTitles($spreadsheetId);
        foreach (UnplannedSheetWriter::TABS as $type => $names) {
            // the reader matches tab names loosely; only a tab really named Unplanned/Unplaned may be read here
            $tab = collect($this->reader->resolveTabs($names, $titles))->first(fn ($t) => str_contains(strtoupper((string) $t), 'UNPLAN'));
            if (! $tab) {
                continue;
            }
            try {
                $this->pullTab($spreadsheetId, $tab, $type, $stats);
            } catch (\Throwable $e) {
                Log::warning("Unplanned pull {$type} gagal: ".$e->getMessage());
                SheetSyncLog::note('pull', 'failed', strtolower($type), null, mb_substr($e->getMessage(), 0, 300));
            }
        }

        return $stats;
    }

    private function pullTab(string $spreadsheetId, string $tab, string $type, array &$stats): void
    {
        $values = $this->reader->values($spreadsheetId, $tab);
        $known = array_merge($this->writer->knownHeaders(), ...array_values(self::EXTRA));
        $header = $this->reader->locateHeader($values, $known, 2);
        if (! $header) {
            return;
        }
        $headerRow = $values[$header['index']] ?? [];
        $cols = $this->writer->columns($header['map'], $type, $headerRow);
        if (! isset($cols['doc_no'])) {
            return;
        }
        foreach (self::EXTRA as $field => $aliases) {
            foreach ($aliases as $alias) {
                if (isset($header['map'][$alias])) {
                    $cols[$field] = $header['map'][$alias];
                    break;
                }
            }
        }

        $seen = [];
        foreach (array_slice($values, $header['index'] + 1) as $sheetRow) {
            $get = fn (string $f) => isset($cols[$f]) ? $this->clean($sheetRow[$cols[$f]] ?? null) : null;
            $doc = LeaderReportParser::normalizeDocNo($get('doc_no'));
            if ($doc === null || isset($seen[$doc])) {
                continue; // blank helper row, or the same document twice in the tab: one record only
            }
            $seen[$doc] = true;

            $data = [
                'date' => $this->date($get('date')),
                'wg' => $get('wg'),
                'reg' => $get('reg'),
                'station' => $get('station') ?? $get('plan_station'),
                'plan_station' => $get('plan_station'),
                'operator' => $get('operator'),
                'type' => $get('type'),
                'description' => $get('description'),
                'ata' => $get('ata'),
                'category' => $get('category'),
                'man_power' => $get('man_power'),
                'man_hour' => $get('man_hour'),
                'close_date' => $this->date($get('close_date')),
                'status' => ($get('status') !== null) ? DjaRowMapper::normalizeStatus($get('status'), $get('close_date')) : 'Closed',
                'reason_open' => $get('reason_open'),
                'code_open' => $get('code_open'),
                'pn_required' => $get('pn_required'),
                'remarks' => $get('remarks'),
                'dmi_category' => $get('dmi_category'),
                'status_wo' => $get('status_wo'),
                'part_number' => $get('part_number'),
                'part_description' => $get('part_description'),
            ];
            if ($data['reg'] === null && $data['description'] === null) {
                continue;
            }

            $this->store($type, $doc, $data, $stats);
        }
    }

    private function store(string $type, string $doc, array $d, array &$stats): void
    {
        $model = self::MODEL[$type];
        $col = self::NUMBER_COLUMN[$type];
        $existing = $model::whereRaw("UPPER(TRIM({$col})) = ?", [$doc])->orderByDesc('id')->get();

        if ($existing->isNotEmpty()) {
            $log = $existing->first();
            if ($existing->count() > 1) {
                $statuses = $existing->pluck('status')->unique();
                if ($statuses->count() > 1) {
                    $stats['conflicts']++;
                    SheetSyncLog::note('pull', 'conflict', strtolower($type), $doc, 'Dokumen sama muncul lebih dari satu kali dengan status berbeda: '.$statuses->implode(' / '));
                }
            }
            if ($log->dja_id) {
                // planned job with the same number: never duplicate it; flag when the sheets disagree
                if ($d['status'] === 'Closed' && strtolower((string) $log->status) !== 'closed') {
                    $stats['conflicts']++;
                    SheetSyncLog::note('pull', 'conflict', strtolower($type), $doc, 'Ada di DJA (Open) dan di Unplanned (Closed).');
                }
                $stats['skipped']++;

                return;
            }
            if ($log->is_submitted) {
                $stats['skipped']++;

                return;
            }
            $this->fillBlanks($log, $type, $d);
            if ($d['status'] === 'Closed' && strtolower((string) $log->status) !== 'closed') {
                $log->status = 'Closed';
            }
            $changed = $log->isDirty();
            $log->save();
            $changed ? $stats['updated']++ : $stats['skipped']++;

            return;
        }

        $model::create($this->attributes($type, $doc, $d));
        $stats['created']++;
    }

    private function fillBlanks($log, string $type, array $d): void
    {
        foreach ($this->attributes($type, '', $d) as $k => $v) {
            if ($k === 'status' || $v === null || $v === '' || $k === self::NUMBER_COLUMN[$type]) {
                continue;
            }
            if ($log->{$k} === null || $log->{$k} === '') {
                $log->{$k} = $v;
            }
        }
    }

    private function attributes(string $type, string $doc, array $d): array
    {
        $wg = $d['wg'] ?: ($d['reg'] ? Aircraft::where('registration', $d['reg'])->value('wg') : null);
        $sta = $d['station'] ? strtoupper($d['station']) : null;
        $plan = $d['plan_station'] ? strtoupper($d['plan_station']) : null;
        $date = $d['date'] ?? DjaPersister::activeDate();
        $mh = is_numeric($d['man_hour']) ? (float) $d['man_hour'] : null;
        $mp = is_numeric($d['man_power']) ? (int) $d['man_power'] : null;

        return match ($type) {
            'WO' => [
                'date' => $date, 'work_group' => $wg, 'aircraft_registration' => $d['reg'], 'wo_number' => $doc,
                'wo_category' => $d['category'], 'description' => $d['description'], 'operator' => $d['operator'],
                'type' => $d['type'], 'plan_station' => $plan, 'act_station' => $sta ?: $plan, 'status' => $d['status'],
                'man_hour' => $mh, 'man_power' => $mp, 'ata' => $d['ata'], 'close_date' => $d['close_date'],
                'reason_open' => $d['reason_open'], 'code_open' => $d['code_open'],
                'hold_reason_category' => $d['code_open'], 'hold_remarks' => $d['reason_open'] ?: $d['remarks'],
            ],
            'DMI' => [
                'date' => $date, 'work_group' => $wg, 'aircraft_registration' => $d['reg'], 'dmi_number' => $doc,
                'description' => $d['description'], 'pn_required' => $d['pn_required'], 'dmi_category' => $d['dmi_category'],
                'category' => $d['category'], 'plan_station' => $plan, 'act_station' => $sta ?: $plan, 'status' => $d['status'],
                'status_wo' => $d['status_wo'], 'remarks' => $d['remarks'], 'aoc' => $d['operator'], 'type' => $d['type'],
                'ata' => $d['ata'], 'close_date' => $d['close_date'], 'man_hour' => $mh, 'man_power' => $mp,
                'hold_reason_category' => $d['code_open'], 'hold_remarks' => $d['reason_open'] ?: $d['remarks'],
            ],
            'NSRDI' => [
                'refresh_date' => $date, 'plan_date' => $date, 'report_date' => $date, 'work_group' => $wg,
                'aircraft_registration' => $d['reg'], 'nsrdi_number' => $doc, 'description' => $d['description'],
                'category' => $d['category'], 'part_number' => $d['part_number'], 'part_description' => $d['part_description'],
                'aoc' => $d['operator'], 'type' => $d['type'], 'plan_station' => $plan, 'act_station' => $sta ?: $plan,
                'remarks' => $d['remarks'], 'status' => $d['status'], 'close_date' => $d['close_date'],
                'man_hour' => $mh, 'man_power' => $mp, 'import_source' => 'gss_unplanned',
                'code_open' => $d['code_open'], 'reason_open' => $d['reason_open'],
                'hold_reason_category' => $d['code_open'], 'hold_remarks' => $d['reason_open'] ?: $d['remarks'],
            ],
        };
    }

    private function date(?string $v): ?string
    {
        if ($v === null) {
            return null;
        }
        $d = GoogleSheetsReader::parseDate($v);
        if ($d) {
            return $d;
        }
        foreach (['d-M-y', 'd-M-Y', 'j-M-y', 'd/M/Y'] as $f) {
            try {
                $c = Carbon::createFromFormat('!'.$f, $v);
                if ($c) {
                    return $c->format('Y-m-d');
                }
            } catch (\Throwable) {
            }
        }

        return null;
    }

    private function clean(mixed $v): ?string
    {
        $v = trim((string) $v);

        return $v === '' || $v === '-' ? null : $v;
    }
}
