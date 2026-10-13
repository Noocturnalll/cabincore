<?php

namespace App\Livewire\Modules\Hr;

use App\Exports\TrainingExport;
use App\Models\Division;
use App\Models\EmployeeTraining;
use App\Services\Training\TrainingExcelImporter;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class Training extends Component
{
    use WithFileUploads, WithPagination;

    public string $tab = 'matrix'; // matrix, recurrent, schedule

    public string $search = '';

    public string $stationFilter = '';

    public string $divisionFilter = '';

    public string $companyFilter = '';

    public string $categoryFilter = '';

    public string $statusFilter = '';

    public string $courseFilter = '';

    public int $perPage = 25;

    // Detail Modal
    public bool $isDetailOpen = false;

    public ?string $selectedEmployeeNik = null;

    public ?string $selectedEmployeeName = null;

    public ?string $selectedEmployeeStation = null;

    public ?string $selectedEmployeeJob = null;

    public ?string $selectedEmployeeCompany = null;

    public array $employeeTrainingsGrouped = [];

    // Import Modal
    public bool $isImportOpen = false;

    public $importFile = null;

    public ?array $importResult = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('hr.view'), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStationFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDivisionFilter(): void
    {
        $this->resetPage();
    }

    public function setDivision(string $divisionId): void
    {
        $this->divisionFilter = $divisionId;
        $this->resetPage();
    }

    public function setStation(string $station): void
    {
        $this->stationFilter = $station;
        $this->resetPage();
    }

    public function updatingCompanyFilter(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingCourseFilter(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['matrix', 'recurrent', 'schedule', 'pivot'], true) ? $tab : 'matrix';
        $this->resetPage();
    }

    public function setCategory(string $category): void
    {
        $this->categoryFilter = $category;
        $this->resetPage();
    }

    public function openDetail(string $nik): void
    {
        $records = EmployeeTraining::where('employee_nik', $nik)->orderBy('category')->orderBy('course_name')->get();
        if ($records->isEmpty()) {
            return;
        }

        $first = $records->first();
        $this->selectedEmployeeNik = $nik;
        $this->selectedEmployeeName = $first->employee_name;
        $this->selectedEmployeeStation = $first->station;
        $this->selectedEmployeeJob = $first->job_title;
        $this->selectedEmployeeCompany = $first->company;

        $grouped = [];
        foreach ($records as $rec) {
            $cat = $rec->category ?: 'Lainnya';
            if (! isset($grouped[$cat])) {
                $grouped[$cat] = [];
            }
            $grouped[$cat][] = [
                'course_name' => $rec->course_name,
                'course_date' => $rec->course_date ? $rec->course_date->format('d/m/Y') : null,
                'last_recurrent_date' => $rec->last_recurrent_date ? $rec->last_recurrent_date->format('d/m/Y') : null,
                'expiry_date' => $rec->expiry_date ? $rec->expiry_date->format('d/m/Y') : null,
                'status' => $rec->status,
                'schedule_status' => $rec->schedule_status,
                'raw_value' => $rec->raw_value,
            ];
        }

        $this->employeeTrainingsGrouped = $grouped;
        $this->isDetailOpen = true;
    }

    public function closeDetail(): void
    {
        $this->isDetailOpen = false;
        $this->selectedEmployeeNik = null;
        $this->employeeTrainingsGrouped = [];
    }

    public function openImportModal(): void
    {
        $this->isImportOpen = true;
        $this->importFile = null;
        $this->importResult = null;
    }

    public function closeImportModal(): void
    {
        $this->isImportOpen = false;
        $this->importFile = null;
        $this->importResult = null;
    }

    public function importExcel(): void
    {
        abort_unless(auth()->user()?->can('hr.manage'), 403);

        $this->validate([
            'importFile' => 'required|file|mimes:xlsx,xls,bin|max:51200',
        ], [
            'importFile.required' => 'Pilih file Excel database training terlebih dahulu.',
            'importFile.mimes' => 'Format file harus berupa Excel (.xlsx atau .xls).',
            'importFile.max' => 'Ukuran file maksimal 50MB.',
        ]);

        $path = $this->importFile->getRealPath();

        try {
            $importer = app(TrainingExcelImporter::class);
            $stats = $importer->import($path);

            $this->importResult = $stats;
            session()->flash('success', "Import berhasil! {$stats['trainings_recorded']} rekor pelatihan dari {$stats['employees_processed']} karyawan berhasil disinkronisasi.");
            $this->resetPage();
        } catch (\Throwable $e) {
            $this->addError('importFile', 'Gagal memproses file: '.$e->getMessage());
        }
    }

    public function exportExcel(): BinaryFileResponse
    {
        $filename = 'Data_Training_Cabin_Maintenance_'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(new TrainingExport(
            search: $this->search,
            stationFilter: $this->stationFilter,
            companyFilter: $this->companyFilter,
            categoryFilter: $this->categoryFilter,
            statusFilter: $this->statusFilter,
            courseFilter: $this->courseFilter,
            divisionFilter: $this->divisionFilter,
        ), $filename);
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->stationFilter = '';
        $this->divisionFilter = '';
        $this->companyFilter = '';
        $this->categoryFilter = '';
        $this->statusFilter = '';
        $this->courseFilter = '';
        $this->resetPage();
    }

    public function render()
    {
        // 1. KPI Stats Summary (cached/instant count)
        $totalTrainings = EmployeeTraining::count();
        $totalEmployees = EmployeeTraining::distinct('employee_nik')->count('employee_nik');
        $validCount = EmployeeTraining::where('status', 'valid')->count();
        $expiringCount = EmployeeTraining::where('status', 'expiring_soon')->count();
        $expiredCount = EmployeeTraining::where('status', 'expired')->count();
        $scheduledCount = EmployeeTraining::where('status', 'scheduled')->count();
        $unscheduledCount = EmployeeTraining::where('status', 'unscheduled')->count();

        // 2. Dropdown & Tab Filter Options
        $divisions = Division::where('status', 'Aktif')->orderBy('name')->get();

        $divisionCounts = EmployeeTraining::join('employees', 'employee_trainings.employee_id', '=', 'employees.id')
            ->selectRaw('employees.division_id, count(employee_trainings.id) as cnt')
            ->groupBy('employees.division_id')
            ->pluck('cnt', 'division_id')
            ->all();

        $stationCounts = EmployeeTraining::whereNotNull('station')->where('station', '!=', '')
            ->selectRaw('station, count(id) as cnt')
            ->groupBy('station')
            ->pluck('cnt', 'station')
            ->all();

        $stations = EmployeeTraining::whereNotNull('station')->where('station', '!=', '')
            ->distinct()->orderBy('station')->pluck('station')->all();

        $companies = EmployeeTraining::whereNotNull('company')->where('company', '!=', '')
            ->distinct()->orderBy('company')->pluck('company')->all();

        $categories = EmployeeTraining::distinct()->orderBy('category')->pluck('category')->all();

        $categoryCounts = EmployeeTraining::selectRaw('category, count(id) as cnt')
            ->groupBy('category')
            ->pluck('cnt', 'category')
            ->all();

        $courses = EmployeeTraining::distinct()->orderBy('course_name')->pluck('course_name')->all();

        // 3. Tab-based Data Query
        $query = EmployeeTraining::query();

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('employee_name', 'like', "%{$this->search}%")
                    ->orWhere('employee_nik', 'like', "%{$this->search}%")
                    ->orWhere('job_title', 'like', "%{$this->search}%")
                    ->orWhere('course_name', 'like', "%{$this->search}%");
            });
        }

        if ($this->stationFilter !== '') {
            $query->where('station', $this->stationFilter);
        }

        if ($this->divisionFilter !== '') {
            $query->whereHas('employee', fn ($q) => $q->where('division_id', $this->divisionFilter));
        }

        if ($this->companyFilter !== '') {
            $query->where('company', $this->companyFilter);
        }

        if ($this->categoryFilter !== '') {
            $query->where('category', $this->categoryFilter);
        }

        if ($this->statusFilter !== '') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->courseFilter !== '') {
            $query->where('course_name', $this->courseFilter);
        }

        if ($this->tab === 'recurrent') {
            $query->where(function ($q) {
                $q->whereNotNull('expiry_date')
                    ->orWhereIn('status', ['expired', 'expiring_soon']);
            })->orderByRaw('CASE WHEN status = "expired" THEN 1 WHEN status = "expiring_soon" THEN 2 ELSE 3 END')
                ->orderBy('expiry_date');
        } elseif ($this->tab === 'schedule') {
            $query->where(function ($q) {
                $q->whereNotNull('schedule_status')
                    ->orWhereIn('status', ['scheduled', 'unscheduled']);
            })->orderBy('schedule_status');
        } else {
            $query->orderBy('station')->orderBy('employee_name')->orderBy('course_name');
        }

        $records = $query->paginate($this->perPage);
        $pivots = $this->tab === 'pivot' ? $this->computePivots() : null;

        return view('livewire.modules.hr.training', [
            'records' => $records,
            'stations' => $stations,
            'stationCounts' => $stationCounts,
            'divisions' => $divisions,
            'divisionCounts' => $divisionCounts,
            'companies' => $companies,
            'categories' => $categories,
            'categoryCounts' => $categoryCounts,
            'courses' => $courses,
            'pivots' => $pivots,
            'canManage' => auth()->user()?->can('hr.manage') ?? false,
            'summary' => [
                'total_trainings' => $totalTrainings,
                'total_employees' => $totalEmployees,
                'valid' => $validCount,
                'expiring_soon' => $expiringCount,
                'expired' => $expiredCount,
                'scheduled' => $scheduledCount,
                'unscheduled' => $unscheduledCount,
            ],
        ])->layout('components.layouts.app', ['title' => 'Data Training Cabin Maintenance']);
    }

    /**
     * @return array{
     *     stations: array<string, array{valid: int, expiring_soon: int, expired: int, scheduled: int, unscheduled: int, total: int, compliance: float}>,
     *     categories: array<string, array{valid: int, expiring_soon: int, expired: int, scheduled: int, unscheduled: int, total: int, compliance: float}>,
     *     divisions: array<string, array{valid: int, expiring_soon: int, expired: int, scheduled: int, unscheduled: int, total: int, compliance: float}>,
     *     stationTotals: array{valid: int, expiring_soon: int, expired: int, scheduled: int, unscheduled: int, total: int, compliance: float},
     *     categoryTotals: array{valid: int, expiring_soon: int, expired: int, scheduled: int, unscheduled: int, total: int, compliance: float},
     *     divisionTotals: array{valid: int, expiring_soon: int, expired: int, scheduled: int, unscheduled: int, total: int, compliance: float},
     * }
     */
    private function computePivots(): array
    {
        // 1. Station Pivot
        $stnRows = EmployeeTraining::selectRaw('COALESCE(NULLIF(station, ""), "Tanpa Station") as stn, status, count(id) as cnt')
            ->groupBy('stn', 'status')
            ->get();

        $stationPivot = [];
        $stationTotals = ['valid' => 0, 'expiring_soon' => 0, 'expired' => 0, 'scheduled' => 0, 'unscheduled' => 0, 'total' => 0, 'compliance' => 0.0];

        foreach ($stnRows as $r) {
            $stn = (string) $r->stn;
            if (! isset($stationPivot[$stn])) {
                $stationPivot[$stn] = ['valid' => 0, 'expiring_soon' => 0, 'expired' => 0, 'scheduled' => 0, 'unscheduled' => 0, 'total' => 0, 'compliance' => 0.0];
            }
            $status = (string) $r->status;
            $cnt = (int) $r->cnt;
            if (isset($stationPivot[$stn][$status])) {
                $stationPivot[$stn][$status] += $cnt;
            }
            $stationPivot[$stn]['total'] += $cnt;

            if (isset($stationTotals[$status])) {
                $stationTotals[$status] += $cnt;
            }
            $stationTotals['total'] += $cnt;
        }

        foreach ($stationPivot as $stn => &$data) {
            $data['compliance'] = $data['total'] > 0 ? round(($data['valid'] / $data['total']) * 100, 1) : 0.0;
        }
        unset($data);
        ksort($stationPivot);

        $stationTotals['compliance'] = $stationTotals['total'] > 0 ? round(($stationTotals['valid'] / $stationTotals['total']) * 100, 1) : 0.0;

        // 2. Category Pivot
        $catRows = EmployeeTraining::selectRaw('COALESCE(NULLIF(category, ""), "Lainnya") as cat, status, count(id) as cnt')
            ->groupBy('cat', 'status')
            ->get();

        $categoryPivot = [];
        $categoryTotals = ['valid' => 0, 'expiring_soon' => 0, 'expired' => 0, 'scheduled' => 0, 'unscheduled' => 0, 'total' => 0, 'compliance' => 0.0];

        foreach ($catRows as $r) {
            $cat = (string) $r->cat;
            if (! isset($categoryPivot[$cat])) {
                $categoryPivot[$cat] = ['valid' => 0, 'expiring_soon' => 0, 'expired' => 0, 'scheduled' => 0, 'unscheduled' => 0, 'total' => 0, 'compliance' => 0.0];
            }
            $status = (string) $r->status;
            $cnt = (int) $r->cnt;
            if (isset($categoryPivot[$cat][$status])) {
                $categoryPivot[$cat][$status] += $cnt;
            }
            $categoryPivot[$cat]['total'] += $cnt;

            if (isset($categoryTotals[$status])) {
                $categoryTotals[$status] += $cnt;
            }
            $categoryTotals['total'] += $cnt;
        }

        foreach ($categoryPivot as $cat => &$data) {
            $data['compliance'] = $data['total'] > 0 ? round(($data['valid'] / $data['total']) * 100, 1) : 0.0;
        }
        unset($data);
        ksort($categoryPivot);

        $categoryTotals['compliance'] = $categoryTotals['total'] > 0 ? round(($categoryTotals['valid'] / $categoryTotals['total']) * 100, 1) : 0.0;

        // 3. Division Pivot
        $divRows = EmployeeTraining::join('employees', 'employee_trainings.employee_id', '=', 'employees.id')
            ->join('divisions', 'employees.division_id', '=', 'divisions.id')
            ->selectRaw('divisions.name as div_name, employee_trainings.status, count(employee_trainings.id) as cnt')
            ->groupBy('divisions.name', 'employee_trainings.status')
            ->get();

        $divisionPivot = [];
        $divisionTotals = ['valid' => 0, 'expiring_soon' => 0, 'expired' => 0, 'scheduled' => 0, 'unscheduled' => 0, 'total' => 0, 'compliance' => 0.0];

        foreach ($divRows as $r) {
            $div = (string) $r->div_name;
            if (! isset($divisionPivot[$div])) {
                $divisionPivot[$div] = ['valid' => 0, 'expiring_soon' => 0, 'expired' => 0, 'scheduled' => 0, 'unscheduled' => 0, 'total' => 0, 'compliance' => 0.0];
            }
            $status = (string) $r->status;
            $cnt = (int) $r->cnt;
            if (isset($divisionPivot[$div][$status])) {
                $divisionPivot[$div][$status] += $cnt;
            }
            $divisionPivot[$div]['total'] += $cnt;

            if (isset($divisionTotals[$status])) {
                $divisionTotals[$status] += $cnt;
            }
            $divisionTotals['total'] += $cnt;
        }

        foreach ($divisionPivot as $div => &$data) {
            $data['compliance'] = $data['total'] > 0 ? round(($data['valid'] / $data['total']) * 100, 1) : 0.0;
        }
        unset($data);
        ksort($divisionPivot);

        $divisionTotals['compliance'] = $divisionTotals['total'] > 0 ? round(($divisionTotals['valid'] / $divisionTotals['total']) * 100, 1) : 0.0;

        return [
            'stations' => $stationPivot,
            'categories' => $categoryPivot,
            'divisions' => $divisionPivot,
            'stationTotals' => $stationTotals,
            'categoryTotals' => $categoryTotals,
            'divisionTotals' => $divisionTotals,
        ];
    }
}
