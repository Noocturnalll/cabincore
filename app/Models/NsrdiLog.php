<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NsrdiLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'dja_id',
        'hold_reason_category',
        'hold_remarks',
        'evidence_path',
        'refresh_date',
        'work_group',
        'aircraft_registration',
        'nsrdi_number',
        'description',
        'category',
        'report_date',
        'due_date',
        'part_number',
        'part_description',
        'defer',
        'aoc',
        'type',
        'plan_station',
        'plan_date',
        'remarks',
        'status',
        'close_date',
        'act_station',
        'reason_open',
        'code_open',
        'photo_evidence_path',
    ];

    public function dailyJobAssignment()
    {
        return $this->belongsTo(DailyJobAssignment::class, 'dja_id');
    }
}
