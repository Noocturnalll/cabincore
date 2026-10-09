<?php

namespace App\Livewire\Modules\DmiLog;

use App\Exports\DmiExport;
use App\Imports\DmiImport;
use App\Livewire\Traits\ManagesLogStatus;
use App\Livewire\Traits\ShowsCarryOver;
use App\Livewire\Traits\WithLogTable;
use App\Models\DmiLog;
use App\Notifications\SystemNotification;
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

    public function importDmi()
    {
        $this->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            Excel::import(new DmiImport, $this->file);
            $this->isImportModalOpen = false;
            $this->file = null;
            $this->dispatch('notify', ['icon' => 'success', 'message' => 'Data DMI berhasil diimport.']);
            auth()->user()->notify(new SystemNotification(['type' => 'success', 'title' => 'Sistem', 'message' => 'Data DMI berhasil diimport.']));
        } catch (\Exception $e) {
            session()->flash('error', 'Terjadi kesalahan saat mengimport data: '.$e->getMessage());
            $this->dispatch('notify', ['icon' => 'error', 'message' => 'Terjadi kesalahan saat mengimport data: '.$e->getMessage()]);
            auth()->user()->notify(new SystemNotification(['type' => 'error', 'title' => 'Sistem', 'message' => 'Terjadi kesalahan saat mengimport data: '.$e->getMessage()]));
        }
    }

    protected function statusLogModel(): string
    {
        return DmiLog::class;
    }

    protected function statusSheetTab(): string
    {
        return 'DJA DMI';
    }

    public function exportExcel()
    {
        $search = property_exists($this, 'search') ? $this->search : '';
        $dateFilter = property_exists($this, 'dateFilter') ? $this->dateFilter : '';
        $activeTab = property_exists($this, 'activeTab') ? $this->activeTab : '';

        return Excel::download(new DmiExport($search, $dateFilter, $activeTab), 'DmiExport-'.date('Y-m-d').'.xlsx');
    }

    public function render()
    {
        $query = DmiLog::query();
        // A chosen date replaces the default "active date" (otherwise other dates could never be viewed)
        $activeDate = $this->dateFilter
            ?: (now()->hour >= 18 ? now()->format('Y-m-d') : now()->subDays(1)->format('Y-m-d'));

        $query->where(function ($q) use ($activeDate) {
            $q->whereHas('dailyJobAssignment', function ($q2) use ($activeDate) {
                $q2->whereDate('date', $activeDate);
            })->orWhere(function ($q2) use ($activeDate) {
                $q2->whereNull('dja_id')->whereDate('date', $activeDate);
            });

            // unfinished logs of earlier days stay visible until they are closed / completed
            if (! $this->dateFilter) {
                $this->addCarryOver($q, $activeDate, 'DATE(date)');
            }
        });

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('aircraft_registration', 'like', '%'.$this->search.'%')
                    ->orWhere('status', 'like', '%'.$this->search.'%')
                    ->orWhere('dmi_number', 'like', '%'.$this->search.'%')
                    ->orWhere('hold_reason_category', 'like', '%'.$this->search.'%')
                    ->orWhere('plan_station', 'like', '%'.$this->search.'%')
                    ->orWhere('act_station', 'like', '%'.$this->search.'%')
                    ->orWhere('dmi_category', 'like', '%'.$this->search.'%')
                    ->orWhere('category', 'like', '%'.$this->search.'%')
                    ->orWhere('description', 'like', '%'.$this->search.'%')
                    ->orWhere('pn_required', 'like', '%'.$this->search.'%');
            });
        }

        $query->orderBy('date', 'asc');

        if ($this->activeTab === 'planned') {
            $query->whereNotNull('dja_id');
        } else {
            $query->whereNull('dja_id');
        }

        return view('livewire.modules.dmi-log.index', [
            'carryOver' => $this->dateFilter ? 0 : $this->carryOverCount(DmiLog::class, 'DATE(date)'),
            'logs' => $query->with('dailyJobAssignment')->paginate($this->perPage),
        ])->layout('components.layouts.app', ['title' => 'DMI Logs']);
    }
}
