<?php

namespace App\Services\Kpi;

use App\Services\Master\MasterSettings;
use Illuminate\Support\Facades\DB;

/**
 * Man hours for a period.
 *   capacity = technicians (day + night, Master Capacity) x effective hours (config kpi.effective_hours) x days
 *   used     = WO / DMI / NSRDI / CML: the man_hour column (filled by the leader report import). Cleaning: end_at - start_at.
 * Each module also reports `records` and `timed`, so a half-filled period shows as such instead of a false low number.
 */
class ManHourService
{
    public function capacity(ReportPeriod $period): array
    {
        return $this->rosterCapacity($period) ?? $this->masterCapacity($period);
    }

    /**
     * From the imported roster: every working person-shift of the capacity teams x the effective hours of that shift.
     * Null when the roster has nothing for the period (the Master Capacity numbers are used instead).
     */
    private function rosterCapacity(ReportPeriod $period): ?array
    {
        $perShift = DB::table('roster_entries')
            ->whereDate('work_date', '>=', $period->from->toDateString())->whereDate('work_date', '<=', $period->to->toDateString())
            ->whereIn('team', app(MasterSettings::class)->capacityTeams())->whereNotNull('shift')
            ->select('shift', DB::raw('COUNT(*) as n'))->groupBy('shift')->pluck('n', 'shift');
        if ($perShift->isEmpty()) {
            return null;
        }

        $hoursByShift = app(MasterSettings::class)->shiftHours();
        $hours = $perShift->sum(fn ($n, $shift) => $n * ($hoursByShift[$shift] ?? config('kpi.effective_hours')));

        return [
            'source' => 'roster',
            'technicians' => (int) round($perShift->sum() / $period->days()),   // average people working per day
            'hours_per_tech_day' => round($hours / max(1, $perShift->sum()), 1),
            'days' => $period->days(),
            'hours' => round($hours, 1),
            'per_shift' => $perShift->all(),
        ];
    }

    private function masterCapacity(ReportPeriod $period): array
    {
        $technicians = (int) DB::table('capacity_stations')
            ->selectRaw('COALESCE(SUM(COALESCE(tech_day,0) + COALESCE(tech_night,0)),0) as n')->value('n');
        $perDay = $technicians * (float) config('kpi.effective_hours');

        return [
            'source' => 'master',
            'technicians' => $technicians,
            'hours_per_tech_day' => (float) config('kpi.effective_hours'),
            'days' => $period->days(),
            'hours' => round($perDay * $period->days(), 1),
        ];
    }

    /** @return array<string, array{label: string, hours: float|null, records: int, timed: int}> */
    public function used(ReportPeriod $period): array
    {
        $from = $period->from->toDateString();
        $to = $period->to->toDateString();

        // WO, DMI, NSRDI and CML carry man_hour (leader report: man power x finish-start, or the sheet's MAN HOUR for WO)
        $result = [];
        $logs = [
            'wo' => ['WO', 'wo_logs', 'date'],
            'dmi' => ['DMI', 'dmi_logs', 'date'],
            'nsrdi' => ['NSRDI', 'nsrdi_logs', 'refresh_date'],
            'cml' => ['CML', 'cml_logs', 'date'],
        ];
        foreach ($logs as $key => [$label, $table, $dateColumn]) {
            $base = DB::table($table)->whereBetween($dateColumn, [$from, $to]);
            $timed = (clone $base)->where('man_hour', '>', 0)->count();
            $result[$key] = [
                'label' => $label,
                'hours' => $timed ? round((float) (clone $base)->sum('man_hour'), 1) : null,
                'records' => (clone $base)->count(),
                'timed' => $timed,
            ];
        }

        // Aircraft cleaning has no man power yet: its working time (finish - start) is counted
        $cleaning = DB::table('aircraft_cleanings')->whereBetween('date', [$from, $to]);
        $result['cleaning'] = [
            'label' => 'Aircraft Cleaning',
            'hours' => round($this->durationHours(clone $cleaning), 1),
            'records' => (clone $cleaning)->count(),
            'timed' => (clone $cleaning)->whereNotNull('start_at')->whereNotNull('end_at')->whereColumn('end_at', '>', 'start_at')->count(),
        ];

        return $result;
    }

    /** Sum of (end_at - start_at) in hours, computed in PHP so it behaves the same on SQLite, MySQL and Postgres. */
    private function durationHours($query): float
    {
        $seconds = 0;
        $query->whereNotNull('start_at')->whereNotNull('end_at')->select('start_at', 'end_at')
            ->orderBy('start_at')->each(function ($row) use (&$seconds) {
                $diff = strtotime($row->end_at) - strtotime($row->start_at);
                if ($diff > 0) {
                    $seconds += $diff;
                }
            });

        return $seconds / 3600;
    }

    public function summary(ReportPeriod $period): array
    {
        $capacity = $this->capacity($period);
        $used = $this->used($period);
        $total = round(array_sum(array_map(fn ($m) => (float) $m['hours'], $used)), 1);
        $utilisation = $capacity['hours'] > 0 ? round($total / $capacity['hours'] * 100, 1) : null;

        return [
            'period' => $period,
            'capacity' => $capacity,
            'used' => $used,
            'total_used' => $total,
            'utilisation' => $utilisation,
            'status' => AchievementStatus::for($utilisation, 100),
        ];
    }
}
