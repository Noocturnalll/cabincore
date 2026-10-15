<?php

namespace App\Services\WhatsApp;

use App\Support\PlannedLogs;
use App\Support\TaskStation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The "CABIN ON-DUTY PRODUCTION" message the COD posts in the WhatsApp group.
 *
 * DJA R01/WO, DMI and NSRDI are counted per PLAN station (C = closed, R = still open / has a reason); the unplanned
 * NSRDI is counted per ACTUAL station. REASON OPEN counts the open WO and NSRDI (not DMI) by code reason.
 */
class CodProductionReport
{
    /** the order the COD always lists stations in; others are added after them when they have work */
    public const STATIONS = ['CGK', 'HLP', 'SUB', 'KNO', 'UPG', 'MDC', 'BPN', 'AMQ', 'SRG', 'PLM', 'DPS', 'KOE', 'LOP', 'SOC', 'PDG', 'BTH', 'YIA', 'PKU'];

    public const REASONS = ['AUTHOR', 'DEFFECT', 'GSE', 'IRR', 'MP', 'NS', 'NT', 'OCT', 'TC', 'WT', 'WTP', 'LT', 'OC'];

    public function text(string $from, string $to, ?string $station = null): string
    {
        $title = $from === $to
            ? strtoupper(Carbon::parse($from)->format('d M Y'))
            : strtoupper(Carbon::parse($from)->format('d M').' - '.Carbon::parse($to)->format('d M Y'));

        $wo = $this->planned('wo_logs', $from, $to, $station);
        $dmi = $this->planned('dmi_logs', $from, $to, $station);
        $nsrdi = $this->planned('nsrdi_logs', $from, $to, $station);
        $unplanned = $this->unplannedNsrdi($from, $to, $station);

        $out = ["> CABIN ON-DUTY PRODUCTION {$title}", ''];

        $out[] = '';
        $out[] = '> DJA R01/WO';
        array_push($out, ...$this->stationLines($wo));
        array_push($out, ...$this->totals($wo, 'DEPLOY: ', 'CLOSED: ', 'OPEN  : '));

        $out[] = '';
        $out[] = '';
        $out[] = '> DMI CBM ';
        array_push($out, ...$this->stationLines($dmi));
        array_push($out, ...$this->totals($dmi, 'DEPLOY: ', 'CLOSED: ', 'OPEN  : '));

        $out[] = '';
        $out[] = '';
        $out[] = '> NSRDI CBM DJA R01 ';
        array_push($out, ...$this->stationLines($nsrdi));
        array_push($out, ...$this->totals($nsrdi, 'DEPLOY : ', 'CLOSED : ', 'OPEN   : '));

        $out[] = '';
        $out[] = '';
        $out[] = '> REASON OPEN';
        $reasons = $this->reasons($from, $to, $station);
        foreach ($reasons as [$code, $n]) {
            $out[] = "`{$code}:` {$n}";
        }

        $out[] = '';
        $out[] = '';
        $out[] = '';
        $out[] = '> UNPLANED NSRDIL';
        foreach ($this->ordered(array_map(fn ($v) => $v['total'], $unplanned)) as $sta => $n) {
            $out[] = '* `'.$sta.':` '.($n ?: '-');
        }
        $out[] = '====================';
        $out[] = 'CLOSED: '.array_sum(array_column($unplanned, 'closed'));
        $out[] = '';
        $out[] = '';
        $out[] = '';
        $out[] = 'Regards';
        $out[] = '> Cabin On Duty';
        $out[] = '';
        $out[] = 'JAM KERJA: 07:00 - 19:00 WIB';
        $out[] = 'NOTED: JIKA ADA TAMBAHAN DI LUAR DJA YANG DI DEPLOY, MOHON DI INFO & SHARE WAG COD';

        return implode("\n", $out);
    }

