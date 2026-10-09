<?php

namespace App\Livewire\Traits;

use App\Services\Dja\DjaPersister;
use Illuminate\Database\Eloquent\Builder;

/**
 * The WO / DMI / NSRDI pages default to the active operational day. A log of an earlier day that is still Open and
 * has not been archived (it lacks a code reason / remarks) must not vanish from the page at the 18:00 rollover:
 * it stays listed until it is closed or its reason is complete.
 */
trait ShowsCarryOver
{
    /** Add "unfinished logs of earlier days" as an OR branch of the given group. */
    protected function addCarryOver(Builder $group, string $activeDate, string $unplannedDateSql): void
    {
        $group->orWhere(function (Builder $c) use ($activeDate, $unplannedDateSql) {
            $c->where('is_submitted', false)
                ->where('status', '!=', 'Closed')
                ->where(function (Builder $d) use ($activeDate, $unplannedDateSql) {
                    $d->whereHas('dailyJobAssignment', fn ($x) => $x->whereDate('date', '<', $activeDate))
                        ->orWhere(fn (Builder $u) => $u->whereNull('dja_id')->whereRaw("{$unplannedDateSql} < ?", [$activeDate]));
                });
        });
    }

    protected function carryOverCount(string $model, string $unplannedDateSql): int
    {
        $query = $model::query();
        $query->where(fn (Builder $q) => $this->addCarryOver($q, DjaPersister::activeDate(), $unplannedDateSql));

        return $query->count();
    }
}
