<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AircraftRotation extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['notes' => 'array', 'remarks' => 'array'];

    public function import(): BelongsTo
    {
        return $this->belongsTo(RotationImport::class, 'rotation_import_id');
    }

    public function legs(): HasMany
    {
        return $this->hasMany(RotationLeg::class)->orderBy('seq');
    }
}
