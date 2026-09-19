<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CmlLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'dja_id',
        'hold_reason_category',
        'hold_remarks',
        'evidence_path',
        'aircraft_registration',
        'station',
        'description',
        'status',
        'date',
        'operator',
        'ac_status',
        'doc_type',
        'no_doc',
    ];

    public function dailyJobAssignment()
    {
        return $this->belongsTo(DailyJobAssignment::class, 'dja_id');
    }
}
