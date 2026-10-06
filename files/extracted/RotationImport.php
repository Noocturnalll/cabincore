<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RotationImport extends Model
{
    protected $guarded = [];

    protected $casts = ['operation_date' => 'date', 'stats' => 'array', 'warnings' => 'array'];

    public function aircraft(): HasMany
    {
        return $this->hasMany(AircraftRotation::class)->orderBy('rotation_no');
    }
}
