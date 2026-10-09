<?php

namespace App\Models;

use App\Support\ExpiryStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'join_date' => 'date',
            'contract_start' => 'date',
            'contract_end' => 'date',
            'passport_expiry' => 'date',
        ];
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /** Permanent staff (PKWTT) have no contract end date to watch. */
    public function contractStatus(): string
    {
        if ($this->contract_type === 'PKWTT') {
            return ExpiryStatus::NONE;
        }

        return ExpiryStatus::for($this->contract_end, config('hr.contract.yellow'), config('hr.contract.red'));
    }

    public function passportStatus(): string
    {
        return ExpiryStatus::for($this->passport_expiry, config('hr.passport.yellow'), config('hr.passport.red'));
    }
}
