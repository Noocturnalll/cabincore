<?php

namespace App\Models;

use App\Support\CmlKind;
use App\Support\ManHours;
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
        'ac_type', 'shift', 'task_type', 'start_perform', 'finish_perform',
        'mp1', 'mp2', 'mp3', 'mp4', 'mp5', 'individual_mhrs',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $log) {
            // a DBI is Cabin Maintenance with its own tab; "DBI" in the station cell is a label, not a station
            $log->kind = CmlKind::detect($log->description, $log->task_type);

            // total MP from the named technicians; man hours from the perform times (one person's hours x total MP)
            $named = collect([$log->mp1, $log->mp2, $log->mp3, $log->mp4, $log->mp5])->filter(fn ($n) => trim((string) $n) !== '')->count();
            if ($named > 0 && ! $log->man_power) {
                $log->man_power = $named;
            }
            $hours = ManHours::duration($log->start_perform, $log->finish_perform);
            if ($hours !== null) {
                $log->individual_mhrs = $hours;
                if ($log->man_power) {
                    $log->man_hour = round($hours * $log->man_power, 2);
                }
            }
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
