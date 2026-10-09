<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaderReportRow extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['work_date' => 'date', 'start_at' => 'datetime', 'end_at' => 'datetime', 'man_hour' => 'float'];
    }
}
