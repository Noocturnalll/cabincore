<?php

namespace App\Models\Concerns;

use App\Support\StationNormalizer;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * Whatever writes a task (DJA sync, Daily Report import, Leader report, a form) stores a station code or nothing,
 * never "BANTEK PNK", a typo or a formula that arrived as text.
 */
trait NormalizesStations
{
    protected function actStation(): Attribute
    {
        return Attribute::set(fn ($value) => StationNormalizer::normalize($value));
    }

    protected function planStation(): Attribute
    {
        return Attribute::set(fn ($value) => StationNormalizer::normalize($value));
    }
}
