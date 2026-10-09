<?php

namespace App\Services\Leader;

use App\Models\Aircraft;
use App\Models\SyncSetting;
use App\Services\Audit\AocResolver;
use App\Services\GoogleSheetsReader;
use Carbon\Carbon;
use Google\Service\Sheets\BatchUpdateValuesRequest;
use Google\Service\Sheets\ValueRange;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

/**
 * Writes unplanned work reported by a leader into the planner workbook (the same spreadsheet as the DJA sync):
 * WO -> "Unplanned WO", DMI -> "Unplaned DMI", NSRDI -> "Unplaned NSRD" (the planner's tabs really are spelled
 * "Unplaned"; the correct spelling is accepted too).
 *
 * Columns are found by header name, so the tabs may order them freely and columns we do not know stay empty.
 * The document number identifies a row: a document already in the tab is updated (status, man hours, close date)
 * instead of added again, so re-importing a report is safe. New documents go into the first empty row under the
 * header (the planner pre-fills helper formulas further down the tab); only when no empty row is left a row is appended.
 */
class UnplannedSheetWriter
{
    /** type => tab names to look for (first match wins; case and spacing are ignored) */
    public const TABS = [
        'WO' => ['UNPLANNED WO', 'UNPLANED WO', 'WO UNPLANNED'],
        'DMI' => ['UNPLANNED DMI', 'UNPLANED DMI', 'DMI UNPLANNED'],
        'NSRDI' => ['UNPLANNED NSRD', 'UNPLANED NSRD', 'UNPLANNED NSRDI', 'UNPLANED NSRDI', 'NSRDI UNPLANNED'],
    ];

    /** field => header names (compared without punctuation) */
    private const HEADERS = [
        'date' => ['REFRESH DATE', 'INPUT DATE', 'DATE', 'TANGGAL'],
        'doc_no' => ['NO DOC', 'DOC NO', 'NO DOKUMEN', 'WO', 'WO NO', 'WO NUMBER', 'DMI NO', 'DMI NUMBER', 'NSRDI', 'NSRDI NO', 'NSRDI NUMBER', 'TASK ID'],
        'doc_type' => ['DOC TYPE'],
        'wg' => ['WG', 'WORK GROUP'],
        'reg' => ['AC REG', 'A/C REG', 'REG', 'AIRCRAFT REG', 'AIRCRAFT REGISTRATION'],
        'station' => ['ACT STA', 'ACT STATION', 'STA', 'STATION'],
        'plan_station' => ['PLAN STA', 'PLAN STATION'],
        'operator' => ['OPERATOR', 'AOC'],
        'type' => ['TYPE'],
        'description' => ['WO DESCRIPTION', 'FINDING DESCRIPTION', 'DMI DESCRIPTION', 'DESCRIPTION', 'DEFFECT DESCRIPTION', 'DEFECT DESCRIPTION'],
        'ata' => ['ATA CHAPTER', 'ATA'],
        'category' => ['CATEGORY'],
        'man_power' => ['MP', 'MAN POWER', 'MANPOWER'],
        'man_hour' => ['MAN HOURS', 'MAN HOUR', 'MH'],
        'close_date' => ['CLOSE DATE'],
        'reason_open' => ['REASON OPEN'],
        'code_open' => ['CODE OPEN'],
    ];

    /** The status column is named per tab: STATUS WO, STATUS DMI, STATUS. */
    private const STATUS_HEADERS = [
        'WO' => ['STATUS WO', 'STATUS'],
        'DMI' => ['STATUS DMI', 'STATUS'],
        'NSRDI' => ['STATUS'],
    ];

    /** Fields refreshed on a document that is already in the tab. */
    private const UPDATE_FIELDS = ['status', 'man_hour', 'close_date', 'reason_open', 'code_open'];

    public function __construct(private ?GoogleSheetsReader $reader = null)
    {
        $this->reader ??= app(GoogleSheetsReader::class);
    }

