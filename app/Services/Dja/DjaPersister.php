<?php

namespace App\Services\Dja;

use App\Models\DailyJobAssignment;
use App\Models\DmiLog;
use App\Models\NsrdiLog;
use App\Models\WoLog;
use Illuminate\Support\Facades\DB;

/**
 * Writes an accepted planner row to daily_job_assignments and the matching WO / DMI / NSRDI log.
 *
 * On re-sync only fields the planner owns are refreshed. Anything a user entered in CBM (status, reason code,
 * remarks, actual station, evidence, submission) is never overwritten by a later sync.
 */
class DjaPersister
{
    /** Log columns that mirror the planner sheet and may be refreshed by a later sync. */
    private const PLANNER_OWNED = [
        'wo' => ['work_group', 'aircraft_registration', 'wo_category', 'description', 'pn_picklist', 'man_hour', 'operator', 'type', 'plan_station', 'remarks_ppc_to_lm'],
        'dmi' => ['aircraft_registration', 'description', 'pn_required', 'dmi_category', 'plan_station', 'category'],
        'nsrdi' => ['work_group', 'aircraft_registration', 'description', 'category', 'report_date', 'due_date', 'part_number', 'part_description', 'defer', 'aoc', 'type', 'plan_station'],
    ];

    private const LOG_MODEL = ['wo' => WoLog::class, 'dmi' => DmiLog::class, 'nsrdi' => NsrdiLog::class];

    public static function activeDate(): string
    {
        return now()->hour >= 18 ? now()->format('Y-m-d') : now()->subDay()->format('Y-m-d');
    }

    /** Stable task id for rows without a number, so repeated syncs do not create duplicates. */
    public static function taskIdFor(array $mapped): string
    {
        if (! empty($mapped['task_id'])) {
            return $mapped['task_id'];
        }

        return 'AUTO-'.strtoupper(substr(md5($mapped['tab'].'|'.$mapped['ac_reg'].'|'.$mapped['description']), 0, 12));
    }

    /**
     * @param  array<string, mixed>  $mapped  output of DjaRowMapper::map()
     * @return array{task_id: string, result: 'created'|'updated'|'unchanged'}
     */
    public function store(array $mapped, ?string $spreadsheetId): array
    {
        return DB::transaction(function () use ($mapped, $spreadsheetId) {
            $kind = $mapped['kind'];
            $taskId = self::taskIdFor($mapped);
            $date = $mapped['date'] ?? self::activeDate();

            $dja = DailyJobAssignment::firstOrNew(['task_id' => $taskId, 'date' => $date]);
            $wasNew = ! $dja->exists;

            $dja->fill([
                'aircraft_registration' => $mapped['ac_reg'] ?? '',
                'job_type' => $mapped['job_type'],
                'description' => $mapped['description'],
                'station' => $mapped['station'] ?: config('dja.default_station'),
            ]);
            if ($spreadsheetId) {
                $dja->source_spreadsheet_id = $spreadsheetId;
            }
            $dja->save();

            $logClass = self::LOG_MODEL[$kind];
            $attrs = $mapped['log'];
            $dateKey = $kind === 'nsrdi' ? 'plan_date' : 'date';
            $attrs[$dateKey] = $attrs[$dateKey] ?? $date;
            if ($kind === 'nsrdi') {
                $attrs['report_date'] = $attrs['report_date'] ?? $date;
            }

            $log = $logClass::where('dja_id', $dja->id)->first();

            if (! $log) {
                $logClass::create(['dja_id' => $dja->id] + $attrs);

                return ['task_id' => $taskId, 'result' => 'created'];
            }

            if ($log->is_submitted) {
                return ['task_id' => $taskId, 'result' => 'unchanged'];
            }

            $log->fill(array_intersect_key($attrs, array_flip(self::PLANNER_OWNED[$kind])));
            $changed = $log->isDirty();
            $log->save();

            return ['task_id' => $taskId, 'result' => $changed || $wasNew ? 'updated' : 'unchanged'];
        });
    }
}
