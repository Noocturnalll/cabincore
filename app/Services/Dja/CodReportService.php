<?php

namespace App\Services\Dja;

use App\Models\CmlLog;
use App\Models\DailyJobAssignment;
use App\Models\DmiLog;
use App\Models\NsrdiLog;
use App\Models\WoLog;
use Carbon\Carbon;

class CodReportService
{
    public const STATIONS = [
        'CGK', 'HLP', 'SUB', 'KNO', 'UPG', 'MDC',
        'BPN', 'AMQ', 'SRG', 'PLM', 'DPS', 'KOE',
        'LOP', 'SOC', 'PDG', 'BTH', 'YIA', 'PKU',
    ];

    public const REASON_CODES = [
        'AUTHOR', 'DEFFECT', 'GSE', 'IRR', 'LT',
        'MP', 'NS', 'NT', 'OCT', 'TC', 'WT',
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
     * Generate the complete Cabin On-Duty Production report string for a given date.
     */
    public function generateReportText(?string $targetDate = null): string
    {
        $date = $targetDate ?: self::getDefaultOperationalDate();
        $dateFormatted = strtoupper(Carbon::parse($date)->format('j M Y'));

        $data = $this->getAggregatedData($date);

        $out = [];
        $out[] = "CABIN ON-DUTY PRODUCTION  {$dateFormatted}";
        $out[] = '------------------------------------------';
        $out[] = '';

        // 1. DJA R01/WO
        $out[] = 'DJA R01/WO';
        foreach (self::STATIONS as $st) {
            $row = $data['dja_wo'][$st];
            $reason = $row['reason'] ? ' '.$row['reason'] : '';
            $out[] = "- {$st}: C: {$row['closed']} R:{$reason}";
        }
        $out[] = '=============';
        $out[] = "DEPLOY: {$data['dja_wo_total']['deploy']}";
        $out[] = "CLOSED: {$data['dja_wo_total']['closed']}";
        $out[] = "OPEN  : {$data['dja_wo_total']['open']}";
        $out[] = '';
        $out[] = '';
        $out[] = '';

        // 2. DMI CBM
        $out[] = 'DMI CBM';
        foreach (self::STATIONS as $st) {
            $row = $data['dmi'][$st];
            $reason = $row['reason'] ? ' '.$row['reason'] : '';
            $out[] = "- {$st}: C: {$row['closed']} R:{$reason}";
        }
        $out[] = '=============';
        $out[] = "DEPLOY: {$data['dmi_total']['deploy']}";
        $out[] = "CLOSED: {$data['dmi_total']['closed']}";
        $out[] = "OPEN  : {$data['dmi_total']['open']}";
        $out[] = '';
        $out[] = '';
        $out[] = '';

        // 3. ALL FINDING
        $out[] = 'ALL FINDING';
        foreach (self::STATIONS as $st) {
            $row = $data['findings'][$st];
            $reason = $row['reason'] ? ' '.$row['reason'] : '';
            $out[] = "- {$st}: C: {$row['closed']} R:{$reason}";
        }
        $out[] = '=============';
        $out[] = "DEPLOY: {$data['findings_total']['deploy']}";
        $out[] = "CLOSED: {$data['findings_total']['closed']}";
        $out[] = "OPEN  : {$data['findings_total']['open']}";
        $out[] = '';
        $out[] = '';
        $out[] = '';

        // 4. NSRDI CBM DJA R01
        $out[] = 'NSRDI CBM DJA R01';
        foreach (self::STATIONS as $st) {
            $row = $data['nsrdi'][$st];
            $reason = $row['reason'] ? ' '.$row['reason'] : '';
            $out[] = "- {$st}: C: {$row['closed']} R:{$reason}";
        }
        $out[] = '============';
        $out[] = "DEPLOY : {$data['nsrdi_total']['deploy']}";
        $out[] = "CLOSED : {$data['nsrdi_total']['closed']}";
        $out[] = "OPEN   : {$data['nsrdi_total']['open']}";
        $out[] = '';
        $out[] = '';
        $out[] = '';

        // 5. REASON OPEN NSRDI
        $out[] = 'REASON OPEN NSRDI';
        foreach (self::REASON_CODES as $code) {
            $count = $data['reasons'][$code] ?? 0;
            $out[] = "{$code}\t: {$count}";
        }
        $out[] = '';
        $out[] = '';
        $out[] = '';

        // 6. UNPLANNED NSRDIL
        $out[] = 'UNPLANED NSRDIL';
        foreach (self::STATIONS as $st) {
            $row = $data['unplanned_nsrdi'][$st];
            $reason = $row['reason'] ? ' '.$row['reason'] : '';
            $out[] = "- {$st}: C: {$row['closed']} R:{$reason}";
        }
        $out[] = '===========';
        $out[] = "CLOSED: {$data['unplanned_nsrdi_total']['closed']}";
        $out[] = '';
        $out[] = '';
        $out[] = '';

        // Footer
        $out[] = 'Regards';
        $out[] = 'Cabin On Duty';
        $out[] = '';
        $out[] = 'JAM KERJA: 07:00 - 19:00 WIB';
        $out[] = 'NOTED: JIKA ADA TAMBAHAN DI LUAR DJA YANG DI DEPLOY, MOHON DI INFO & SHARE WAG COD';

        return implode("\n", $out);
    }

    /**
     * Get structured aggregation data per section and station.
     */
    public function getAggregatedData(string $date): array
    {
        // 1. DJA R01/WO
        $djaWo = [];
        $djaWoDeployTotal = 0;
        $djaWoClosedTotal = 0;
        $djaWoOpenTotal = 0;

        foreach (self::STATIONS as $st) {
            $deploy = DailyJobAssignment::whereDate('date', $date)
                ->where('station', $st)
                ->where(function ($q) {
                    $q->where('job_type', 'R01/WO')->orWhere('job_type', 'like', '%WO%')->orWhere('job_type', 'like', '%R01%');
                })
                ->count();

            $woLogs = WoLog::whereHas('dailyJobAssignment', function ($q) use ($date, $st) {
                $q->whereDate('date', $date)
                    ->where('station', $st)
                    ->where(function ($q2) {
                        $q2->where('job_type', 'R01/WO')->orWhere('job_type', 'like', '%WO%')->orWhere('job_type', 'like', '%R01%');
                    });
            })->get();

            $closed = $woLogs->where('status', 'Closed')->count();
            $openLogs = $woLogs->where('status', '!=', 'Closed');
            $open = $deploy > 0 ? max(0, $deploy - $closed) : $openLogs->count();

            $reason = $this->formatReasons($openLogs);

            $djaWo[$st] = ['deploy' => $deploy, 'closed' => $closed, 'open' => $open, 'reason' => $reason];
            $djaWoDeployTotal += $deploy;
            $djaWoClosedTotal += $closed;
            $djaWoOpenTotal += $open;
        }

        // 2. DMI CBM
        $dmi = [];
        $dmiDeployTotal = 0;
        $dmiClosedTotal = 0;
        $dmiOpenTotal = 0;

        foreach (self::STATIONS as $st) {
            $deploy = DailyJobAssignment::whereDate('date', $date)
                ->where('station', $st)
                ->where('job_type', 'like', '%DMI%')
                ->count();

            $dmiLogs = DmiLog::whereHas('dailyJobAssignment', function ($q) use ($date, $st) {
                $q->whereDate('date', $date)->where('station', $st)->where('job_type', 'like', '%DMI%');
            })->get();

            $closed = $dmiLogs->where('status', 'Closed')->count();
            $openLogs = $dmiLogs->where('status', '!=', 'Closed');
            $open = $deploy > 0 ? max(0, $deploy - $closed) : $openLogs->count();

            $reason = $this->formatReasons($openLogs);

            $dmi[$st] = ['deploy' => $deploy, 'closed' => $closed, 'open' => $open, 'reason' => $reason];
            $dmiDeployTotal += $deploy;
            $dmiClosedTotal += $closed;
            $dmiOpenTotal += $open;
        }

        // 3. ALL FINDING (CML / Findings)
        $findings = [];
        $findingsDeployTotal = 0;
        $findingsClosedTotal = 0;
        $findingsOpenTotal = 0;

        foreach (self::STATIONS as $st) {
            $cmlLogs = CmlLog::where('station', $st)
                ->whereDate('created_at', $date)
                ->get();

            $deploy = $cmlLogs->count();
            $closed = $cmlLogs->where('status', 'Closed')->count();
            $openLogs = $cmlLogs->where('status', '!=', 'Closed');
            $open = $deploy > 0 ? max(0, $deploy - $closed) : $openLogs->count();

            $reason = $this->formatReasons($openLogs);

            $findings[$st] = ['deploy' => $deploy, 'closed' => $closed, 'open' => $open, 'reason' => $reason];
            $findingsDeployTotal += $deploy;
            $findingsClosedTotal += $closed;
            $findingsOpenTotal += $open;
        }

        // 4. NSRDI CBM DJA R01
        $nsrdi = [];
        $nsrdiDeployTotal = 0;
        $nsrdiClosedTotal = 0;
        $nsrdiOpenTotal = 0;

        foreach (self::STATIONS as $st) {
            $deploy = DailyJobAssignment::whereDate('date', $date)
                ->where('station', $st)
                ->where(function ($q) {
                    $q->where('job_type', 'AOC/NSRDI')->orWhere('job_type', 'like', '%NSRDI%')->orWhere('job_type', 'like', '%AOC%');
                })
                ->count();

            $nsrdiLogs = NsrdiLog::whereHas('dailyJobAssignment', function ($q) use ($date, $st) {
                $q->whereDate('date', $date)
                    ->where('station', $st)
                    ->where(function ($q2) {
                        $q2->where('job_type', 'AOC/NSRDI')->orWhere('job_type', 'like', '%NSRDI%')->orWhere('job_type', 'like', '%AOC%');
                    });
            })->get();

            $closed = $nsrdiLogs->where('status', 'Closed')->count();
            $openLogs = $nsrdiLogs->where('status', '!=', 'Closed');
            $open = $deploy > 0 ? max(0, $deploy - $closed) : $openLogs->count();

            $reason = $this->formatReasons($openLogs);

            $nsrdi[$st] = ['deploy' => $deploy, 'closed' => $closed, 'open' => $open, 'reason' => $reason];
            $nsrdiDeployTotal += $deploy;
            $nsrdiClosedTotal += $closed;
            $nsrdiOpenTotal += $open;
        }

        // 5. REASON OPEN NSRDI
        $reasons = [];
        foreach (self::REASON_CODES as $code) {
            $count = NsrdiLog::where('status', '!=', 'Closed')
                ->where(function ($q) use ($code) {
                    if ($code === 'WT') {
                        $q->whereIn('code_open', ['WT', 'WTP'])
                            ->orWhereIn('hold_reason_category', ['WT', 'WTP']);
                    } elseif ($code === 'DEFFECT') {
                        $q->whereIn('code_open', ['DEFFECT', 'DEFECT'])
                            ->orWhereIn('hold_reason_category', ['DEFFECT', 'DEFECT']);
                    } else {
                        $q->where('code_open', $code)
                            ->orWhere('hold_reason_category', $code);
                    }
                })
                ->count();
            $reasons[$code] = $count;
        }

        // 6. UNPLANNED NSRDI
        $unplannedNsrdi = [];
        $unplannedNsrdiClosedTotal = 0;

        foreach (self::STATIONS as $st) {
            $unplannedLogs = NsrdiLog::whereNull('dja_id')
                ->where(function ($q) use ($st) {
                    $q->where('act_station', $st)->orWhere('plan_station', $st);
                })
                ->where(function ($q) use ($date) {
                    $q->whereDate('close_date', $date)
                        ->orWhereDate('created_at', $date);
                })
                ->get();

            $closed = $unplannedLogs->where('status', 'Closed')->count();
            $openLogs = $unplannedLogs->where('status', '!=', 'Closed');
            $reason = $this->formatReasons($openLogs);

            $unplannedNsrdi[$st] = ['closed' => $closed, 'reason' => $reason];
            $unplannedNsrdiClosedTotal += $closed;
        }

        return [
            'dja_wo' => $djaWo,
            'dja_wo_total' => ['deploy' => $djaWoDeployTotal, 'closed' => $djaWoClosedTotal, 'open' => $djaWoOpenTotal],
            'dmi' => $dmi,
            'dmi_total' => ['deploy' => $dmiDeployTotal, 'closed' => $dmiClosedTotal, 'open' => $dmiOpenTotal],
            'findings' => $findings,
            'findings_total' => ['deploy' => $findingsDeployTotal, 'closed' => $findingsClosedTotal, 'open' => $findingsOpenTotal],
            'nsrdi' => $nsrdi,
            'nsrdi_total' => ['deploy' => $nsrdiDeployTotal, 'closed' => $nsrdiClosedTotal, 'open' => $nsrdiOpenTotal],
            'reasons' => $reasons,
            'unplanned_nsrdi' => $unplannedNsrdi,
            'unplanned_nsrdi_total' => ['closed' => $unplannedNsrdiClosedTotal],
        ];
    }

    /**
     * Format reason string for open logs in a station.
     */
    private function formatReasons($logs): string
    {
        if ($logs->isEmpty()) {
            return '';
        }

        $codes = $logs->map(function ($l) {
            $val = $l->code_open ?: $l->hold_reason_category ?: $l->reason_open ?: $l->remarks ?: null;
            if ($val === 'WTP') {
                return 'WT';
            }
            if ($val === 'DEFECT') {
                return 'DEFFECT';
            }

            return $val;
        })->filter()->values();

        if ($codes->isEmpty()) {
            return '';
        }

        return $codes->countBy()->map(function ($count, $code) {
            return $count > 1 ? "{$code} ({$count})" : (string) $code;
        })->implode(', ');
    }
}
