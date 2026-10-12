<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Stretcher extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function transactions(): HasMany
    {
        return $this->hasMany(StretcherTransaction::class)->orderByDesc('date')->orderByDesc('id');
    }

    public function latestTransaction(): HasOne
    {
        return $this->hasOne(StretcherTransaction::class)->latestOfMany('date');
    }

    /**
     * Recalculate and update current location/station based on the latest transaction.
     */
    public function syncCurrentStation(?string $defaultStation = 'CGK'): void
    {
        $latest = $this->transactions()->where('status', '!=', 'CANCELLED')->first();
        if ($latest) {
            $this->update([
                'current_station' => strtoupper($latest->destination_station),
                'status' => $latest->status === 'IN_TRANSIT' ? 'IN_USE' : 'AVAILABLE',
            ]);
        } else {
            $this->update([
                'current_station' => $defaultStation,
                'status' => 'AVAILABLE',
            ]);
        }
    }
}
