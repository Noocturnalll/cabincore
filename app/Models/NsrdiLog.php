<?php

namespace App\Models;

use App\Models\Concerns\NormalizesStations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NsrdiLog extends Model
{
    use HasFactory, NormalizesStations;

    protected $fillable = [
        'man_power', 'start_at', 'end_at', 'man_hour', 'leader_import_id',
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
        'is_submitted',
        'import_source',
    ];

    protected static function booted(): void
    {
        static::saving(function (NsrdiLog $log) {
            $log->category = static::determineCategory(
                $log->category,
                $log->description,
                $log->work_group
            );
        });

        static::saved(function (NsrdiLog $log) {
            if (strtolower((string) $log->status) === 'closed') {
                $num = strtoupper(trim((string) $log->nsrdi_number));
                if ($num !== '') {
                    NsrdiOverdue::where(function ($q) use ($num) {
                        $q->whereRaw('UPPER(TRIM(nsrdi_number)) = ?', [$num])
                            ->orWhereRaw('UPPER(TRIM(mddr)) = ?', [$num]);
                    })
                        ->whereRaw('LOWER(status) != ?', ['closed'])
                        ->update([
                            'status' => 'Closed',
                            'closed_at' => now(),
                        ]);
                }
            }
        });
    }

    /**
     * Determines whether an NSRDI record belongs to Aircraft Painting or Cabin Maintenance (CBM).
     */
    public static function determineCategory(?string $category, ?string $description = null, ?string $workGroup = null): string
    {
        $cat = strtoupper(trim((string) $category));

        if (
            in_array($cat, ['PAINTING', 'AIRCRAFT PAINTING', 'PAINT', 'PPO', 'PENGECATAN', 'CAT PAINTING'], true) ||
            str_contains($cat, 'PAINT') ||
            str_contains($cat, 'PENGECATAN')
        ) {
            return 'PAINTING';
        }

        $text = strtolower(trim(($category ?? '').' '.($description ?? '').' '.($workGroup ?? '')));

        if (
            str_contains($text, 'paint') ||
            str_contains($text, 'pengecatan') ||
            str_contains($text, 'ppo') ||
            str_contains($text, 'repaint') ||
            preg_match('/\bcat\s+painting\b/i', $text) ||
            preg_match('/\baircraft\s+painting\b/i', $text)
        ) {
            return 'PAINTING';
        }

        return 'CBM';
    }

    public function dailyJobAssignment()
    {
        return $this->belongsTo(DailyJobAssignment::class, 'dja_id');
    }

    protected function casts(): array
    {
        return [
            'refresh_date' => 'date',
            'report_date' => 'date',
            'due_date' => 'date',
            'plan_date' => 'date',
            'close_date' => 'date',
            'is_submitted' => 'boolean',
        ];
    }
}
