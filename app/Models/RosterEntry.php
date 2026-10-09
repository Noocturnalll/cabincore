<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RosterEntry extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['work_date' => 'date:Y-m-d'];
    }
}
