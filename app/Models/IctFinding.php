<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
