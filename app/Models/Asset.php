<?php

namespace App\Models;

use App\Services\Master\MasterSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['acquired_at' => 'date'];
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(AssetAssignment::class);
    }

    /** Units currently out with somebody. */
    public function unitsInUse(): int
    {
        return (int) $this->assignments()->whereNull('returned_at')->sum('qty');
    }

    /** Units that can still be handed out: zero when the condition is not lendable (damaged, lost ...). */
    public function unitsAvailable(?int $inUse = null): int
    {
        if (! in_array($this->condition, app(MasterSettings::class)->lendableConditions(), true)) {
            return 0;
        }

        return max(0, $this->qty_total - ($inUse ?? $this->unitsInUse()));
    }
}
