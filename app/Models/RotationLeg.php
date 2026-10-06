<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RotationLeg extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['is_charter' => 'bool', 'is_revised' => 'bool', 'flags' => 'array'];
}
