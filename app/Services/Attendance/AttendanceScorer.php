<?php

namespace App\Services\Attendance;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\RosterEntry;
use App\Services\Master\MasterSettings;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Discipline per employee for a period (a month up to a year).
 *
 * Sources, in this order of trust:
 *  1. imported attendance (clock in / out, status) for that person and day
 *  2. the roster: a day coded S (sick) or C (leave) counts as sick / leave even without an import
 * A person with no imported attendance at all in the period is assumed present on their roster days ("assumed"),
 * so the page is useful before the first presensi file arrives; once data exists, a roster day with no record is
 * counted as "tidak tercatat" instead.
 *
 * Thresholds come from Data Master > Aturan Disiplin and are per month: a year multiplies them by 12.
 */
class AttendanceScorer
{
    public const FLAGS = [
        'rajin' => 'Rajin',
        'sering_terlambat' => 'Sering terlambat',
        'banyak_sakit' => 'Terlalu banyak sakit',
        'banyak_cuti' => 'Terlalu banyak cuti',
        'alpa' => 'Alpa',
    ];

    /**
     * @param  array<int, string>|null  $stations
     * @return array{rows: Collection<int, array<string, mixed>>, summary: array<string, mixed>, months: int}
     */
    public function build(CarbonInterface $from, CarbonInterface $to, ?array $stations = null, ?string $team = null): array
    {
        $master = app(MasterSettings::class);
        $groups = $master->attendanceGroups();
        $months = max(1, (int) round($from->diffInDays($to) / 30.4));
        $limits = [
            'late' => $master->rule('LATE_MAX_PER_MONTH', 3) * $months,
            'sick' => $master->rule('SICK_MAX_PER_MONTH', 3) * $months,
            'leave' => $master->rule('LEAVE_MAX_PER_MONTH', 5) * $months,
            'diligent' => $master->rule('DILIGENT_MIN_PERCENT', 97),
        ];

        $roster = RosterEntry::query()
            ->whereDate('work_date', '>=', $from->toDateString())->whereDate('work_date', '<=', $to->toDateString())
            ->when($stations, fn ($q) => $q->whereIn('station', $stations))
            ->when($team, fn ($q) => $q->where('team', $team))
            ->get(['employee_id', 'employee_name', 'station', 'team', 'work_date', 'shift_code', 'shift']);

        $attendance = AttendanceRecord::query()
            ->whereDate('work_date', '>=', $from->toDateString())->whereDate('work_date', '<=', $to->toDateString())
            ->when($stations, fn ($q) => $q->whereIn('station', $stations))
            ->get()->groupBy('employee_nik');

        $people = $roster->groupBy('employee_id');
        // man hours each person worked on logged jobs (the job crew), a second view of who is productive
        $hours = DB::table('job_crew')
            ->whereDate('work_date', '>=', $from->toDateString())->whereDate('work_date', '<=', $to->toDateString())
            ->whereIn('employee_ref', $people->keys()->map(fn ($k) => strtoupper((string) $k))->all())
            ->selectRaw('employee_ref, COALESCE(SUM(man_hour),0) as hours')->groupBy('employee_ref')->pluck('hours', 'employee_ref');
        $nameOf = Employee::whereIn('nik', $people->keys())->pluck('name', 'nik');

        $rows = $people->map(function (Collection $days, string $nik) use ($attendance, $groups, $limits, $nameOf, $hours) {
            $records = ($attendance[$nik] ?? collect())->keyBy(fn ($r) => $r->work_date->toDateString());
            $assumed = $records->isEmpty();

            $r = ['working' => 0, 'present' => 0, 'late' => 0, 'late_minutes' => 0, 'sick' => 0, 'leave' => 0, 'permit' => 0, 'absent' => 0, 'training' => 0, 'unrecorded' => 0];
            foreach ($days as $day) {
                $date = $day->work_date->toDateString();
                $rec = $records[$date] ?? null;
                $group = $rec?->status_group ?? ($groups[strtoupper($day->shift_code)] ?? null);

                if ($rec === null && $day->shift === null && $group === null) {
                    continue;   // OFF / unknown code and no attendance: not a working day
                }
                if (in_array($group, ['libur'], true) && $rec === null) {
                    continue;
                }
                if ($group === 'training') {
                    $r['training']++;

                    continue;
                }

                $r['working']++;
                switch (true) {
                    case $group === 'sakit': $r['sick']++;
                        break;
                    case $group === 'cuti': $r['leave']++;
                        break;
                    case $group === 'izin': $r['permit']++;
                        break;
                    case $group === 'alpa': $r['absent']++;
                        break;
                    case $rec !== null && $group === 'hadir':
                        $r['present']++;
                        if ($rec->late_minutes > 0) {
                            $r['late']++;
                            $r['late_minutes'] += $rec->late_minutes;
                        }
                        break;
                    case $assumed && $day->shift !== null: $r['present']++;
                        break;
                    default: $r['unrecorded']++;
                }
            }
            // attendance on days outside the roster (overtime, swaps) still counts as presence
            foreach ($records as $date => $rec) {
                if ($days->contains(fn ($d) => $d->work_date->toDateString() === $date)) {
                    continue;
                }
                if ($rec->status_group === 'hadir') {
                    $r['working']++;
                    $r['present']++;
                    if ($rec->late_minutes > 0) {
                        $r['late']++;
                        $r['late_minutes'] += $rec->late_minutes;
                    }
                }
            }

            $onTime = $r['working'] > 0 ? round(($r['present'] - $r['late']) / $r['working'] * 100, 1) : null;
            $first = $days->first();

            $flags = [];
            if ($r['late'] > $limits['late']) {
                $flags[] = 'sering_terlambat';
            }
            if ($r['sick'] > $limits['sick']) {
                $flags[] = 'banyak_sakit';
            }
            if ($r['leave'] > $limits['leave']) {
                $flags[] = 'banyak_cuti';
            }
            if ($r['absent'] > 0) {
                $flags[] = 'alpa';
            }
            if (! $flags && $onTime !== null && $r['working'] >= 5 && $onTime >= $limits['diligent'] && ! $assumed) {
                $flags[] = 'rajin';
            }

            return $r + [
                'nik' => $nik, 'name' => $nameOf[$nik] ?? $first->employee_name ?? $nik,
                'station' => $first->station, 'team' => $first->team,
                'on_time' => $onTime, 'flags' => $flags, 'assumed' => $assumed,
                'man_hours' => round((float) ($hours[strtoupper($nik)] ?? 0), 1),
            ];
        })->filter(fn ($r) => $r['working'] > 0)->values();

        return ['rows' => $rows, 'summary' => $this->summary($rows), 'months' => $months];
    }

    private function summary(Collection $rows): array
    {
        $counted = $rows->whereNotNull('on_time');
        $flagCount = fn (string $f) => $rows->filter(fn ($r) => in_array($f, $r['flags'], true))->count();

        return [
            'people' => $rows->count(),
            'on_time' => $counted->isEmpty() ? null : round($counted->avg('on_time'), 1),
            'present' => $rows->sum('present'),
            'working' => $rows->sum('working'),
            'late' => $rows->sum('late'),
            'sick' => $rows->sum('sick'),
            'leave' => $rows->sum('leave'),
            'absent' => $rows->sum('absent'),
            'assumed' => $rows->where('assumed', true)->count(),
            'flags' => array_combine(array_keys(self::FLAGS), array_map($flagCount, array_keys(self::FLAGS))),
        ];
    }
}
