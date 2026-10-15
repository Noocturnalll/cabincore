<?php

namespace App\Livewire\Traits;

use App\Models\Aircraft;
use App\Models\DmiLog;
use App\Models\NsrdiLog;
use App\Models\WoLog;
use App\Services\Audit\AocResolver;
use App\Services\Dja\DjaPersister;
use App\Services\Dja\UnplannedSheetPusher;
use App\Services\Leader\LeaderReportParser;

/**
 * Fast entry of unplanned work: paste several lines (straight from Excel / the Google Sheet, tab separated), one line
 * per document. Unplanned work is always Closed. WG, operator and fleet come from the aircraft master. Each saved
 * row is written to the Unplanned tab of the Google Sheet; a failed write is only logged and retried.
 */
trait QuickUnplannedEntry
{
    public bool $isQuickOpen = false;

    public string $quickText = '';

    /** @return array<int, string> column order of a pasted line */
    public function quickColumns(): array
    {
        return match ($this->statusLogModel()) {
            DmiLog::class => ['REG', 'DMI NO', 'CATEGORY', 'DESCRIPTION', 'REMARK', 'ACT STA', 'PN REQUIRED'],
            NsrdiLog::class => ['REG', 'NSRDI NO', 'CATEGORY', 'DESCRIPTION', 'ACT STA'],
            default => ['REG', 'WO NO', 'DESCRIPTION', 'ACT STA', 'MAN HOUR'],
        };
    }

    public function saveQuick(): void
    {
        $cols = $this->quickColumns();
        $model = $this->statusLogModel();
        $numberCol = match ($model) {
            DmiLog::class => 'dmi_number',
            NsrdiLog::class => 'nsrdi_number',
            default => 'wo_number',
        };
        $created = 0;
        $skipped = 0;

        foreach (preg_split('/\r\n|\r|\n/', $this->quickText) as $line) {
            if (trim($line) === '') {
                continue;
            }
            $cells = str_contains($line, "\t") ? explode("\t", $line) : preg_split('/\s*[;|]\s*|\s{2,}/', $line);
            $cells = array_map('trim', $cells);
            $r = array_combine($cols, array_pad(array_slice($cells, 0, count($cols)), count($cols), ''));
            if (in_array(strtoupper($r['REG']), ['REG', 'AC REG', 'A/C REG'], true)) {
                continue; // pasted header line
            }
            $doc = LeaderReportParser::normalizeDocNo($r[$cols[1]]);
            $reg = strtoupper($r['REG']);
            if ($doc === null || $reg === '') {
                $skipped++;

                continue;
            }
            if ($model::whereRaw("UPPER(TRIM({$numberCol})) = ?", [$doc])->exists()) {
                $skipped++; // one document = one record

                continue;
            }

            $ac = Aircraft::where('registration', $reg)->first();
            $sta = strtoupper($r['ACT STA']) ?: null;
            $date = DjaPersister::activeDate();
            $base = [
                'dja_id' => null, 'aircraft_registration' => $reg, $numberCol => $doc,
                'description' => $r['DESCRIPTION'] ?: null, 'act_station' => $sta, 'plan_station' => $sta,
                'status' => 'Closed', 'work_group' => $ac?->wg, 'close_date' => $date,
            ];

            $log = match ($model) {
                DmiLog::class => DmiLog::create($base + [
                    'date' => $date, 'category' => $r['CATEGORY'] ?: null, 'dmi_category' => $r['CATEGORY'] ?: null,
                    'remarks' => $r['REMARK'] ?: null, 'pn_required' => $r['PN REQUIRED'] ?: null,
                ]),
                NsrdiLog::class => NsrdiLog::create($base + [
                    'plan_date' => $date, 'report_date' => $date, 'refresh_date' => $date,
                    'category' => $r['CATEGORY'] ?: null, 'import_source' => 'web_unplanned',
                ]),
                default => WoLog::create($base + [
                    'date' => $date, 'operator' => app(AocResolver::class)->resolve($reg, null)?->code, 'type' => $ac?->fleet,
                    'man_hour' => is_numeric($r['MAN HOUR']) ? (float) $r['MAN HOUR'] : null,
                ]),
            };

            app(UnplannedSheetPusher::class)->push($log);   // silent on failure
            $created++;
        }

        $this->quickText = '';
        $this->isQuickOpen = false;
        $this->dispatch('notify', [
            'icon' => $created > 0 ? 'success' : 'warning',
            'message' => "{$created} unplanned tersimpan (Closed)".($skipped ? ", {$skipped} dilewati (kosong / sudah ada)." : '.'),
        ]);
    }
}