    public function spreadsheetId(): ?string
    {
        return SyncSetting::where('key', SyncSetting::Dja)->value('spreadsheet_id') ?: null;
    }

    /**
     * @param  array<string, mixed>  $row  a leader_report_rows record as array
     * @return bool|null true written, false failed, null when no spreadsheet / credentials are configured (nothing to do)
     */
    public function upsert(string $type, array $row): ?bool
    {
        $spreadsheetId = $this->spreadsheetId();
        if (! $spreadsheetId || ! $this->reader->isReady() || ! isset(self::TABS[$type])) {
            return null;
        }

        try {
            $titles = $this->reader->tabTitles($spreadsheetId);
            $tab = collect($this->reader->resolveTabs(self::TABS[$type], $titles))->first();
            if (! $tab) {
                Log::error("Unplanned write: tab {$type} tidak ditemukan di spreadsheet (dicari: ".implode(' / ', self::TABS[$type]).').');

                return false;
            }

            $values = $this->reader->values($spreadsheetId, $tab);
            $header = $this->reader->locateHeader($values, $this->knownHeaders(), 2);
            if (! $header) {
                Log::error("Unplanned write: header tab {$tab} tidak dikenali.");

                return false;
            }

            $cols = $this->columns($header['map'], $type, $values[$header['index']] ?? []);
            if (! isset($cols['doc_no'])) {
                Log::error("Unplanned write: kolom nomor dokumen tidak ada di tab {$tab}.");

                return false;
            }

            $cells = $this->cells($cols, $type, $this->enrich($row));
            $docNo = LeaderReportParser::normalizeDocNo($row['doc_no'] ?? null);
            $service = $this->reader->service();
            $quoted = "'".str_replace("'", "''", $tab)."'";

            // Existing document -> refresh it; otherwise the first empty row under the header; otherwise append
            $existingRow = null;
            $emptyRow = null;
            foreach (array_slice($values, $header['index'] + 1, null, true) as $i => $sheetRow) {
                $cell = LeaderReportParser::normalizeDocNo($sheetRow[$cols['doc_no']] ?? null);
                if ($cell === $docNo) {
                    $existingRow = $i + 1;   // 1-based sheet row
                    break;
                }
                if ($cell === null && $emptyRow === null) {
                    $emptyRow = $i + 1;
                }
            }

            $target = $existingRow ?? $emptyRow;
            if ($target) {
                $fields = $existingRow ? self::UPDATE_FIELDS : array_keys($cols);
                $data = [];
                foreach ($fields as $field) {
                    if (isset($cols[$field], $cells[$cols[$field]]) && ($cells[$cols[$field]] !== '' || $existingRow === null && $field === 'doc_no')) {
                        $letter = Coordinate::stringFromColumnIndex($cols[$field] + 1);
                        $data[] = new ValueRange(['range' => "{$quoted}!{$letter}{$target}", 'values' => [[$cells[$cols[$field]]]]]);
                    }
                }
                if ($data) {
                    $service->spreadsheets_values->batchUpdate($spreadsheetId, new BatchUpdateValuesRequest(['valueInputOption' => 'USER_ENTERED', 'data' => $data]));
                }

                return true;
            }

            $width = max(array_keys($cells)) + 1;
            $line = [];
            for ($i = 0; $i < $width; $i++) {
                $line[] = $cells[$i] ?? '';
            }
            $service->spreadsheets_values->append(
                $spreadsheetId,
                "{$quoted}!A1",
                new ValueRange(['values' => [$line]]),
                ['valueInputOption' => 'USER_ENTERED', 'insertDataOption' => 'INSERT_ROWS']
            );

            return true;
        } catch (\Throwable $e) {
            Log::error('Unplanned write failed: '.$e->getMessage());

            return false;
        }
    }

