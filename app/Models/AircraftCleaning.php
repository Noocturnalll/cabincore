<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AircraftCleaning extends Model
{
    protected $guarded = [];

    /** Cleaning work of the AIEC team: DBI (Daily Beautification Inspection) belongs to Cabin Maintenance. */
    public function scopeAiec($query)
    {
        return $query->where('type', '!=', 'DBI');
    }
}
