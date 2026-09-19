<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyJobAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_spreadsheet_id',
        'aircraft_registration',
        'date',
        'task_id',
        'job_type',
        'description',
        'station',
    ];

    public function cmlLogs()
    {
        return $this->hasMany(CmlLog::class, 'dja_id');
    }

    public function nsrdiLogs()
    {
        return $this->hasMany(NsrdiLog::class, 'dja_id');
    }

    public function dmiLogs()
    {
        return $this->hasMany(DmiLog::class, 'dja_id');
    }

    public function woLogs()
    {
        return $this->hasMany(WoLog::class, 'dja_id');
    }
}
