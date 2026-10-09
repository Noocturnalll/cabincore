<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DmiLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'man_power', 'start_at', 'end_at', 'man_hour', 'leader_import_id',
        'dja_id',
        'hold_reason_category',
        'hold_remarks',
        'evidence_path',
        'date',
        'aircraft_registration',
        'description',
        'pn_required',
        'dmi_number',
        'dmi_category',
        'plan_station',
        'category',
        'act_station',
        'status',
        'remarks',
        'photo_evidence_path',
        'is_submitted',
        'import_source',
    ];

    public function dailyJobAssignment()
    {
        return $this->belongsTo(DailyJobAssignment::class, 'dja_id');
    }
}
