<?php

namespace App\Models;

use App\Support\CmlKind;
use App\Support\StationNormalizer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CmlLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'man_power', 'start_at', 'end_at', 'man_hour', 'leader_import_id',
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
        'kind',
        'no_doc',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $log) {
            // a DBI is Cabin Maintenance with its own tab; "DBI" in the station cell is a label, not a station
            $log->kind = CmlKind::detect($log->description);
            $log->station = strtoupper(trim((string) $log->station)) === 'DBI' ? null : StationNormalizer::normalize($log->station);
        });
    }

    public function dailyJobAssignment()
    {
        return $this->belongsTo(DailyJobAssignment::class, 'dja_id');
    }

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }
}
