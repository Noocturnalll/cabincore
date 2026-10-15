<?php

namespace App\Livewire\Modules\NsrdiLog;

use App\Exports\NsrdiExport;
use App\Livewire\Traits\ManagesLogStatus;
use App\Livewire\Traits\QuickUnplannedEntry;
use App\Livewire\Traits\ShowsCarryOver;
use App\Livewire\Traits\WithLogTable;
use App\Models\NsrdiLog;
use App\Notifications\SystemNotification;
use App\Services\Dja\DjaPersister;
use App\Services\Nsrdi\NsrdiExcelImporter;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class Index extends Component
{
    use ManagesLogStatus, QuickUnplannedEntry, ShowsCarryOver, WithFileUploads, WithLogTable, WithPagination;

    public $activeTab = 'planned';

    public $isImportModalOpen = false;

    public $file;

    public $search = '';

    public $dateFilter = '';

    public string $category = 'CBM';

    public function mount(?string $category = null): void
    {
        if (request()->routeIs('modules.painting.nsrdi') || $category === 'PAINTING' || request()->query('category') === 'PAINTING') {
            $this->category = 'PAINTING';
        } else {
            $this->category = 'CBM';
        }
    }

    public function importNsrdi(NsrdiExcelImporter $importer)
    {
        $this->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:20480',
        ]);

        try {
            $stats = $importer->import(
                $this->file->getRealPath(),
                false,
                $this->category === 'PAINTING' ? 'PAINTING' : null
            );
            $this->isImportModalOpen = false;
            $this->file = null;
            $message = "Data NSRDI berhasil diimport: {$stats['total']} total ({$stats['dja_count']} planned, {$stats['unplanned_count']} unplanned).";
            $this->dispatch('notify', ['icon' => 'success', 'message' => $message]);
            auth()->user()->notify(new SystemNotification(['type' => 'success', 'title' => 'Sistem', 'message' => $message]));
        } catch (\Throwable $e) {
            session()->flash('error', 'Terjadi kesalahan saat mengimport data: '.$e->getMessage());
        }
    }

    protected function statusLogModel(): string
    {
        return NsrdiLog::class;
    }

    protected function statusSheetTab(): string
    {
        return 'DJA NSRD';
    }

    protected function statusSideEffects(Model $log, string $status): array
    {
        // Capacity Management counts NSRDI by close date, so closing must stamp it (and re-opening clears it)
        return ['close_date' => $status === 'Closed' ? $this->operationalDate() : null];
    }

    public function exportExcel()
    {
        $search = property_exists($this, 'search') ? $this->search : '';
        $dateFilter = property_exists($this, 'dateFilter') ? $this->dateFilter : '';
        $activeTab = property_exists($this, 'activeTab') ? $this->activeTab : '';
        $prefix = $this->category === 'PAINTING' ? 'NsrdiPaintingExport' : 'NsrdiExport';

        return Excel::download(new NsrdiExport($search, $dateFilter, $activeTab, $this->category), $prefix.'-'.date('Y-m-d').'.xlsx');
    }

    protected function nsrdiCarryOverCount(): int
    {
        $query = NsrdiLog::query();
        if ($this->category === 'PAINTING') {
            $query->where('category', 'PAINTING');
        } else {
            $query->where(function ($q) {
                $q->where('category', 'CBM')
                    ->orWhereNull('category')
                    ->orWhere('category', '!=', 'PAINTING');
            });
        }
        $query->where(fn ($q) => $this->addCarryOver($q, DjaPersister::activeDate(), 'DATE(COALESCE(plan_date, report_date))'));

        return $query->count();
    }

    public function render()
    {
        $query = NsrdiLog::query();

        if ($this->category === 'PAINTING') {
            $query->where('category', 'PAINTING');
        } else {
            $query->where(function ($q) {
                $q->where('category', 'CBM')
                    ->orWhereNull('category')
                    ->orWhere('category', '!=', 'PAINTING');
            });
        }

        if ($this->dateFilter) {
            $query->where(function ($q) {
                $q->whereHas('dailyJobAssignment', function ($q2) {
                    $q2->whereDate('date', $this->dateFilter);
                })->orWhere(function ($q2) {
                    $q2->whereNull('dja_id')->whereDate('plan_date', $this->dateFilter);
                });
            });
        } else {
            $activeDate = now()->hour >= 18 ? now()->format('Y-m-d') : now()->subDays(1)->format('Y-m-d');
            $query->where(function ($q) use ($activeDate) {
                $q->whereHas('dailyJobAssignment', function ($q2) use ($activeDate) {
                    $q2->whereDate('date', $activeDate);
                })->orWhere(function ($q2) use ($activeDate) {
                    $q2->whereNull('dja_id')->whereDate('plan_date', $activeDate);
                });

                // unfinished logs of earlier days stay visible until they are closed / completed
                $this->addCarryOver($q, $activeDate, 'DATE(COALESCE(plan_date, report_date))');
            });
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('aircraft_registration', 'like', '%'.$this->search.'%')
                    ->orWhere('status', 'like', '%'.$this->search.'%')
                    ->orWhere('nsrdi_number', 'like', '%'.$this->search.'%')
                    ->orWhere('hold_reason_category', 'like', '%'.$this->search.'%')
                    ->orWhere('plan_station', 'like', '%'.$this->search.'%')
                    ->orWhere('act_station', 'like', '%'.$this->search.'%')
                    ->orWhere('category', 'like', '%'.$this->search.'%')
                    ->orWhere('aoc', 'like', '%'.$this->search.'%')
                    ->orWhere('type', 'like', '%'.$this->search.'%')
                    ->orWhere('description', 'like', '%'.$this->search.'%')
                    ->orWhere('part_description', 'like', '%'.$this->search.'%');
            });
        }

        $query->orderBy('report_date', 'asc');

        if ($this->activeTab === 'planned') {
            $query->whereNotNull('dja_id');
        } elseif ($this->activeTab === 'unplanned') {
            $query->whereNull('dja_id');
        } elseif ($this->activeTab === 'nospare') {
            $query->where(function ($q) {
                $q->where('hold_reason_category', 'NS')
                    ->orWhere('code_open', 'NS');
            });
        }

        $pageTitle = $this->category === 'PAINTING' ? 'NSRDI Aircraft Painting' : 'NSRDI Cabin Maintenance';

        return view('livewire.modules.nsrdi-log.index', [
            'carryOver' => $this->dateFilter ? 0 : $this->nsrdiCarryOverCount(),
            'logs' => $query->with('dailyJobAssignment')->paginate($this->perPage),
            'isPainting' => $this->category === 'PAINTING',
            'category' => $this->category,
        ])->layout('components.layouts.app', ['title' => $pageTitle]);
    }
}
