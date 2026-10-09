<?php

namespace App\Services\Dja;

use App\Models\DmiLog;
use App\Models\NsrdiLog;
use App\Models\WoLog;
use Illuminate\Database\Eloquent\Builder;

/**
 * Moves finished WO / DMI / NSRDI logs into the Daily Report (the data bank) by flagging them is_submitted.
 *
 * Operational day: rolls over at 18:00 (DjaPersister::activeDate()). A log dated before the active date is past
 * its cutoff and is archived when it is either
 *   - Closed, or
 *   - Open with BOTH a reason code and remarks (a reason may come from CBM or already be in the planner sheet).
 * Open logs without a complete reason are held back and counted, so they can be chased instead of silently
 * missing from the bank. CML has no flag: the Daily Report lists CML rows older than the active date directly.
 */
class DailyReportArchiver
{
    /** model => [date SQL, code columns, remarks columns, label] */
    private const SOURCES = [
        'wo' => [WoLog::class, 'DATE(date)', ['hold_reason_category', 'code_open'], ['hold_remarks', 'reason_open'], 'WO'],
        'dmi' => [DmiLog::class, 'DATE(date)', ['hold_reason_category'], ['hold_remarks', 'remarks'], 'DMI'],
        'nsrdi' => [NsrdiLog::class, 'DATE(COALESCE(plan_date, report_date))', ['hold_reason_category', 'code_open'], ['hold_remarks', 'reason_open'], 'NSRDI'],
    ];

    public function cutoff(): string
    {
        return DjaPersister::activeDate();
    }

    /**
     * Flag every archivable log. Idempotent.
     *
     * @return array<string, int> kind => number of logs moved to the Daily Report
     */
    public function run(): array
    {
        $moved = [];
        foreach (self::SOURCES as $kind => [$model, $dateSql, $codes, $remarks]) {
            $moved[$kind] = $this->pastCutoff($model, $dateSql)
                ->where(fn (Builder $q) => $q
                    ->where('status', 'Closed')
                    ->orWhere(fn (Builder $open) => $this->hasAny($open, $codes)->where(fn (Builder $r) => $this->hasAny($r, $remarks))))
                ->update(['is_submitted' => true]);
        }

        return $moved;
    }

    /**
     * Logs past the cutoff that cannot be archived yet (Open without a complete reason).
     *
     * @return array<string, int> kind => count
     */
    public function held(): array
    {
        $held = [];
        foreach (self::SOURCES as $kind => [$model, $dateSql, $codes, $remarks]) {
            $held[$kind] = $this->pastCutoff($model, $dateSql)
                ->where('status', '!=', 'Closed')
                ->where(fn (Builder $q) => $q
                    ->where(fn (Builder $c) => $this->blankAll($c, $codes))
                    ->orWhere(fn (Builder $r) => $this->blankAll($r, $remarks)))
                ->count();
        }

        return $held;
    }

    private function pastCutoff(string $model, string $dateSql): Builder
    {
        return $model::query()->where('is_submitted', false)->whereRaw("{$dateSql} < ?", [$this->cutoff()]);
    }

    /** At least one of the columns holds a value. */
    private function hasAny(Builder $query, array $columns): Builder
    {
        return $query->where(function (Builder $q) use ($columns) {
            foreach ($columns as $column) {
                $q->orWhere(fn (Builder $c) => $c->whereNotNull($column)->where($column, '!=', ''));
            }
        });
    }

    /** All of the columns are empty. */
    private function blankAll(Builder $query, array $columns): Builder
    {
        foreach ($columns as $column) {
            $query->where(fn (Builder $c) => $c->whereNull($column)->orWhere($column, ''));
        }

        return $query;
    }
}
