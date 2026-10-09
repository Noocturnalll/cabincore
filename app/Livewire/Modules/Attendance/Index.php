<?php

namespace App\Livewire\Modules\Attendance;

use App\Models\AssetAssignment;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\JobCrew;
use App\Models\RosterEntry;
use App\Services\Attendance\AttendanceImporter;
use App\Services\Attendance\AttendanceScorer;
use App\Services\Master\MasterSettings;
use Carbon\Carbon;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Presensi & disiplin: who is diligent, punctual, often sick, on leave too much or late - per month, quarter or year.
 * Everyone with attendance.view sees their own scope (station and team); registry.view_all sees all.
 * Clicking a person opens their profile: attendance days, man hours worked (from the job crew) and assets held.
 */
class Index extends Component
{
    use WithFileUploads;

    #[Url(as: 'p')]
    public string $kind = 'month';        // month | quarter | year

    #[Url(as: 'd')]
    public string $date = '';

    #[Url(as: 'sta')]
    public string $station = '';

    #[Url(as: 'f')]
    public string $flag = '';

    public string $search = '';

    public string $sort = 'on_time';

    public string $dir = 'asc';

    public ?string $selected = null;

    public $file = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('attendance.view'), 403);
        $this->date = $this->date ?: now()->startOfMonth()->toDateString();
    }

    private function seesAll(): bool
    {
        return (bool) auth()->user()->can('registry.view_all');
    }

    /** @return array{0: ?array<int, string>, 1: ?string} */
    private function scope(): array
    {
        $user = auth()->user();
        if ($this->seesAll()) {
            return [$this->station !== '' ? [strtoupper($this->station)] : null, null];
        }

        return [$user->station ? [strtoupper($user->station)] : null, app(MasterSettings::class)->teamForDivision($user->division_id)];
    }

    private function range(): array
    {
        $d = Carbon::parse($this->date ?: now());

        return match ($this->kind) {
            'year' => [$d->copy()->startOfYear(), $d->copy()->endOfYear()->startOfDay()],
            'quarter' => [$d->copy()->firstOfQuarter(), $d->copy()->lastOfQuarter()->startOfDay()],
            default => [$d->copy()->startOfMonth(), $d->copy()->endOfMonth()->startOfDay()],
        };
    }

    public function setKind(string $kind): void
    {
        $this->kind = in_array($kind, ['month', 'quarter', 'year'], true) ? $kind : 'month';
        $this->selected = null;
    }

    public function move(int $steps): void
    {
        $d = Carbon::parse($this->date);
        $next = match ($this->kind) {
            'year' => $d->addYears($steps),
            'quarter' => $d->addMonthsNoOverflow(3 * $steps),
            default => $d->addMonthsNoOverflow($steps),
        };
        $this->date = $next->toDateString();
        $this->selected = null;
    }

    public function sortBy(string $key): void
    {
        $this->dir = $this->sort === $key && $this->dir === 'asc' ? 'desc' : 'asc';
        $this->sort = $key;
    }

    public function pick(?string $nik): void
    {
        $this->selected = $this->selected === $nik ? null : $nik;
    }

    public function importFile(AttendanceImporter $importer): void
    {
        abort_unless(auth()->user()->can('attendance.manage'), 403);
        $this->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:20480']], [], ['file' => 'file presensi']);

        try {
            $stats = $importer->import($this->file->getRealPath());
        } catch (\Throwable $e) {
            $this->addError('file', $e->getMessage());

            return;
        }
        $this->file = null;
        $msg = "{$stats['saved']} hari presensi diimpor".($stats['late'] ? ", {$stats['late']} terlambat" : '').'.';
        if ($stats['unknown_status']) {
            $msg .= ' Status tidak dikenali: '.collect($stats['unknown_status'])->take(4)->map(fn ($n, $k) => "$k ($n)")->implode(', ').'.';
        }
        $this->dispatch('notify', ['icon' => $stats['saved'] ? 'success' : 'warning', 'message' => $msg, 'timer' => 8000]);
    }

    public function render(AttendanceScorer $scorer)
    {
        [$from, $to] = $this->range();
        [$stations, $team] = $this->scope();
        $result = $scorer->build($from, $to, $stations, $team);

        $rows = $result['rows']
            ->when($this->flag !== '', fn ($c) => $c->filter(fn ($r) => in_array($this->flag, $r['flags'], true)))
            ->when($this->search !== '', fn ($c) => $c->filter(fn ($r) => stripos($r['name'].' '.$r['nik'], $this->search) !== false));
        $key = in_array($this->sort, ['name', 'station', 'on_time', 'working', 'late', 'sick', 'leave', 'absent', 'man_hours'], true) ? $this->sort : 'on_time';
        $rows = $rows->sortBy(fn ($r) => $r[$key] ?? -1, SORT_NATURAL | SORT_FLAG_CASE, $this->dir === 'desc')->values();

        // pivot per station over everyone in scope (before the flag / search filters)
        $byStation = $result['rows']->groupBy('station')->map(fn ($g, $sta) => [
            'station' => $sta, 'people' => $g->count(),
            'on_time' => ($k = $g->where('assumed', false)->whereNotNull('on_time'))->isEmpty() ? null : round($k->avg('on_time'), 1),
            'late' => $g->sum('late'), 'sick' => $g->sum('sick'), 'leave' => $g->sum('leave'), 'absent' => $g->sum('absent'),
            'flagged' => $g->filter(fn ($r) => array_diff($r['flags'], ['rajin']))->count(),
            'diligent' => $g->filter(fn ($r) => in_array('rajin', $r['flags'], true))->count(),
        ])->sortBy('station')->values();

        return view('livewire.modules.attendance.index', [
            'byStation' => $byStation,
            'from' => $from, 'to' => $to,
            'rows' => $rows,
            'summary' => $result['summary'],
            'months' => $result['months'],
            'flags' => AttendanceScorer::FLAGS,
            'stationOptions' => RosterEntry::query()->distinct()->orderBy('station')->pluck('station'),
            'seesAll' => $this->seesAll(),
            'canImport' => auth()->user()->can('attendance.manage'),
            'profile' => $this->selected ? $this->profile($this->selected, $from, $to) : null,
        ])->layout('components.layouts.app', ['title' => 'Presensi & Disiplin']);
    }

    /** Attendance days, hours worked on jobs and assets held for one person. */
    private function profile(string $nik, Carbon $from, Carbon $to): array
    {
        $employee = Employee::where('nik', $nik)->first();
        $records = AttendanceRecord::where('employee_nik', $nik)
            ->whereDate('work_date', '>=', $from->toDateString())->whereDate('work_date', '<=', $to->toDateString())
            ->orderBy('work_date')->get();

        $crew = JobCrew::where('employee_ref', $nik)
            ->whereDate('work_date', '>=', $from->toDateString())->whereDate('work_date', '<=', $to->toDateString());

        return [
            'nik' => $nik,
            'name' => $employee?->name ?? $records->first()?->employee_name ?? $nik,
            'job_title' => $employee?->job_title,
            'records' => $records,
            'hours' => round((float) (clone $crew)->sum('man_hour'), 1),
            'jobs' => (clone $crew)->count(),
            'assets' => $employee
                ? AssetAssignment::with('asset')->where('employee_id', $employee->id)->whereNull('returned_at')->get()
                : collect(),
        ];
    }
}
