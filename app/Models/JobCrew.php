<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobCrew extends Model
{
    protected $table = 'job_crew';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['work_date' => 'date:Y-m-d', 'man_hour' => 'float'];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
