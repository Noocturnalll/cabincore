<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * A WO / DMI / NSRDI that is not finished is carried to the next day's DJA, so the same document number sits in the
 * log once per day (Open on the 25th, Closed on the 26th). Counting every row makes a week look bigger and its closed
 * rate lower than the work really was. Aggregates therefore count the LAST row of a document inside the period;
 * rows without a document number are kept as they are. The Daily Report itself stays one row per day.
 */
class PlannedLogs
{
    /** document number column of each log table */
    public const DOC_COLUMN = ['wo_logs' => 'wo_number', 'dmi_logs' => 'dmi_number', 'nsrdi_logs' => 'nsrdi_number'];

    /**
     * @param  Builder  $query  logs aliased as $alias, already joined to daily_job_assignments
     */
    public static function latestInPeriod(Builder $query, string $table, string $from, string $to, string $alias = 'l'): Builder
    {
        $doc = self::DOC_COLUMN[$table] ?? null;
        if ($doc === null) {
            return $query;
        }

        return $query->where(function ($w) use ($table, $doc, $from, $to, $alias) {
            $w->whereNull("{$alias}.{$doc}")->orWhereNotExists(function ($sub) use ($table, $doc, $from, $to, $alias) {
                $sub->select(DB::raw(1))->from("{$table} as later")
                    ->join('daily_job_assignments as later_d', 'later_d.id', '=', 'later.dja_id')
                    ->whereColumn("later.{$doc}", "{$alias}.{$doc}")
                    ->whereColumn('later.id', '>', "{$alias}.id")
                    ->whereDate('later_d.date', '>=', $from)
                    ->whereDate('later_d.date', '<=', $to);
            });
        });
    }
}
