<?php

namespace App\Support;

/**
 * The one rule for "which station does this WO / DMI / NSRDI belong to":
 *   - planned (it has a DJA task):  plan station, then the DJA's own station, then the actual station;
 *   - unplanned (no DJA task):      actual station, then plan station.
 * This is the rule of the COD report; Capacity and the dashboard follow it so the same task is never counted at two stations.
 */
class TaskStation
{
    /**
     * SQL for a log table joined to daily_job_assignments (planned rows).
     *
     * @param  string  $log  alias of the log table
     * @param  string  $dja  alias of daily_job_assignments
     */
    public static function plannedSql(string $log = 'l', string $dja = 'd'): string
    {
        return "COALESCE(NULLIF({$log}.plan_station, ''), NULLIF({$dja}.station, ''), NULLIF({$log}.act_station, ''))";
    }

    /** SQL for rows that have no DJA task. */
    public static function unplannedSql(string $log = ''): string
    {
        $p = $log === '' ? '' : $log.'.';

        return "COALESCE(NULLIF({$p}act_station, ''), NULLIF({$p}plan_station, ''))";
    }

    /** PHP side: the log may carry a loaded dailyJobAssignment relation. */
    public static function of(object $log): string
    {
        $isPlanned = ! empty($log->dja_id);
        $dja = $isPlanned && method_exists($log, 'relationLoaded') && $log->relationLoaded('dailyJobAssignment') ? $log->dailyJobAssignment?->station : null;

        $candidates = $isPlanned
            ? [$log->plan_station ?? null, $dja, $log->act_station ?? null]
            : [$log->act_station ?? null, $log->plan_station ?? null];

        foreach ($candidates as $value) {
            $value = strtoupper(trim((string) $value));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }
}
