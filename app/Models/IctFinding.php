<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IctFinding extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'no_finding',
        'operator',
        'aircraft_registration',
        'defect_description',
        'remarks',
        'status',
    ];
}
