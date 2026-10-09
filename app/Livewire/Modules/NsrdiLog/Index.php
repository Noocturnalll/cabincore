<?php

namespace App\Livewire\Modules\NsrdiLog;

use App\Exports\NsrdiExport;
use App\Imports\NsrdiImport;
use App\Livewire\Traits\ManagesLogStatus;
use App\Livewire\Traits\ShowsCarryOver;
use App\Livewire\Traits\WithLogTable;
use App\Models\NsrdiLog;
use App\Notifications\SystemNotification;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class Index extends Component
{
    use ManagesLogStatus, ShowsCarryOver, WithFileUploads, WithLogTable, WithPagination;

    public $activeTab = 'planned';

    public $isImportModalOpen = false;

    public $file;

    public $search = '';

    public $dateFilter = '';

    public function importNsrdi()
    {
        $this->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            Excel::import(new NsrdiImport, $this->file);
            $this->isImportModalOpen = false;
            $this->file = null;
            $this->dispatch('notify', ['icon' => 'success', 'message' => 'Data NSRDI berhasil diimport.']);
            auth()->user()->notify(new SystemNotification(['type' => 'success', 'title' => 'Sistem', 'message' => 'Data NSRDI berhasil diimport.']));
        } catch (\Exception $e) {
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

        return Excel::download(new NsrdiExport($search, $dateFilter, $activeTab), 'NsrdiExport-'.date('Y-m-d').'.xlsx');
    }

    public function render()
    {
        $query = NsrdiLog::query();
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

        return view('livewire.modules.nsrdi-log.index', [
            'carryOver' => $this->dateFilter ? 0 : $this->carryOverCount(NsrdiLog::class, 'DATE(COALESCE(plan_date, report_date))'),
            'logs' => $query->with('dailyJobAssignment')->paginate($this->perPage),
        ])->layout('components.layouts.app', ['title' => 'NSRDI Logs']);
    }
}
