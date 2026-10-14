<?php

namespace App\Livewire\Modules\AircraftCleaning;

use App\Exports\AircraftCleaningExport;
use App\Imports\AircraftCleaningImport;
use App\Livewire\Traits\WithLogTable;
use App\Models\AircraftCleaning;
use App\Notifications\SystemNotification;
use App\Services\Aiec\StationCapabilityService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class DailyReport extends Component
{
    use WithFileUploads, WithLogTable, WithPagination;

    /** Tab Utama: logs, capability, pivots, overdue */
    public string $mainTab = 'logs';

    /** Sub-filter jenis cleaning: '', Transit, General, DCI, DCE, GCI, GCE, LGT (DBI is Cabin Maintenance) */
    public string $cleaningType = '';

    /** Filter periode bulan: '', 2026-10, 2026-09, 2026-08, 2026-07, 2026-06 */
    public string $monthFilter = '';

    public string $dateFilter = '';

    public string $stationFilter = '';

    public string $shiftFilter = '';

    public string $operatorFilter = '';

    public string $statusFilter = '';

    public string $search = '';

    // Modal Import
    public bool $isImportModalOpen = false;

    public $file;

    /** Target sheet import: ALL, DATABASE, SEP, AUG 26, JUL 26, JUN 26 */
    public string $importTargetSheet = 'ALL';

    protected $queryString = [
        'mainTab' => ['except' => 'logs'],
        'cleaningType' => ['except' => ''],
        'monthFilter' => ['except' => ''],
        'stationFilter' => ['except' => ''],
        'shiftFilter' => ['except' => ''],
        'operatorFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'search' => ['except' => ''],
    ];

    public function setMainTab(string $tab): void
    {
        $this->mainTab = in_array($tab, ['logs', 'capability', 'pivots', 'overdue'], true) ? $tab : 'logs';
        $this->resetPage();
    }

    public function setCleaningType(string $type): void
    {
        $this->cleaningType = $type;
        $this->resetPage();
    }

    public function openWaModal(): void
    {
        $date = $this->dateFilter ?: now()->toDateString();
        $this->dispatch('open-wa-report-modal',
            module: 'cleaning',
            startDate: $date,
            endDate: $date,
            station: $this->stationFilter ?: null
        );
    }

    public function resetFilters(): void
    {
        $this->reset(['cleaningType', 'monthFilter', 'dateFilter', 'stationFilter', 'shiftFilter', 'operatorFilter', 'statusFilter', 'search']);
        $this->resetPage();
    }

    public function importData(): void
    {
        $this->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:51200',
        ]);

        try {
            ini_set('memory_limit', '1024M');
            set_time_limit(300);

            Excel::import(new AircraftCleaningImport($this->importTargetSheet), $this->file);

            $this->isImportModalOpen = false;
            $this->file = null;

            $sheetLabel = $this->importTargetSheet === 'ALL' ? 'Semua Sheet Bulanan' : $this->importTargetSheet;
            $msg = "Data Aircraft Cleaning dari sheet [{$sheetLabel}] berhasil diimport dan disanitasi.";

            $this->dispatch('notify', ['icon' => 'success', 'message' => $msg]);
            auth()->user()->notify(new SystemNotification(['type' => 'success', 'title' => 'Import AIEC Selesai', 'message' => $msg]));
        } catch (\Exception $e) {
            $err = 'Terjadi kesalahan saat mengimport data: '.$e->getMessage();
            session()->flash('error', $err);
            $this->dispatch('notify', ['icon' => 'error', 'message' => $err]);
            auth()->user()->notify(new SystemNotification(['type' => 'error', 'title' => 'Import AIEC Gagal', 'message' => $err]));
        }
    }

    public function exportExcel()
    {
        return Excel::download(
            new AircraftCleaningExport(
                $this->search,
                $this->dateFilter,
                $this->cleaningType,
                $this->monthFilter,
                $this->stationFilter,
                $this->shiftFilter,
                $this->operatorFilter,
                $this->statusFilter
            ),
            'AIEC_Report_Export_'.date('Y-m-d_His').'.xlsx'
        );
    }

    public function render()
    {
        $query = AircraftCleaning::aiec();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('aircraft_registration', 'like', '%'.$this->search.'%')
                    ->orWhere('aircraft_type', 'like', '%'.$this->search.'%')
                    ->orWhere('station', 'like', '%'.$this->search.'%')
                    ->orWhere('operator', 'like', '%'.$this->search.'%')
                    ->orWhere('ref_no', 'like', '%'.$this->search.'%')
                    ->orWhere('remarks', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->monthFilter) {
            $query->where('date', 'like', $this->monthFilter.'%');
        }

        if ($this->dateFilter) {
            $query->whereDate('date', $this->dateFilter);
        }

        if ($this->stationFilter) {
            $query->where('station', $this->stationFilter);
        }

        if ($this->shiftFilter) {
            $query->where('shift', $this->shiftFilter);
        }

        if ($this->operatorFilter) {
            $query->where('operator', $this->operatorFilter);
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->cleaningType) {
            $query->where('type', $this->cleaningType);
        }

        $query->orderByDesc('date')->orderByDesc('id');

        // Data pendukung untuk filter dropdown
        $availableStations = AircraftCleaning::select('station')
            ->whereNotNull('station')
            ->distinct()
            ->orderBy('station')
            ->pluck('station');

        $availableOperators = AircraftCleaning::select('operator')
            ->whereNotNull('operator')
            ->distinct()
            ->orderBy('operator')
            ->pluck('operator');

        // Metrik Kapabilitas
        $capabilityMetrics = StationCapabilityService::getStationMetrics($this->monthFilter ?: null);
        $nonCapAlerts = StationCapabilityService::getNonCapabilityOpenAlerts();

        // Data Pivot
        $stationTypePivot = StationCapabilityService::getStationTypePivot($this->monthFilter ?: null);
        $stationShiftPivot = StationCapabilityService::getStationShiftPivot($this->monthFilter ?: null);
        $monthlyTrends = StationCapabilityService::getMonthlyTrendPivot();

        // Data Overdue Batik OD
        $overdueBatikOd = StationCapabilityService::getBatikOdOverdueMonitoring();

        return view('livewire.modules.aircraft-cleaning.daily-report', [
            'logs' => $query->paginate($this->perPage),
            'availableStations' => $availableStations,
            'availableOperators' => $availableOperators,
            'capabilityMetrics' => $capabilityMetrics,
            'nonCapAlerts' => $nonCapAlerts,
            'stationTypePivot' => $stationTypePivot,
            'stationShiftPivot' => $stationShiftPivot,
            'monthlyTrends' => $monthlyTrends,
            'overdueBatikOd' => $overdueBatikOd,
        ])->layout('components.layouts.app', ['title' => 'Aircraft Cleaning - Daily Report & Capability Hub']);
    }
}
