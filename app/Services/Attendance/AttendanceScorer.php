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
     * @return array{
     *     rows: Collection<int, array<string, mixed>>,
     *     summary: array<string, mixed>,
     *     months: int,
     *     sakit_list: Collection<int, array<string, mixed>>,
     *     cuti_list: Collection<int, array<string, mixed>>,
     *     alpa_list: Collection<int, array<string, mixed>>,
     *     resign_list: Collection<int, array<string, mixed>>,
     *     weekly_breakdown: array<int, array<string, mixed>>,
     *     limits: array<string, int|float>
     * }
     */
    public function build(CarbonInterface $from, CarbonInterface $to, ?array $stations = null, ?string $team = null): array
    {
        $master = app(MasterSettings::class);
        $groups = $master->attendanceGroups();
        $totalDays = (int) $from->diffInDays($to) + 1;
        $months = max(1, (int) round($from->diffInDays($to) / 30.4));

        if ($totalDays <= 7) {
            $limits = [
                'late' => max(1, (int) round($master->rule('LATE_MAX_PER_MONTH', 3) * 0.25)),
                'sick' => max(1, (int) round($master->rule('SICK_MAX_PER_MONTH', 3) * 0.25)),
                'leave' => max(1, (int) round($master->rule('LEAVE_MAX_PER_MONTH', 5) * 0.25)),
                'diligent' => $master->rule('DILIGENT_MIN_PERCENT', 97),
            ];
        } else {
            $limits = [
                'late' => $master->rule('LATE_MAX_PER_MONTH', 3) * $months,
                'sick' => $master->rule('SICK_MAX_PER_MONTH', 3) * $months,
                'leave' => $master->rule('LEAVE_MAX_PER_MONTH', 5) * $months,
                'diligent' => $master->rule('DILIGENT_MIN_PERCENT', 97),
            ];
        }

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

        // man hours each person worked on logged jobs (the job crew)
        $hours = DB::table('job_crew')
            ->whereDate('work_date', '>=', $from->toDateString())->whereDate('work_date', '<=', $to->toDateString())
            ->whereIn('employee_ref', $people->keys()->map(fn ($k) => strtoupper((string) $k))->all())
            ->selectRaw('employee_ref, COALESCE(SUM(man_hour),0) as hours')
            ->groupBy('employee_ref')
            ->pluck('hours', 'employee_ref');

        // man hours per date for weekly breakdown
        $hoursByDate = DB::table('job_crew')
            ->whereDate('work_date', '>=', $from->toDateString())->whereDate('work_date', '<=', $to->toDateString())
            ->when($stations, fn ($q) => $q->whereIn('station', $stations))
            ->selectRaw('DATE(work_date) as dt, COALESCE(SUM(man_hour),0) as hours')
            ->groupBy('dt')
            ->pluck('hours', 'dt');

        $nameOf = Employee::whereIn('nik', $people->keys())->pluck('name', 'nik');

        $sakitEvents = [];
        $cutiEvents = [];
        $alpaEvents = [];
        $dailyBreakdown = [];

        $rows = $people->map(function (Collection $days, string $nik) use (
            $attendance, $groups, $limits, $nameOf, $hours,
            &$sakitEvents, &$cutiEvents, &$alpaEvents, &$dailyBreakdown
        ) {
            $records = ($attendance[$nik] ?? collect())->keyBy(fn ($r) => $r->work_date->toDateString());
            $assumed = $records->isEmpty();

            $r = [
                'working' => 0, 'present' => 0, 'late' => 0, 'late_minutes' => 0,
                'sick' => 0, 'leave' => 0, 'permit' => 0, 'absent' => 0,
                'training' => 0, 'unrecorded' => 0,
            ];

            foreach ($days as $day) {
                $date = $day->work_date->toDateString();
                $rec = $records[$date] ?? null;
                $group = $rec?->status_group ?? ($groups[strtoupper((string) $day->shift_code)] ?? null);
                $note = $rec?->notes;

                if (! isset($dailyBreakdown[$date])) {
                    $dailyBreakdown[$date] = [
                        'working' => 0, 'present' => 0, 'late' => 0,
                        'sick' => 0, 'leave' => 0, 'absent' => 0,
                    ];
                }

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
                $dailyBreakdown[$date]['working']++;

                switch (true) {
                    case $group === 'sakit':
                        $r['sick']++;
                        $dailyBreakdown[$date]['sick']++;
                        $sakitEvents[$nik][] = ['date' => $date, 'note' => $note];
                        break;
                    case $group === 'cuti':
                        $r['leave']++;
                        $dailyBreakdown[$date]['leave']++;
                        $cutiEvents[$nik][] = ['date' => $date, 'note' => $note];
                        break;
                    case $group === 'izin':
                        $r['permit']++;
                        break;
                    case $group === 'alpa':
                        $r['absent']++;
                        $dailyBreakdown[$date]['absent']++;
                        $alpaEvents[$nik][] = ['date' => $date, 'note' => $note];
                        break;
                    case $rec !== null && $group === 'hadir':
                        $r['present']++;
                        $dailyBreakdown[$date]['present']++;
                        if ($rec->late_minutes > 0) {
                            $r['late']++;
                            $r['late_minutes'] += $rec->late_minutes;
                            $dailyBreakdown[$date]['late']++;
                        }
                        break;
                    case $assumed && $day->shift !== null:
                        $r['present']++;
                        $dailyBreakdown[$date]['present']++;
                        break;
                    default:
                        $r['unrecorded']++;
                }
            }

            // attendance on days outside the roster (overtime, swaps) still counts as presence
            foreach ($records as $date => $rec) {
                if ($days->contains(fn ($d) => $d->work_date->toDateString() === $date)) {
                    continue;
                }
                if (! isset($dailyBreakdown[$date])) {
                    $dailyBreakdown[$date] = [
                        'working' => 0, 'present' => 0, 'late' => 0,
                        'sick' => 0, 'leave' => 0, 'absent' => 0,
                    ];
                }

                if ($rec->status_group === 'hadir') {
                    $r['working']++;
                    $r['present']++;
                    $dailyBreakdown[$date]['working']++;
                    $dailyBreakdown[$date]['present']++;
                    if ($rec->late_minutes > 0) {
                        $r['late']++;
                        $r['late_minutes'] += $rec->late_minutes;
                        $dailyBreakdown[$date]['late']++;
                    }
                } elseif ($rec->status_group === 'sakit') {
                    $sakitEvents[$nik][] = ['date' => $date, 'note' => $rec->notes];
                } elseif ($rec->status_group === 'cuti') {
                    $cutiEvents[$nik][] = ['date' => $date, 'note' => $rec->notes];
                } elseif ($rec->status_group === 'alpa') {
                    $alpaEvents[$nik][] = ['date' => $date, 'note' => $rec->notes];
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
                'nik' => $nik,
                'name' => $nameOf[$nik] ?? $first->employee_name ?? $nik,
                'station' => $first->station,
                'team' => $first->team,
                'on_time' => $onTime,
                'flags' => $flags,
                'assumed' => $assumed,
                'man_hours' => round((float) ($hours[strtoupper($nik)] ?? 0), 1),
            ];
        })->filter(fn ($r) => $r['working'] > 0)->values();

        // Build structured Sakit, Cuti, Alpa lists
        $sakitList = $this->buildStatusList($sakitEvents, $people, $nameOf, $limits['sick']);
        $cutiList = $this->buildStatusList($cutiEvents, $people, $nameOf, $limits['leave']);
        $alpaList = $this->buildStatusList($alpaEvents, $people, $nameOf, 0);

        // Build Resign list from Employee records
        $resignList = $this->buildResignList($from, $to, $stations, $team);

        // Build Weekly / Periodic Breakdown
        $weeklyBreakdown = $this->buildWeeklyBreakdown($from, $to, $dailyBreakdown, $hoursByDate);

        return [
            'rows' => $rows,
            'summary' => $this->summary($rows, $sakitList, $cutiList, $alpaList, $resignList),
            'months' => $months,
            'sakit_list' => $sakitList,
            'cuti_list' => $cutiList,
            'alpa_list' => $alpaList,
            'resign_list' => $resignList,
            'weekly_breakdown' => $weeklyBreakdown,
            'limits' => $limits,
        ];
    }

    /**
     * @param  array<string, array<int, array{date: string, note: ?string}>>  $events
     * @param  Collection<string, Collection<int, RosterEntry>>  $people
     * @param  Collection<string, string>  $nameOf
     * @return Collection<int, array<string, mixed>>
     */
    private function buildStatusList(array $events, Collection $people, Collection $nameOf, int|float $limit): Collection
    {
        return collect($events)->map(function (array $items, string $nik) use ($people, $nameOf, $limit) {
            $first = $people[$nik]?->first();
            $dates = collect($items)->pluck('date')->unique()->sort()->values()->all();
            $notes = collect($items)->pluck('note')->filter()->unique()->values()->all();
            $count = count($dates);

            return [
                'nik' => $nik,
                'name' => $nameOf[$nik] ?? $first?->employee_name ?? $nik,
                'station' => $first?->station ?? '–',
                'team' => $first?->team ?? '–',
                'count' => $count,
                'dates' => $dates,
                'notes' => $notes,
                'exceeded_limit' => $limit > 0 && $count > $limit,
            ];
        })->sortByDesc('count')->values();
    }

    /**
     * @param  array<int, string>|null  $stations
     * @return Collection<int, array<string, mixed>>
     */
    private function buildResignList(CarbonInterface $from, CarbonInterface $to, ?array $stations = null, ?string $team = null): Collection
    {
        $resigned = Employee::query()
            ->with('division')
            ->where(function ($q) {
                $q->where('status', 'Resign')
                    ->orWhereNotNull('resign_date');
            })
            ->when($stations, fn ($q) => $q->whereIn('station', $stations))
            ->when($team, fn ($q) => $q->whereHas('division', fn ($d) => $d->where('name', $team)))
            ->orderByDesc('resign_date')
            ->orderBy('name')
            ->get();

        return $resigned->map(function (Employee $e) use ($from, $to) {
            $isPeriod = $e->resign_date && $e->resign_date->between($from, $to);
            $tenure = null;
            if ($e->join_date && $e->resign_date) {
                $diff = $e->join_date->diff($e->resign_date);
                $parts = [];
                if ($diff->y > 0) {
                    $parts[] = "{$diff->y} thn";
                }
                if ($diff->m > 0) {
                    $parts[] = "{$diff->m} bln";
                }
                $tenure = implode(' ', $parts) ?: "{$diff->d} hari";
            }

            return [
                'id' => $e->id,
                'nik' => $e->nik,
                'name' => $e->name,
                'station' => $e->station ?? '–',
                'job_title' => $e->job_title ?? '–',
                'department' => $e->department ?? ($e->division?->name ?? '–'),
                'join_date' => $e->join_date?->format('d M Y'),
                'resign_date' => $e->resign_date?->format('d M Y'),
                'resign_reason' => $e->resign_reason ?: '–',
                'notes' => $e->notes,
                'tenure' => $tenure ?? '–',
                'is_period_resign' => (bool) $isPeriod,
            ];
        })->sortByDesc('is_period_resign')->values();
    }

    /**
     * @param  array<string, array{working: int, present: int, late: int, sick: int, leave: int, absent: int}>  $dailyBreakdown
     * @param  Collection<string, float|int>  $hoursByDate
     * @return array<int, array<string, mixed>>
     */
    private function buildWeeklyBreakdown(CarbonInterface $from, CarbonInterface $to, array $dailyBreakdown, Collection $hoursByDate): array
    {
        $totalDays = (int) $from->diffInDays($to) + 1;
        $items = [];

        if ($totalDays <= 7) {
            // Day by day breakdown for weekly view
            $cur = $from->copy();
            while ($cur->lte($to)) {
                $dt = $cur->toDateString();
                $st = $dailyBreakdown[$dt] ?? ['working' => 0, 'present' => 0, 'late' => 0, 'sick' => 0, 'leave' => 0, 'absent' => 0];
                $working = $st['working'];
                $present = $st['present'];
                $late = $st['late'];

                $items[] = [
                    'key' => $dt,
                    'date' => $dt,
                    'label' => $cur->translatedFormat('l, d M'),
                    'start_date' => $dt,
                    'end_date' => $dt,
                    'working' => $working,
                    'present' => $present,
                    'late' => $late,
                    'sick' => $st['sick'],
                    'leave' => $st['leave'],
                    'absent' => $st['absent'],
                    'attendance_rate' => $working > 0 ? round($present / $working * 100, 1) : null,
                    'on_time_rate' => $working > 0 ? round(max(0, $present - $late) / $working * 100, 1) : null,
                    'man_hours' => round((float) ($hoursByDate[$dt] ?? 0), 1),
                ];
                $cur->addDay();
            }

            return $items;
        }

        if ($totalDays <= 35) {
            // W1..W5 week windows for monthly view
            $daysInMonth = (int) $from->copy()->endOfMonth()->day;
            $windows = [
                ['w' => 'W1', 's' => 1, 'e' => 7],
                ['w' => 'W2', 's' => 8, 'e' => 14],
                ['w' => 'W3', 's' => 15, 'e' => 21],
                ['w' => 'W4', 's' => 22, 'e' => 28],
            ];
            if ($daysInMonth > 28) {
                $windows[] = ['w' => 'W5', 's' => 29, 'e' => $daysInMonth];
            }

            foreach ($windows as $win) {
                $sDate = $from->copy()->day(min($win['s'], $daysInMonth));
                $eDate = $from->copy()->day(min($win['e'], $daysInMonth));

                if ($sDate->gt($to) || $eDate->lt($from)) {
                    continue;
                }

                $wStart = $sDate->max($from);
                $wEnd = $eDate->min($to);

                $wWorking = 0;
                $wPresent = 0;
                $wLate = 0;
                $wSick = 0;
                $wLeave = 0;
                $wAbsent = 0;
                $wHours = 0.0;

                $cur = $wStart->copy();
                while ($cur->lte($wEnd)) {
                    $dt = $cur->toDateString();
                    $st = $dailyBreakdown[$dt] ?? ['working' => 0, 'present' => 0, 'late' => 0, 'sick' => 0, 'leave' => 0, 'absent' => 0];
                    $wWorking += $st['working'];
                    $wPresent += $st['present'];
                    $wLate += $st['late'];
                    $wSick += $st['sick'];
                    $wLeave += $st['leave'];
                    $wAbsent += $st['absent'];
                    $wHours += (float) ($hoursByDate[$dt] ?? 0);
                    $cur->addDay();
                }

                $items[] = [
                    'key' => $win['w'],
                    'label' => sprintf('%s (%02d - %02d %s)', $win['w'], $wStart->day, $wEnd->day, $from->translatedFormat('M')),
                    'start_date' => $wStart->toDateString(),
                    'end_date' => $wEnd->toDateString(),
                    'working' => $wWorking,
                    'present' => $wPresent,
                    'late' => $wLate,
                    'sick' => $wSick,
                    'leave' => $wLeave,
                    'absent' => $wAbsent,
                    'attendance_rate' => $wWorking > 0 ? round($wPresent / $wWorking * 100, 1) : null,
                    'on_time_rate' => $wWorking > 0 ? round(max(0, $wPresent - $wLate) / $wWorking * 100, 1) : null,
                    'man_hours' => round($wHours, 1),
                ];
            }

            return $items;
        }

        // Monthly chunks for quarter/year
        $cur = $from->copy()->startOfMonth();
        while ($cur->lte($to)) {
            $mStart = $cur->copy()->startOfMonth()->max($from);
            $mEnd = $cur->copy()->endOfMonth()->min($to);

            $mWorking = 0;
            $mPresent = 0;
            $mLate = 0;
            $mSick = 0;
            $mLeave = 0;
            $mAbsent = 0;
            $mHours = 0.0;

            $d = $mStart->copy();
            while ($d->lte($mEnd)) {
                $dt = $d->toDateString();
                $st = $dailyBreakdown[$dt] ?? ['working' => 0, 'present' => 0, 'late' => 0, 'sick' => 0, 'leave' => 0, 'absent' => 0];
                $mWorking += $st['working'];
                $mPresent += $st['present'];
                $mLate += $st['late'];
                $mSick += $st['sick'];
                $mLeave += $st['leave'];
                $mAbsent += $st['absent'];
                $mHours += (float) ($hoursByDate[$dt] ?? 0);
                $d->addDay();
            }

            $items[] = [
                'key' => $cur->format('Y-m'),
                'label' => $cur->translatedFormat('F Y'),
                'start_date' => $mStart->toDateString(),
                'end_date' => $mEnd->toDateString(),
                'working' => $mWorking,
                'present' => $mPresent,
                'late' => $mLate,
                'sick' => $mSick,
                'leave' => $mLeave,
                'absent' => $mAbsent,
                'attendance_rate' => $mWorking > 0 ? round($mPresent / $mWorking * 100, 1) : null,
                'on_time_rate' => $mWorking > 0 ? round(max(0, $mPresent - $mLate) / $mWorking * 100, 1) : null,
                'man_hours' => round($mHours, 1),
            ];

            $cur->addMonth();
        }

        return $items;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @param  Collection<int, array<string, mixed>>|null  $sakitList
     * @param  Collection<int, array<string, mixed>>|null  $cutiList
     * @param  Collection<int, array<string, mixed>>|null  $alpaList
     * @param  Collection<int, array<string, mixed>>|null  $resignList
     * @return array<string, mixed>
     */
    private function summary(
        Collection $rows,
        ?Collection $sakitList = null,
        ?Collection $cutiList = null,
        ?Collection $alpaList = null,
        ?Collection $resignList = null
    ): array {
        $counted = $rows->whereNotNull('on_time');
        $flagCount = fn (string $f) => $rows->filter(fn ($r) => in_array($f, $r['flags'], true))->count();

        $totalWorking = $rows->sum('working');
        $totalPresent = $rows->sum('present');

        return [
            'people' => $rows->count(),
            'on_time' => $counted->isEmpty() ? null : round($counted->avg('on_time'), 1),
            'present' => $totalPresent,
            'working' => $totalWorking,
            'late' => $rows->sum('late'),
            'sick' => $rows->sum('sick'),
            'leave' => $rows->sum('leave'),
            'absent' => $rows->sum('absent'),
            'assumed' => $rows->where('assumed', true)->count(),
            'flags' => array_combine(array_keys(self::FLAGS), array_map($flagCount, array_keys(self::FLAGS))),
            'attendance_rate' => $totalWorking > 0 ? round($totalPresent / $totalWorking * 100, 1) : null,
            'sick_people' => $sakitList ? $sakitList->count() : 0,
            'leave_people' => $cutiList ? $cutiList->count() : 0,
            'absent_people' => $alpaList ? $alpaList->count() : 0,
            'period_resign' => $resignList ? $resignList->where('is_period_resign', true)->count() : 0,
            'total_resigned' => $resignList ? $resignList->count() : 0,
        ];
    }
}
