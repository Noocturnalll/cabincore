<?php

namespace App\Models\Ims;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AircraftType extends Model
{
    use SoftDeletes;
    
    protected $table = 'ims_aircraft_types';
    protected $guarded = [];
}