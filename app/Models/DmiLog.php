<?php

namespace App\Models;

use App\Models\Concerns\NormalizesStations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DmiLog extends Model
{
    use HasFactory, NormalizesStations;

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
        'work_group', 'defer_type', 'status_wo', 'wo_number', 'ata', 'due_date_dmi', 'picklist_number', 'aoc',
        'type', 'plan_date', 'close_date', 'highlight', 'operator',
    ];

    public function dailyJobAssignment()
    {
        return $this->belongsTo(DailyJobAssignment::class, 'dja_id');
    }

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'due_date_dmi' => 'date',
            'plan_date' => 'date',
            'close_date' => 'date',
            'is_submitted' => 'boolean',
        ];
    }

    public function getCodeOpenAttribute(): ?string
    {
        return $this->attributes['code_open'] ?? $this->hold_reason_category;
    }

    public function setCodeOpenAttribute(?string $value): void
    {
        $this->attributes['hold_reason_category'] = $value;
    }

    public function getReasonOpenAttribute(): ?string
    {
        return $this->attributes['reason_open'] ?? $this->hold_remarks;
    }

    public function setReasonOpenAttribute(?string $value): void
    {
        $this->attributes['hold_remarks'] = $value;
    }
}