    /** @return array<string, array{total: int, closed: int}> by plan station */
    private function planned(string $table, string $from, string $to, ?string $station): array
    {
        $sta = TaskStation::plannedSql('l', 'd');
        $q = DB::table("{$table} as l")->join('daily_job_assignments as d', 'd.id', '=', 'l.dja_id')
            ->whereDate('d.date', '>=', $from)->whereDate('d.date', '<=', $to);
        $q = PlannedLogs::latestInPeriod($q, $table, $from, $to);
        if ($station) {
            $q->whereRaw("{$sta} = ?", [$station]);
        }

        return $this->group($q, $sta);
    }

    /** @return array<string, array{total: int, closed: int}> by actual station */
    private function unplannedNsrdi(string $from, string $to, ?string $station): array
    {
        $sta = TaskStation::unplannedSql();
        $q = DB::table('nsrdi_logs')->whereNull('dja_id')->whereDate('plan_date', '>=', $from)->whereDate('plan_date', '<=', $to);
        if ($station) {
            $q->whereRaw("{$sta} = ?", [$station]);
        }

        return $this->group($q, $sta);
    }

    private function group($query, string $expr): array
    {
        $rows = $query->selectRaw("UPPER({$expr}) as s, COUNT(*) as total, SUM(CASE WHEN LOWER(status) = 'closed' THEN 1 ELSE 0 END) as done")
            ->groupBy('s')->get();

        $out = [];
        foreach ($rows as $r) {
            if ($r->s !== null && $r->s !== '') {
                $out[$r->s] = ['total' => (int) $r->total, 'closed' => (int) $r->done];
            }
        }

        return $out;
    }

    /** @return array<int, string> one line per station: `CGK:` 5 (C:4 R:1) */
    private function stationLines(array $by): array
    {
        $lines = [];
        foreach ($this->ordered(array_map(fn ($v) => $v['total'], $by)) as $sta => $total) {
            $c = $by[$sta]['closed'] ?? 0;
            $r = $total - $c;
            $lines[] = $total === 0
                ? '* `'.$sta.':` - (C:- R:-)'
                : '* `'.$sta.':` '.$total.' (C:'.($c ?: '-').' R:'.($r ?: '-').')';
        }

        return $lines;
    }

    private function totals(array $by, string $deploy, string $closed, string $open): array
    {
        $total = array_sum(array_column($by, 'total'));
        $done = array_sum(array_column($by, 'closed'));

        return ['====================', $deploy.$total, $closed.$done, $open.($total - $done)];
    }

    /**
     * The fixed station order first (shown even when empty), then any other station that has work, busiest first.
     *
     * @param  array<string, int>  $totals
     * @return array<string, int>
     */
    private function ordered(array $totals): array
    {
        $out = [];
        foreach (self::STATIONS as $sta) {
            $out[$sta] = $totals[$sta] ?? 0;
        }
        $extra = array_diff_key($totals, $out);
        arsort($extra);

        return $out + $extra;
    }

    /** @return array<int, array{0: string, 1: int|string}> code reason => number of open NSRDI */
    private function reasons(string $from, string $to, ?string $station): array
    {
        $sta = TaskStation::plannedSql('l', 'd');
        // open WO and open NSRDI (DMI does not count here)
        $counts = [];
        foreach (['wo_logs', 'nsrdi_logs'] as $table) {
            $q = DB::table("{$table} as l")->join('daily_job_assignments as d', 'd.id', '=', 'l.dja_id')
                ->whereDate('d.date', '>=', $from)->whereDate('d.date', '<=', $to)
                ->whereRaw("LOWER(l.status) <> 'closed'");
            $q = PlannedLogs::latestInPeriod($q, $table, $from, $to);
            if ($station) {
                $q->whereRaw("{$sta} = ?", [$station]);
            }
            foreach ($q->selectRaw('UPPER(TRIM(l.hold_reason_category)) as c, COUNT(*) as n')->groupBy('c')->get() as $r) {
                $counts[(string) $r->c] = ($counts[(string) $r->c] ?? 0) + (int) $r->n;
            }
        }

        $out = [];
        foreach (self::REASONS as $code) {
            $out[] = [$code, ($counts[$code] ?? 0) ?: '-'];
        }
        foreach ($counts as $code => $n) {
            if ($code !== '' && ! in_array($code, self::REASONS, true)) {
                $out[] = [$code, $n];
            }
        }

        return $out;
    }
}
