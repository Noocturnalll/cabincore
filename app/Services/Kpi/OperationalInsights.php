<?php

namespace App\Services\Kpi;

use App\Support\TaskStation;
use Illuminate\Support\Facades\DB;

/**
 * Extra pivots over WO / DMI / NSRDI / CML for one period: by work group, by operator (AOC), aging of what is still
 * open, the reasons behind open work, planned vs unplanned per station, and man hours (CML / DBI, the KPI basis).
 * Everything follows the station scope of the user, like the operational pivot.
 */
class OperationalInsights
{
    /** key => [label, table, date column, work group column, operator column] */
    private const SOURCES = [
        'wo' => ['WO', 'wo_logs', 'date', 'work_group', 'operator'],
        'dmi' => ['DMI', 'dmi_logs', 'date', 'work_group', 'aoc'],
        'nsrdi' => ['NSRDI', 'nsrdi_logs', 'plan_date', 'work_group', 'aoc'],
    ];

    private const AGING = ['0-3 hari' => [0, 3], '4-7 hari' => [4, 7], '8-14 hari' => [8, 14], '>14 hari' => [15, 100000]];

    /**
     * @param  array<int, string>|null  $stations
     * @return array<string, mixed>
     */
    public function build(ReportPeriod $period, ?array $stations = null): array
    {
        $from = $period->from->toDateString();
        $to = $period->to->toDateString();
        $sta = TaskStation::unplannedSql();
        $scoped = fn ($q) => $stations === null ? $q : $q->whereIn(DB::raw($sta), $stations);
        $closed = "LOWER(status) = 'closed'";

        $byWg = $byAoc = $aging = $reasons = [];
        $planned = [];

        foreach (self::SOURCES as $key => [$label, $table, $dateCol, $wgCol, $opCol]) {
            $period = fn () => $scoped(DB::table($table)->whereDate($dateCol, '>=', $from)->whereDate($dateCol, '<=', $to));

            foreach ([$wgCol => &$byWg, $opCol => &$byAoc] as $col => &$bucket) {
                $rows = $period()->selectRaw("COALESCE(NULLIF(TRIM({$col}), ''), '(kosong)') as k, COUNT(*) as total, SUM(CASE WHEN {$closed} THEN 1 ELSE 0 END) as done")
                    ->groupBy('k')->get();
                foreach ($rows as $r) {
                    $bucket[$r->k][$key] = ['total' => (int) $r->total, 'closed' => (int) $r->done];
                }
            }
            unset($bucket);

            // still open now (not limited to the period): how old, and why
            $open = $scoped(DB::table($table)->whereRaw("NOT ({$closed})"));
            $dates = (clone $open)->select($dateCol.' as d')->whereNotNull($dateCol)->pluck('d');
            foreach (self::AGING as $name => [$min, $max]) {
                $aging[$name][$key] = 0;
            }
            foreach ($dates as $d) {
                $age = max(0, (int) floor((time() - strtotime((string) $d)) / 86400));
                foreach (self::AGING as $name => [$min, $max]) {
                    if ($age >= $min && $age <= $max) {
                        $aging[$name][$key]++;
                        break;
                    }
                }
            }
            foreach ((clone $open)->selectRaw("COALESCE(NULLIF(TRIM(hold_reason_category), ''), '(belum ada code)') as k, COUNT(*) as n")->groupBy('k')->get() as $r) {
                $reasons[$r->k] = ($reasons[$r->k] ?? 0) + (int) $r->n;
            }

            // planned vs unplanned per station
            foreach ($period()->selectRaw("COALESCE(NULLIF({$sta}, ''), '(tanpa station)') as s, SUM(CASE WHEN dja_id IS NULL THEN 0 ELSE 1 END) as p, SUM(CASE WHEN dja_id IS NULL THEN 1 ELSE 0 END) as u")->groupBy('s')->get() as $r) {
                $planned[$r->s]['planned'] = ($planned[$r->s]['planned'] ?? 0) + (int) $r->p;
                $planned[$r->s]['unplanned'] = ($planned[$r->s]['unplanned'] ?? 0) + (int) $r->u;
            }
        }

        arsort($reasons);

        // the planner-sheet columns of the WO: class, planner and PPC on duty
        $bySheet = [];
        foreach (['class_wo' => 'CLASS WO', 'planner' => 'PLANNER', 'ppc_onduty' => 'PPC ONDUTY'] as $col => $label) {
            $rows = $scoped(DB::table('wo_logs')->whereDate('date', '>=', $from)->whereDate('date', '<=', $to))
                ->selectRaw("COALESCE(NULLIF(TRIM({$col}), ''), '(kosong)') as k, COUNT(*) as total, SUM(CASE WHEN {$closed} THEN 1 ELSE 0 END) as done")
                ->groupBy('k')->orderByDesc('total')->limit(15)->get();
            $bySheet[$label] = $rows->map(fn ($r) => ['name' => $r->k, 'total' => (int) $r->total, 'closed' => (int) $r->done])->all();
        }

        return [
            'byWg' => $this->rows($byWg),
            'byAoc' => $this->rows($byAoc),
            'aging' => $aging,
            'bySheet' => $bySheet,
            'reasons' => array_slice($reasons, 0, 10, true),
            'planned' => collect($planned)->sortByDesc(fn ($v) => $v['planned'] + $v['unplanned'])->all(),
            'manHours' => $this->manHours($from, $to, $stations),
        ];
    }

    /** CML and DBI: hours worked per station and per shift, the average per job, and how many jobs are timed. */
    private function manHours(string $from, string $to, ?array $stations): array
    {
        $base = fn () => DB::table('cml_logs')->whereDate('date', '>=', $from)->whereDate('date', '<=', $to)
            ->when($stations !== null, fn ($q) => $q->whereIn('station', $stations));

        $perStation = [];
        foreach (['CML', 'DBI'] as $kind) {
            foreach ($base()->where('kind', $kind)->selectRaw("COALESCE(NULLIF(TRIM(station), ''), '(tanpa station)') as s, COUNT(*) as jobs, SUM(COALESCE(man_hour, 0)) as hrs, SUM(CASE WHEN man_hour > 0 THEN 1 ELSE 0 END) as timed")->groupBy('s')->get() as $r) {
                $perStation[$r->s][$kind] = ['jobs' => (int) $r->jobs, 'hours' => round((float) $r->hrs, 1), 'timed' => (int) $r->timed];
            }
        }

        $perShift = $base()->selectRaw("COALESCE(NULLIF(TRIM(shift), ''), '(kosong)') as s, COUNT(*) as jobs, SUM(COALESCE(man_hour, 0)) as hrs")
            ->groupBy('s')->get()->map(fn ($r) => ['shift' => $r->s, 'jobs' => (int) $r->jobs, 'hours' => round((float) $r->hrs, 1)])->all();

        $all = $base();
        $jobs = (clone $all)->count();
        $timed = (clone $all)->where('man_hour', '>', 0)->count();
        $hours = round((float) (clone $all)->sum('man_hour'), 1);

        return [
            'perStation' => $perStation,
            'perShift' => $perShift,
            'jobs' => $jobs,
            'timed' => $timed,
            'hours' => $hours,
            'avgPerJob' => $timed ? round($hours / $timed, 2) : null,
            'coverage' => $jobs ? round($timed / $jobs * 100, 1) : null,
        ];
    }

    private function rows(array $bucket): array
    {
        uasort($bucket, fn ($a, $b) => array_sum(array_column($b, 'total')) <=> array_sum(array_column($a, 'total')));

        return $bucket;
    }
}
