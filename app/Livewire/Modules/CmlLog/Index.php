<?php

namespace App\Livewire\Modules\CmlLog;

use App\Exports\CmlExport;
use App\Imports\CmlImport;
use App\Livewire\Traits\WithAdvancedFilter;
use App\Livewire\Traits\WithLogTable;
use App\Models\CmlLog;
use App\Notifications\SystemNotification;
use App\Services\Reports\ProductionExecutiveService;
use App\Support\CmlKind;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class Index extends Component
{
    use WithAdvancedFilter, WithFileUploads, WithLogTable, WithPagination;

    public $file;

    public $isImportModalOpen = false;

    public string $activeTab = 'logs'; // 'logs' | 'dbi' | 'pivot'

    public function mount()
    {
        $this->mountWithAdvancedFilter();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = in_array($tab, ['logs', 'dbi', 'pivot'], true) ? $tab : 'logs';
        $this->resetPage();
    }

    public function openWaModal(): void
    {
        $startDate = $this->filters['start_date'] ?? now()->startOfMonth()->toDateString();
        $endDate = $this->filters['end_date'] ?? now()->toDateString();
        $station = $this->filters['station'] ?? null;

        $this->dispatch('open-wa-report-modal',
            module: 'cml',
            startDate: $startDate,
            endDate: $endDate,
            station: $station
        );
    }

    public function importCml()
    {
        $this->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240', // 10MB Max
        ]);

        try {
            Excel::import(new CmlImport, $this->file);
            $this->isImportModalOpen = false;
            $this->file = null;
            $this->dispatch('notify', ['icon' => 'success', 'message' => 'Data CML berhasil diimport.']);
            auth()->user()->notify(new SystemNotification(['type' => 'success', 'title' => 'Sistem', 'message' => 'Data CML berhasil diimport.']));
        } catch (\Exception $e) {
            session()->flash('error', 'Terjadi kesalahan saat mengimport data: '.$e->getMessage());
            $this->dispatch('notify', ['icon' => 'error', 'message' => 'Terjadi kesalahan saat mengimport data: '.$e->getMessage()]);
            auth()->user()->notify(new SystemNotification(['type' => 'error', 'title' => 'Sistem', 'message' => 'Terjadi kesalahan saat mengimport data: '.$e->getMessage()]));
        }
    }

    public function exportExcel()
    {
        return Excel::download(
            new CmlExport($this->search, $this->dateStart, '', $this->dateEnd, $this->filterStation),
            'CmlExport-'.date('Y-m-d').'.xlsx'
        );
    }

    public function render()
    {
        // normal CML and DBI are separate lists; the pivot is about CML
        $kind = $this->activeTab === 'dbi' ? CmlKind::DBI : CmlKind::CML;
        $query = CmlLog::query()->with('dailyJobAssignment')->where('kind', $kind);
        $searchFields = ['aircraft_registration', 'status', 'doc_type', 'no_doc', 'station', 'operator', 'ac_status', 'description'];
        $query = $this->scopeAdvancedFilter($query, $searchFields);

        $pivotData = [];
        if ($this->activeTab === 'pivot') {
            $allRows = (clone $query)->get();

            $total = $allRows->count();
            $closed = $allRows->where('status', 'Closed')->count();
            $open = $total - $closed;
            $rate = $total > 0 ? round(($closed / $total) * 100, 1) : 0;

            // 1. Pivot Station vs Operator
            $operators = $allRows->pluck('operator')->filter()->unique()->sort()->values()->all();
            if (empty($operators)) {
                $operators = ['LION AIR', 'BATIK AIR', 'SUPER AIR JET', 'WINGS AIR'];
            }

            $stationMatrix = [];
            foreach ($allRows as $r) {
                $sta = strtoupper(trim($r->station ?: 'N/A'));
                $op = strtoupper(trim($r->operator ?: 'OTHER'));

                if (! isset($stationMatrix[$sta])) {
                    $stationMatrix[$sta] = [
                        'station' => $sta,
                        'operators' => array_fill_keys($operators, 0),
                        'total' => 0,
                        'closed' => 0,
                        'open' => 0,
                    ];
                }

                if (isset($stationMatrix[$sta]['operators'][$op])) {
                    $stationMatrix[$sta]['operators'][$op]++;
                }
                $stationMatrix[$sta]['total']++;
                if (($r->status ?? '') === 'Closed') {
                    $stationMatrix[$sta]['closed']++;
                } else {
                    $stationMatrix[$sta]['open']++;
                }
            }
            uasort($stationMatrix, fn ($a, $b) => $b['total'] <=> $a['total']);

            // 2. Pivot Defect Category
            $categoryCounts = [];
            foreach ($allRows as $r) {
                $cat = ProductionExecutiveService::categorizeDefect($r->description ?? '');
                if (! isset($categoryCounts[$cat])) {
                    $categoryCounts[$cat] = ['total' => 0, 'closed' => 0, 'open' => 0];
                }
                $categoryCounts[$cat]['total']++;
                if (($r->status ?? '') === 'Closed') {
                    $categoryCounts[$cat]['closed']++;
                } else {
                    $categoryCounts[$cat]['open']++;
                }
            }
            uasort($categoryCounts, fn ($a, $b) => $b['total'] <=> $a['total']);

            // 3. Top Aircraft Affected
            $acCounts = $allRows->groupBy(fn ($r) => strtoupper(trim($r->aircraft_registration ?: 'N/A')))
                ->map(fn ($grp, $reg) => [
                    'reg' => $reg,
                    'total' => $grp->count(),
                    'closed' => $grp->where('status', 'Closed')->count(),
                    'open' => $grp->count() - $grp->where('status', 'Closed')->count(),
                ])->sortByDesc('total')->take(6)->values()->all();

            $pivotData = [
                'total' => $total,
                'closed' => $closed,
                'open' => $open,
                'rate' => $rate,
                'operators' => $operators,
                'stationMatrix' => $stationMatrix,
                'categories' => $categoryCounts,
                'topAircraft' => $acCounts,
            ];
        }

        return view('livewire.modules.cml-log.index', [
            'logs' => $query->paginate($this->perPage),
            'pivotData' => $pivotData,
            'dbiCount' => CmlLog::where('kind', CmlKind::DBI)->count(),
        ])->layout('components.layouts.app', ['title' => 'CML Logs & Pivot Defect']);
    }
}
