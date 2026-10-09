<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WoLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'man_power', 'start_at', 'end_at', 'man_hour', 'leader_import_id',
        'dja_id',
        'hold_reason_category',
        'hold_remarks',
        'evidence_path',
        'date',
        'work_group',
        'aircraft_registration',
        'wo_number',
        'wo_category',
        'description',
        'pn_picklist',
        'man_hour',
        'operator',
        'type',
        'plan_station',
        'remarks_ppc_to_lm',
        'act_station',
        'status',
        'reason_open',
        'code_open',
        'photo_evidence_path',
        'is_submitted',
        'import_source',
    ];

    public function dailyJobAssignment()
    {
        return $this->belongsTo(DailyJobAssignment::class, 'dja_id');
    }
}
