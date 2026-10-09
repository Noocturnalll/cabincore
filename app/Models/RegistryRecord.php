<?php

namespace App\Models;

use App\Support\ExpiryStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegistryRecord extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'due_date' => 'date',
        ];
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function expiryStatus(): string
    {
        $cfg = config("registry.modules.{$this->module}");
        if (! $cfg || empty($cfg['due'])) {
            return ExpiryStatus::NONE;
        }

        return ExpiryStatus::for($this->due_date, (int) $cfg['yellow'], (int) $cfg['red']);
    }
}
