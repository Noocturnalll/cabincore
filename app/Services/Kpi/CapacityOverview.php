<?php

namespace App\Services\Kpi;

use App\Services\Aiec\StationCapabilityService;
use Illuminate\Support\Facades\DB;

/**
 * Capacity of every division against what was really used, so capacity reads as a target.
 *
 *  divisions  roster capacity (people x effective hours) against the man hours the logs carry:
 *             CBM = WO + DMI + CML + non-paint NSRDI, Painting = paint NSRDI, AIEC = cleaning time.
 *             IRREG and FINISHING have capacity but no job logs, so only their capacity is shown.
 *  aiec       capability per station (DCI / DCE per day, from the AIEC master) against the real average and peak
 *             per day in the period.
 */
class CapacityOverview
{
    /** team => label */
    public const DIVISIONS = ['CBM' => 'Cabin Maintenance', 'PAINTING' => 'Aircraft Painting', 'AIEC' => 'Aircraft Cleaning (AIEC)', 'IRREG' => 'Team Irreg', 'FINISHING' => 'Finishing'];

    /**
     * @param  array<string, array{hours?: float, mp_avg?: float}>  $teams  the roster capacity per team (StationKpiService)
     * @param  array<int, string>|null  $stations
     * @return array{divisions: array<int, array<string, mixed>>, aiec: array<int, array<string, mixed>>}
     */
    public function build(ReportPeriod $period, array $teams, ?array $stations = null): array
    {
        $from = $period->from->toDateString();
        $to = $period->to->toDateString();
        $days = max(1, $period->days());

        $used = $this->usedByDivision($from, $to, $stations);

        $divisions = [];
        foreach (self::DIVISIONS as $team => $label) {
            $capacity = (float) ($teams[$team]['hours'] ?? 0);
            $hours = $used[$team] ?? null;
            $pct = ($hours !== null && $capacity > 0) ? round($hours / $capacity * 100, 1) : null;
            $divisions[] = [
                'team' => $team,
                'label' => $label,
                'mp_avg' => $teams[$team]['mp_avg'] ?? null,
                'capacity' => round($capacity, 1),
                'used' => $hours !== null ? round($hours, 1) : null,
                'gap' => $hours !== null ? round($capacity - $hours, 1) : null,
                'pct' => $pct,
                'state' => $this->state($pct),
            ];
        }

        return ['divisions' => $divisions, 'aiec' => $this->aiecCapability($from, $to, $days, $stations)];
    }

    /** @return array<string, float|null> team => hours used (null = no job log carries hours for that team) */
    private function usedByDivision(string $from, string $to, ?array $stations): array
    {
        $in = fn ($q, string $col) => $stations === null ? $q : $q->whereIn(DB::raw("UPPER($col)"), $stations);
        $sum = fn ($q) => (float) $q->sum('man_hour');

        $wo = $sum($in(DB::table('wo_logs')->whereDate('date', '>=', $from)->whereDate('date', '<=', $to), 'COALESCE(act_station, plan_station)'));
        $dmi = $sum($in(DB::table('dmi_logs')->whereDate('date', '>=', $from)->whereDate('date', '<=', $to), 'COALESCE(act_station, plan_station)'));
        $cml = $sum($in(DB::table('cml_logs')->whereDate('date', '>=', $from)->whereDate('date', '<=', $to), 'station'));
        $nsrdi = fn (bool $paint) => $sum($in(
            DB::table('nsrdi_logs')->whereDate('plan_date', '>=', $from)->whereDate('plan_date', '<=', $to)->where('category', $paint ? '=' : '!=', 'PAINTING'),
            'COALESCE(act_station, plan_station)'
        ));

        $cleaning = 0.0;
        $q = $in(DB::table('aircraft_cleanings')->whereDate('date', '>=', $from)->whereDate('date', '<=', $to)->whereNotNull('start_at')->whereNotNull('end_at'), 'station');
        $q->select('start_at', 'end_at')->orderBy('start_at')->each(function ($r) use (&$cleaning) {
            $d = strtotime($r->end_at) - strtotime($r->start_at);
            if ($d > 0 && $d <= (int) config('kpi.max_job_hours', 14) * 3600) {
                $cleaning += $d / 3600;
            }
        });

        return [
            'CBM' => $wo + $dmi + $cml + $nsrdi(false),
            'PAINTING' => $nsrdi(true),
            'AIEC' => $cleaning,
            'IRREG' => null,
            'FINISHING' => null,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function aiecCapability(string $from, string $to, int $days, ?array $stations): array
    {
        $perStation = [];
        $rows = DB::table('aircraft_cleanings')->whereDate('date', '>=', $from)->whereDate('date', '<=', $to)->whereIn('type', ['DCI', 'DCE'])
            ->when($stations !== null, fn ($q) => $q->whereIn(DB::raw('UPPER(station)'), $stations))
            ->selectRaw('UPPER(station) as s, type, date, COUNT(*) as n')->groupBy('s', 'type', 'date')->get();
        foreach ($rows as $r) {
            $perStation[$r->s][$r->type][$r->date] = (int) $r->n;
        }

        $out = [];
        foreach (StationCapabilityService::getStationCapabilities() as $code => $cap) {
            if ($stations !== null && ! in_array($code, $stations, true)) {
                continue;
            }
            $row = ['station' => $code, 'name' => $cap['name'] ?? $code, 'mp_plan' => $cap['mp_plan'] ?? null];
            $worst = null;
            foreach (['DCI' => 'dci_cap', 'DCE' => 'dce_cap'] as $type => $capKey) {
                $byDay = $perStation[$code][$type] ?? [];
                $limit = (int) ($cap[$capKey] ?? 0);
                $avg = $byDay ? round(array_sum($byDay) / $days, 1) : 0.0;
                $peak = $byDay ? max($byDay) : 0;
                $pct = $limit > 0 ? round($avg / $limit * 100, 1) : null;
                $row[$type] = ['cap' => $limit, 'avg' => $avg, 'peak' => $peak, 'pct' => $pct, 'over' => $limit > 0 && $peak > $limit];
                if ($pct !== null) {
                    $worst = max($worst ?? 0, $pct);
                }
            }
            $row['state'] = $this->state($worst);
            $out[] = $row;
        }

        usort($out, fn ($a, $b) => ($b['DCI']['cap'] + $b['DCE']['cap']) <=> ($a['DCI']['cap'] + $a['DCE']['cap']));

        return $out;
    }

    /** over 100% = overloaded, 80-100 on target, 50-80 room left, below 50 under used, null = nothing to compare */
    private function state(?float $pct): string
    {
        return match (true) {
            $pct === null => 'none',
            $pct > 100 => 'over',
            $pct >= 80 => 'good',
            $pct >= 50 => 'room',
            default => 'low',
        };
    }
}