    /** Adds what the aircraft master knows: AOC code (JT / ID / IU ...), fleet (A320 / B737 ...) and work group. */
    private function enrich(array $row): array
    {
        $reg = $row['aircraft_registration'] ?? null;
        if (! $reg) {
            return $row;
        }
        $aircraft = Aircraft::where('registration', $reg)->first();
        $aoc = app(AocResolver::class)->resolve($reg, $row['operator'] ?? null);

        return $row + [
            'aoc_code' => $aoc?->code,
            'fleet' => $aircraft?->fleet,
            'wg' => $aircraft?->wg,
        ];
    }

    /** @return array<int, string> every header name we can place, so the header row can be located */
    public function knownHeaders(): array
    {
        return array_values(array_unique(array_merge(
            ...array_values(self::HEADERS),
            ...array_values(self::STATUS_HEADERS),
        )));
    }

    /**
     * @param  array<string, int>  $headerMap  upper-cased header name => column index (from locateHeader)
     * @param  array<int, mixed>  $headerRow  the header row itself; a blank first column is used for the date
     * @return array<string, int> field => column index
     */
    public function columns(array $headerMap, ?string $type = null, array $headerRow = []): array
    {
        $byClean = [];
        foreach ($headerMap as $name => $index) {
            $byClean[$this->clean((string) $name)] ??= $index;
        }

        $headers = self::HEADERS + ['status' => self::STATUS_HEADERS[$type] ?? ['STATUS']];
        $cols = [];
        foreach ($headers as $field => $names) {
            foreach ($names as $name) {
                if (isset($byClean[$this->clean($name)])) {
                    $cols[$field] = $byClean[$this->clean($name)];
                    break;
                }
            }
        }

        // The planner's NSRDI tab has no title over its first column, which holds the refresh date
        if (! isset($cols['date']) && $headerRow !== [] && trim((string) ($headerRow[0] ?? '')) === '' && ! in_array(0, $cols, true)) {
            $cols['date'] = 0;
        }

        return $cols;
    }

    /**
     * @param  array<string, int>  $cols
     * @param  array<string, mixed>  $row
     * @return array<int, string> column index => text for the sheet
     */
    public function cells(array $cols, string $type, array $row): array
    {
        $date = fn ($v) => $v ? Carbon::parse($v)->format('d-M-y') : '';
        $closed = ($row['status'] ?? null) === 'Closed';
        $open = ($row['status'] ?? null) === 'Open';

        $values = [
            'date' => $date($row['work_date'] ?? null),
            'doc_no' => (string) ($row['doc_no'] ?? ''),
            'doc_type' => $type,
            'wg' => (string) ($row['wg'] ?? ''),
            'reg' => (string) ($row['aircraft_registration'] ?? ''),
            'station' => (string) ($row['station'] ?? ''),
            'plan_station' => (string) ($row['plan_station'] ?? ''),
            'operator' => (string) (($row['aoc_code'] ?? null) ?: ($row['operator'] ?? '')),
            'type' => (string) ($row['fleet'] ?? ''),
            'description' => (string) ($row['description'] ?? ''),
            'ata' => (string) ($row['ata'] ?? ''),
            'status' => strtoupper((string) ($row['status'] ?? '')),
            'category' => $type === 'NSRDI' ? (string) ($row['category'] ?? '') : '',
            'man_power' => isset($row['man_power']) ? (string) $row['man_power'] : '',
            'man_hour' => isset($row['man_hour']) ? (string) $row['man_hour'] : '',
            'close_date' => $closed ? $date($row['work_date'] ?? null) : '',
            'reason_open' => $open ? (string) ($row['reason_open'] ?? '') : '',
            'code_open' => $open ? (string) ($row['code_open'] ?? '') : '',
        ];

        $cells = [];
        foreach ($cols as $field => $index) {
            $cells[$index] = $values[$field] ?? '';
        }
        ksort($cells);

        return $cells;
    }

    private function clean(string $name): string
    {
        return preg_replace('/[^A-Z0-9]+/', '', strtoupper($name));
    }
}
