<?php

namespace App\Services\Dja;

use App\Models\DailyJobAssignment;
use App\Models\DmiLog;
use App\Models\NsrdiLog;
use App\Models\WoLog;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CodReportService
{
    public const STATIONS = [
        'CGK', 'HLP', 'SUB', 'KNO', 'UPG', 'MDC',
        'BPN', 'AMQ', 'SRG', 'PLM', 'DPS', 'KOE',
        'LOP', 'SOC', 'PDG', 'BTH', 'YIA', 'PKU',
    ];

    public const REASON_CODES = [
        'AUTHOR', 'DEFFECT', 'GSE', 'IRR', 'MP',
        'NS', 'NT', 'OCT', 'TC', 'WT', 'WTP',
    ];

    /**
     * Get the operational reporting date based on shift cutoff (18:00 WIB).
     * Before 18:00 (e.g. morning duty 09:00), the operational date is D-1 (yesterday).
     * At or after 18:00, the operational date is the current calendar day.
     */
    public static function getDefaultOperationalDate(): string
    {
        return now()->hour >= 18 ? now()->format('Y-m-d') : now()->subDay()->format('Y-m-d');
    }

    /**
     * Generate the complete Cabin On-Duty Production report string matching the operational format.
     */
    public function generateReportText(?string $targetDate = null): string
    {
        $date = $targetDate ?: self::getDefaultOperationalDate();
        $dateFormatted = strtoupper(Carbon::parse($date)->format('j M Y'));

        $data = $this->getAggregatedData($date);

        $out = [];
        $out[] = "> CABIN ON-DUTY PRODUCTION {$dateFormatted}";
        $out[] = '';
        $out[] = '';

        // 1. DJA R01/WO
        $out[] = '> DJA R01/WO';
        foreach ($data['dja_wo_stations'] as $st) {
            $row = $data['dja_wo'][$st];
            $out[] = $this->formatStationLine($st, $row['deploy'], $row['closed'], $row['open']);
        }
        $out[] = '====================';
        $out[] = "DEPLOY: {$data['dja_wo_total']['deploy']}";
        $out[] = "CLOSED: {$data['dja_wo_total']['closed']}";
        $out[] = "OPEN  : {$data['dja_wo_total']['open']}";
        $out[] = '';
        $out[] = '';

        // 2. DMI CBM
        $out[] = '> DMI CBM ';
        foreach ($data['dmi_stations'] as $st) {
            $row = $data['dmi'][$st];
            $out[] = $this->formatStationLine($st, $row['deploy'], $row['closed'], $row['open']);
        }
        $out[] = '====================';
        $out[] = "DEPLOY: {$data['dmi_total']['deploy']}";
        $out[] = "CLOSED: {$data['dmi_total']['closed']}";
        $out[] = "OPEN  : {$data['dmi_total']['open']}";
        $out[] = '';
        $out[] = '';

        // 3. NSRDI CBM DJA R01
        $out[] = '> NSRDI CBM DJA R01 ';
        foreach ($data['nsrdi_stations'] as $st) {
            $row = $data['nsrdi'][$st];
            $out[] = $this->formatStationLine($st, $row['deploy'], $row['closed'], $row['open']);
        }
        $out[] = '====================';
        $out[] = "DEPLOY : {$data['nsrdi_total']['deploy']}";
        $out[] = "CLOSED : {$data['nsrdi_total']['closed']}";
        $out[] = "OPEN   : {$data['nsrdi_total']['open']}";
        $out[] = '';
        $out[] = '';

        // 4. REASON OPEN
        $out[] = '> REASON OPEN';
        foreach (self::REASON_CODES as $code) {
            $cnt = $data['reasons'][$code] ?? 0;
            $cntStr = $cnt > 0 ? (string) $cnt : '';
            $out[] = "`{$code}:`{$cntStr}";
        }
        $out[] = '';
        $out[] = '';

        // 5. UNPLANED NSRDIL
        $out[] = '> UNPLANED NSRDIL';
        foreach (self::STATIONS as $st) {
            $row = $data['unplanned_nsrdi'][$st];
            $val = '-';
            if ($row['closed'] > 0) {
                $val = (string) $row['closed'];
            } elseif ($row['total'] > 0) {
                $val = '0';
            }
            $out[] = "- `{$st}:`{$val}";
        }
        $out[] = '====================';
        $out[] = "CLOSED: {$data['unplanned_nsrdi_total']['closed']}";
        $out[] = '';
        $out[] = '';
        $out[] = '';
        $out[] = '';

        // Footer
        $out[] = 'Regards';
        $out[] = '> Cabin On Duty';
        $out[] = '';
        $out[] = 'JAM KERJA: 19:00 - 07:00 WIB';
        $out[] = 'NOTED: JIKA ADA TAMBAHAN DI LUAR DJA YANG DI DEPLOY, MOHON DI INFO & SHARE WAG COD';

        return implode("\n", $out);
    }

    /**
     * Format individual station row.
     */
    private function formatStationLine(string $st, int $deploy, int $closed, int $open): string
    {
        if ($deploy === 0 && $closed === 0 && $open === 0) {
            return "- `{$st}:` - (C:- R:-)";
        }

        $deployStr = (string) $deploy;
        $cStr = $closed > 0 ? (string) $closed : '-';
        $rStr = $open > 0 ? (string) $open : '-';

        return "- `{$st}:` {$deployStr} (C:{$cStr} R:{$rStr})";
    }

    /**
     * Get structured aggregation data per section and station.
     */
    public function getAggregatedData(string $date): array
    {
        // 1. DJA R01/WO
        $allWoDjas = DailyJobAssignment::whereDate('date', $date)
            ->where(function ($q) {
                $q->where('job_type', 'R01/WO')
                    ->orWhere('job_type', 'like', '%WO%')
                    ->orWhere('job_type', 'like', '%R01%');
            })->get();

        $allWoLogs = WoLog::where(function ($q) use ($date) {
            $q->whereDate('date', $date)
                ->orWhereHas('dailyJobAssignment', fn ($d) => $d->whereDate('date', $date));
        })->get();

        [$djaWoStations, $djaWo, $djaWoTotal] = $this->aggregateSection($allWoDjas, $allWoLogs);

        // 2. DMI CBM
        $allDmiDjas = DailyJobAssignment::whereDate('date', $date)
            ->where('job_type', 'like', '%DMI%')
            ->get();

        $allDmiLogs = DmiLog::where(function ($q) use ($date) {
            $q->whereDate('date', $date)
                ->orWhereHas('dailyJobAssignment', fn ($d) => $d->whereDate('date', $date));
        })->get();

        [$dmiStations, $dmi, $dmiTotal] = $this->aggregateSection($allDmiDjas, $allDmiLogs);

        // 3. NSRDI CBM DJA R01
        $allNsrdiDjas = DailyJobAssignment::whereDate('date', $date)
            ->where(function ($q) {
                $q->where('job_type', 'AOC/NSRDI')
                    ->orWhere('job_type', 'like', '%NSRDI%')
                    ->orWhere('job_type', 'like', '%AOC%');
            })->get();

        $allNsrdiLogs = NsrdiLog::where(function ($q) use ($date) {
            $q->whereDate('plan_date', $date)
                ->orWhereDate('report_date', $date)
                ->orWhereHas('dailyJobAssignment', fn ($d) => $d->whereDate('date', $date));
        })->get();

        [$nsrdiStations, $nsrdi, $nsrdiTotal] = $this->aggregateSection($allNsrdiDjas, $allNsrdiLogs);

        // 4. REASON OPEN
        $openLogs = NsrdiLog::where('status', '!=', 'Closed')
            ->where(function ($q) use ($date) {
                $q->whereHas('dailyJobAssignment', fn ($d) => $d->whereDate('date', $date))
                    ->orWhereDate('plan_date', $date)
                    ->orWhereDate('report_date', $date)
                    ->orWhereDate('created_at', $date);
            })->get();

        $woOpenLogs = WoLog::where('status', '!=', 'Closed')
            ->where(function ($q) use ($date) {
                $q->whereHas('dailyJobAssignment', fn ($d) => $d->whereDate('date', $date))
                    ->orWhereDate('date', $date);
            })->get();

        $allOpen = $openLogs->concat($woOpenLogs);
        $reasons = array_fill_keys(self::REASON_CODES, 0);

        foreach ($allOpen as $log) {
            $raw = strtoupper(trim((string) ($log->code_open ?: ($log->hold_reason_category ?: ($log->reason_open ?: ($log->remarks ?? ''))))));
            if ($raw === '') {
                continue;
            }

            foreach (self::REASON_CODES as $code) {
                if ($code === 'WT') {
                    if ($raw === 'WT' || (preg_match('/\bWT\b/', $raw) && ! str_contains($raw, 'WTP'))) {
                        $reasons['WT']++;
                        break;
                    }
                } elseif ($code === 'WTP') {
                    if ($raw === 'WTP' || str_contains($raw, 'WTP')) {
                        $reasons['WTP']++;
                        break;
                    }
                } elseif ($code === 'DEFFECT') {
                    if (str_contains($raw, 'DEFFECT') || str_contains($raw, 'DEFECT')) {
                        $reasons['DEFFECT']++;
                        break;
                    }
                } elseif ($code === 'NS') {
                    if ($raw === 'NS' || str_contains($raw, 'NO SPARE') || str_contains($raw, 'NSP')) {
                        $reasons['NS']++;
                        break;
                    }
                } elseif ($code === 'MP') {
                    if ($raw === 'MP' || str_contains($raw, 'MANPOWER')) {
                        $reasons['MP']++;
                        break;
                    }
                } else {
                    if ($raw === $code || str_contains($raw, $code)) {
                        $reasons[$code]++;
                        break;
                    }
                }
            }
        }

        // 5. UNPLANNED NSRDI
        $unplannedLogs = NsrdiLog::whereNull('dja_id')
            ->where(function ($q) use ($date) {
                $q->whereDate('close_date', $date)
                    ->orWhereDate('plan_date', $date)
                    ->orWhereDate('report_date', $date)
                    ->orWhereDate('created_at', $date);
            })->get();

        $unplannedNsrdi = [];
        $unplannedClosedTotal = 0;
        foreach (self::STATIONS as $st) {
            $stLogs = $unplannedLogs->filter(function ($l) use ($st) {
                $station = $l->act_station ?: ($l->plan_station ?: null);

                return $station === $st;
            });
            $closed = $stLogs->where('status', 'Closed')->count();
            $total = $stLogs->count();
            $unplannedNsrdi[$st] = [
                'closed' => $closed,
                'total' => $total,
            ];
            $unplannedClosedTotal += $closed;
        }

        return [
            'dja_wo_stations' => $djaWoStations,
            'dja_wo' => $djaWo,
            'dja_wo_total' => $djaWoTotal,
            'dmi_stations' => $dmiStations,
            'dmi' => $dmi,
            'dmi_total' => $dmiTotal,
            'nsrdi_stations' => $nsrdiStations,
            'nsrdi' => $nsrdi,
            'nsrdi_total' => $nsrdiTotal,
            'reasons' => $reasons,
            'unplanned_nsrdi' => $unplannedNsrdi,
            'unplanned_nsrdi_total' => ['closed' => $unplannedClosedTotal],
        ];
    }

    /**
     * Aggregate station numbers for a section.
     *
     * @return array{0: array<int, string>, 1: array<string, array{deploy: int, closed: int, open: int}>, 2: array{deploy: int, closed: int, open: int}}
     */
    private function aggregateSection(Collection $djas, Collection $logs): array
    {
        $allStations = $this->collectAllStations($djas, $logs);
        $result = [];
        $totalDeploy = 0;
        $totalClosed = 0;
        $totalOpen = 0;

        foreach ($allStations as $st) {
            $stationDjas = $djas->where('station', $st);
            $stationLogs = $logs->filter(function ($log) use ($st, $stationDjas) {
                if ($log->dja_id && $stationDjas->contains('id', $log->dja_id)) {
                    return true;
                }
                $logStation = $log->act_station ?: ($log->plan_station ?: $log->dailyJobAssignment?->station);

                return $logStation === $st;
            });

            $deploy = max($stationDjas->count(), $stationLogs->count());
            $closed = $stationLogs->where('status', 'Closed')->count();
            $open = $deploy > 0 ? max(0, $deploy - $closed) : $stationLogs->where('status', '!=', 'Closed')->count();

            if ($deploy === 0 && ($closed > 0 || $open > 0)) {
                $deploy = $closed + $open;
            }

            $result[$st] = [
                'deploy' => $deploy,
                'closed' => $closed,
                'open' => $open,
            ];

            $totalDeploy += $deploy;
            $totalClosed += $closed;
            $totalOpen += $open;
        }

        // Exclude extra stations that have zero activity
        $visibleStations = array_values(array_filter($allStations, function ($st) use ($result) {
            if (in_array($st, self::STATIONS, true)) {
                return true;
            }
            $row = $result[$st] ?? null;

            return $row && ($row['deploy'] > 0 || $row['closed'] > 0 || $row['open'] > 0);
        }));

        return [
            $visibleStations,
            $result,
            ['deploy' => $totalDeploy, 'closed' => $totalClosed, 'open' => $totalOpen],
        ];
    }

    /**
     * Collect base 18 stations + any dynamic extra stations alphabetically.
     *
     * @return array<int, string>
     */
    private function collectAllStations(Collection $djas, Collection $logs): array
    {
        $extra = [];

        foreach ($djas as $dja) {
            $st = strtoupper(trim((string) $dja->station));
            if ($st !== '' && ! in_array($st, self::STATIONS, true)) {
                $extra[$st] = true;
            }
        }

        foreach ($logs as $log) {
            $st = strtoupper(trim((string) ($log->act_station ?: ($log->plan_station ?: $log->dailyJobAssignment?->station))));
            if ($st !== '' && ! in_array($st, self::STATIONS, true)) {
                $extra[$st] = true;
            }
        }

        $extraKeys = array_keys($extra);
        sort($extraKeys);

        return array_merge(self::STATIONS, $extraKeys);
    }
}
