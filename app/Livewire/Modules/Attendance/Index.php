<?php

namespace App\Livewire\Modules\Attendance;

use App\Exports\AttendanceExport;
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
use Maatwebsite\Excel\Facades\Excel;

/**
 * Presensi & disiplin: who is diligent, punctual, often sick, on leave too much or late - per month, quarter or year.
 * Everyone with attendance.view sees their own scope (station and team); registry.view_all sees all.
 * Clicking a person opens their profile: attendance days, man hours worked (from the job crew) and assets held.
 */
class Index extends Component
{
    use WithFileUploads;

    #[Url(as: 'p')]
    public string $kind = 'month';        // week | month | quarter | year

    #[Url(as: 'tab')]
    public string $tab = 'overview';      // overview | sakit | cuti | alpa | resign

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

    // Quick Record Attendance Modal
    public bool $showRecordModal = false;

    public string $recordNik = '';

    public string $recordDateFrom = '';

    public string $recordDateTo = '';

    public string $recordStatus = 'sakit';

    public string $recordNotes = '';

    // Resign Modal
    public bool $showResignModal = false;

    public ?int $resignEmployeeId = null;

    public string $resignNik = '';

    public string $resignDate = '';

    public string $resignReason = '';

    public string $resignNotes = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('attendance.view'), 403);
        $this->date = $this->date ?: now()->startOfMonth()->toDateString();
        $this->recordDateFrom = now()->toDateString();
        $this->recordDateTo = now()->toDateString();
        $this->resignDate = now()->toDateString();
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
            'week' => [$d->copy()->startOfWeek(Carbon::MONDAY), $d->copy()->endOfWeek(Carbon::SUNDAY)->startOfDay()],
            'year' => [$d->copy()->startOfYear(), $d->copy()->endOfYear()->startOfDay()],
            'quarter' => [$d->copy()->firstOfQuarter(), $d->copy()->lastOfQuarter()->startOfDay()],
            default => [$d->copy()->startOfMonth(), $d->copy()->endOfMonth()->startOfDay()],
        };
    }

    public function setKind(string $kind): void
    {
        $this->kind = in_array($kind, ['week', 'month', 'quarter', 'year'], true) ? $kind : 'month';
        $this->selected = null;
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['overview', 'sakit', 'cuti', 'alpa', 'resign'], true) ? $tab : 'overview';
        $this->selected = null;
    }

    public function move(int $steps): void
    {
        $d = Carbon::parse($this->date);
        $next = match ($this->kind) {
            'week' => $d->addWeeks($steps),
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

    public function openRecordModal(?string $nik = null, string $status = 'sakit'): void
    {
        abort_unless(auth()->user()->can('attendance.manage') || auth()->user()->can('hr.manage'), 403);
        $this->recordNik = $nik ?? '';
        $this->recordStatus = in_array($status, ['sakit', 'cuti', 'izin', 'alpa', 'hadir'], true) ? $status : 'sakit';
        $this->recordDateFrom = $this->recordDateFrom ?: now()->toDateString();
        $this->recordDateTo = $this->recordDateTo ?: now()->toDateString();
        $this->recordNotes = '';
        $this->showRecordModal = true;
    }

    public function closeRecordModal(): void
    {
        $this->showRecordModal = false;
        $this->resetValidation();
    }

    public function saveRecord(): void
    {
        abort_unless(auth()->user()->can('attendance.manage') || auth()->user()->can('hr.manage'), 403);
        $this->validate([
            'recordNik' => ['required', 'exists:employees,nik'],
            'recordDateFrom' => ['required', 'date'],
            'recordDateTo' => ['required', 'date', 'after_or_equal:recordDateFrom'],
            'recordStatus' => ['required', 'in:sakit,cuti,izin,alpa,hadir'],
            'recordNotes' => ['nullable', 'string', 'max:500'],
        ], [], [
            'recordNik' => 'ID Karyawan',
            'recordDateFrom' => 'Tanggal mulai',
            'recordDateTo' => 'Tanggal selesai',
            'recordStatus' => 'Status presensi',
            'recordNotes' => 'Keterangan',
        ]);

        $employee = Employee::where('nik', $this->recordNik)->firstOrFail();
        $cur = Carbon::parse($this->recordDateFrom);
        $end = Carbon::parse($this->recordDateTo);
        $saved = 0;

        $code = match ($this->recordStatus) {
            'sakit' => 'S',
            'cuti' => 'C',
            'izin' => 'I',
            'alpa' => 'A',
            'hadir' => 'H',
            default => 'H',
        };

        while ($cur->lte($end)) {
            AttendanceRecord::updateOrCreate(
                [
                    'employee_nik' => $employee->nik,
                    'work_date' => $cur->toDateString(),
                ],
                [
                    'employee_name' => $employee->name,
                    'station' => $employee->station ?? 'CGK',
                    'status_code' => $code,
                    'status_group' => $this->recordStatus,
                    'late_minutes' => 0,
                    'source' => 'manual',
                    'notes' => $this->recordNotes ?: null,
                ]
            );
            $saved++;
            $cur->addDay();
        }

        $this->showRecordModal = false;
        $this->dispatch('notify', [
            'icon' => 'success',
            'message' => "Berhasil menyimpan {$saved} hari {$this->recordStatus} untuk {$employee->name}.",
        ]);
    }

    public function openResignModal(?int $id = null, ?string $nik = null): void
    {
        abort_unless(auth()->user()->can('attendance.manage') || auth()->user()->can('hr.manage'), 403);
        $this->resignEmployeeId = $id;
        $this->resignNik = $nik ?? '';
        if ($id) {
            $e = Employee::find($id);
            if ($e) {
                $this->resignNik = $e->nik;
                $this->resignDate = $e->resign_date ? $e->resign_date->toDateString() : now()->toDateString();
                $this->resignReason = $e->resign_reason ?? '';
                $this->resignNotes = $e->notes ?? '';
            }
        } else {
            $this->resignDate = now()->toDateString();
            $this->resignReason = '';
            $this->resignNotes = '';
        }
        $this->showResignModal = true;
    }

    public function closeResignModal(): void
    {
        $this->showResignModal = false;
        $this->resetValidation();
    }

    public function saveResign(): void
    {
        abort_unless(auth()->user()->can('attendance.manage') || auth()->user()->can('hr.manage'), 403);
        $this->validate([
            'resignNik' => ['required_without:resignEmployeeId', 'nullable', 'exists:employees,nik'],
            'resignDate' => ['required', 'date'],
            'resignReason' => ['required', 'string', 'max:255'],
            'resignNotes' => ['nullable', 'string', 'max:1000'],
        ], [], [
            'resignNik' => 'Karyawan',
            'resignDate' => 'Tanggal resign',
            'resignReason' => 'Alasan resign',
            'resignNotes' => 'Catatan',
        ]);

        $employee = $this->resignEmployeeId
            ? Employee::findOrFail($this->resignEmployeeId)
            : Employee::where('nik', $this->resignNik)->firstOrFail();

        $employee->update([
            'status' => 'Resign',
            'resign_date' => $this->resignDate,
            'resign_reason' => $this->resignReason,
            'notes' => $this->resignNotes ?: $employee->notes,
        ]);

        $this->showResignModal = false;
        $this->dispatch('notify', [
            'icon' => 'success',
            'message' => "Karyawan {$employee->name} telah dicatat sebagai Resign.",
        ]);
    }

    public function revertResign(int $id): void
    {
        abort_unless(auth()->user()->can('attendance.manage') || auth()->user()->can('hr.manage'), 403);
        $employee = Employee::findOrFail($id);
        $employee->update([
            'status' => 'Aktif',
            'resign_date' => null,
            'resign_reason' => null,
        ]);

        $this->dispatch('notify', [
            'icon' => 'success',
            'message' => "Status {$employee->name} dikembalikan ke Aktif.",
        ]);
    }

    public function exportExcel()
    {
        [$from, $to] = $this->range();
        [$stations, $team] = $this->scope();
        $filename = sprintf('presensi_pivot_%s_%s_%s.xlsx', $this->tab, $from->format('Ymd'), $to->format('Ymd'));

        return Excel::download(new AttendanceExport($from, $to, $stations, $team, $this->tab), $filename);
    }

    public function exportCsv(AttendanceScorer $scorer)
    {
        [$from, $to] = $this->range();
        [$stations, $team] = $this->scope();
        $result = $scorer->build($from, $to, $stations, $team);

        $filename = sprintf('presensi_%s_%s_%s.csv', $this->tab, $from->format('Ymd'), $to->format('Ymd'));

        return response()->streamDownload(function () use ($result) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF"); // BOM for UTF-8 Excel

            if ($this->tab === 'sakit') {
                fputcsv($handle, ['NIK', 'Nama', 'Station', 'Tim', 'Jumlah Hari Sakit', 'Tanggal Sakit', 'Keterangan/Catatan', 'Status Batas']);
                foreach ($result['sakit_list'] as $s) {
                    fputcsv($handle, [
                        $s['nik'],
                        $s['name'],
                        $s['station'],
                        $s['team'],
                        $s['count'],
                        implode(', ', $s['dates']),
                        implode('; ', $s['notes']),
                        $s['exceeded_limit'] ? 'Melebihi Batas' : 'Dalam Batas',
                    ]);
                }
            } elseif ($this->tab === 'cuti') {
                fputcsv($handle, ['NIK', 'Nama', 'Station', 'Tim', 'Jumlah Hari Cuti', 'Tanggal Cuti', 'Keterangan/Catatan', 'Status Batas']);
                foreach ($result['cuti_list'] as $c) {
                    fputcsv($handle, [
                        $c['nik'],
                        $c['name'],
                        $c['station'],
                        $c['team'],
                        $c['count'],
                        implode(', ', $c['dates']),
                        implode('; ', $c['notes']),
                        $c['exceeded_limit'] ? 'Melebihi Batas' : 'Dalam Batas',
                    ]);
                }
            } elseif ($this->tab === 'alpa') {
                fputcsv($handle, ['NIK', 'Nama', 'Station', 'Tim', 'Jumlah Hari Alpa', 'Tanggal Alpa', 'Keterangan/Catatan']);
                foreach ($result['alpa_list'] as $a) {
                    fputcsv($handle, [
                        $a['nik'],
                        $a['name'],
                        $a['station'],
                        $a['team'],
                        $a['count'],
                        implode(', ', $a['dates']),
                        implode('; ', $a['notes']),
                    ]);
                }
            } elseif ($this->tab === 'resign') {
                fputcsv($handle, ['NIK', 'Nama', 'Station', 'Jabatan', 'Divisi', 'Tgl Masuk', 'Tgl Resign', 'Masa Kerja', 'Alasan Resign', 'Catatan']);
                foreach ($result['resign_list'] as $r) {
                    fputcsv($handle, [
                        $r['nik'],
                        $r['name'],
                        $r['station'],
                        $r['job_title'],
                        $r['department'],
                        $r['join_date'] ?? '–',
                        $r['resign_date'] ?? '–',
                        $r['tenure'],
                        $r['resign_reason'],
                        $r['notes'] ?? '',
                    ]);
                }
            } else {
                fputcsv($handle, ['NIK', 'Nama', 'Station', 'Tim', 'Hari Kerja', 'Hadir', 'Tepat Waktu (%)', 'Terlambat', 'Menit Terlambat', 'Sakit', 'Cuti', 'Alpa', 'Man Hours', 'Catatan/Flags']);
                foreach ($result['rows'] as $r) {
                    $flagNames = array_map(fn ($f) => AttendanceScorer::FLAGS[$f] ?? $f, $r['flags']);
                    fputcsv($handle, [
                        $r['nik'],
                        $r['name'],
                        $r['station'],
                        $r['team'],
                        $r['working'],
                        $r['present'],
                        $r['on_time'] !== null ? $r['on_time'].'%' : '–',
                        $r['late'],
                        $r['late_minutes'],
                        $r['sick'],
                        $r['leave'],
                        $r['absent'],
                        $r['man_hours'],
                        implode(', ', $flagNames),
                    ]);
                }
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
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

        // Filter Sakit, Cuti, Alpa lists by search
        $sakitList = $result['sakit_list']
            ->when($this->search !== '', fn ($c) => $c->filter(fn ($r) => stripos($r['name'].' '.$r['nik'], $this->search) !== false))
            ->values();

        $cutiList = $result['cuti_list']
            ->when($this->search !== '', fn ($c) => $c->filter(fn ($r) => stripos($r['name'].' '.$r['nik'], $this->search) !== false))
            ->values();

        $alpaList = $result['alpa_list']
            ->when($this->search !== '', fn ($c) => $c->filter(fn ($r) => stripos($r['name'].' '.$r['nik'], $this->search) !== false))
            ->values();

        $resignList = $result['resign_list']
            ->when($this->search !== '', fn ($c) => $c->filter(fn ($r) => stripos($r['name'].' '.$r['nik'].' '.$r['resign_reason'], $this->search) !== false))
            ->values();

        // pivot per station over everyone in scope (before the flag / search filters)
        $byStation = $result['rows']->groupBy('station')->map(fn ($g, $sta) => [
            'station' => $sta, 'people' => $g->count(),
            'on_time' => ($k = $g->where('assumed', false)->whereNotNull('on_time'))->isEmpty() ? null : round($k->avg('on_time'), 1),
            'late' => $g->sum('late'), 'sick' => $g->sum('sick'), 'leave' => $g->sum('leave'), 'absent' => $g->sum('absent'),
            'flagged' => $g->filter(fn ($r) => array_diff($r['flags'], ['rajin']))->count(),
            'diligent' => $g->filter(fn ($r) => in_array('rajin', $r['flags'], true))->count(),
        ])->sortBy('station')->values();

        $employeesList = Employee::query()
            ->orderBy('name')
            ->get(['id', 'nik', 'name', 'station', 'status']);

        return view('livewire.modules.attendance.index', [
            'byStation' => $byStation,
            'from' => $from,
            'to' => $to,
            'rows' => $rows,
            'summary' => $result['summary'],
            'months' => $result['months'],
            'flags' => AttendanceScorer::FLAGS,
            'sakitList' => $sakitList,
            'cutiList' => $cutiList,
            'alpaList' => $alpaList,
            'resignList' => $resignList,
            'weeklyBreakdown' => $result['weekly_breakdown'],
            'limits' => $result['limits'],
            'stationOptions' => RosterEntry::query()->distinct()->orderBy('station')->pluck('station'),
            'seesAll' => $this->seesAll(),
            'canManage' => auth()->user()->can('attendance.manage') || auth()->user()->can('hr.manage'),
            'canImport' => auth()->user()->can('attendance.manage'),
            'profile' => $this->selected ? $this->profile($this->selected, $from, $to) : null,
            'employeesList' => $employeesList,
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
