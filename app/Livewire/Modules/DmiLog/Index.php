<?php

namespace App\Livewire\Modules\DmiLog;

use App\Exports\DmiExport;
use App\Imports\DmiImport;
use App\Models\DailyJobAssignment;
use App\Models\DmiLog;
use App\Notifications\SystemNotification;
use App\Services\GoogleSheetsSyncService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class Index extends Component
{
    use WithFileUploads, WithPagination;

    public $activeTab = 'planned';

    public $isModalOpen = false;

    public $isImportModalOpen = false;

    public $file;

    public $selectedLogId = null;

    public $status = 'Open';

    public $hold_reason_category = '';

    public $hold_remarks = '';

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
            $this->dispatch('notify', ['icon' => 'error', 'message' => 'Terjadi kesalahan saat mengimport data: '.$e->getMessage()]);
            auth()->user()->notify(new SystemNotification(['type' => 'error', 'title' => 'Sistem', 'message' => 'Terjadi kesalahan saat mengimport data: '.$e->getMessage()]));
        }
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function openStatusModal($id)
    {
        $this->selectedLogId = $id;
        $log = DmiLog::find($id);
        if ($log) {
            $this->status = $log->status === 'Closed' ? 'Closed' : 'Open';
            $this->hold_reason_category = $log->hold_reason_category;
            $this->hold_remarks = $log->hold_remarks;
            $this->isModalOpen = true;
        }
    }

    public function updateStatus(GoogleSheetsSyncService $syncService)
    {
        $this->validate([
            'status' => 'required|in:Open,Closed',
            'hold_reason_category' => 'required_if:status,Open',
        ]);

        $log = DmiLog::find($this->selectedLogId);
        if ($log) {
            $log->status = $this->status;
            $log->hold_reason_category = $this->status === 'Open' ? $this->hold_reason_category : null;
            $log->hold_remarks = $this->status === 'Open' ? $this->hold_remarks : null;
            $log->save();

            if ($log->dja_id) {
                $dja = DailyJobAssignment::find($log->dja_id);
                if ($dja && $dja->source_spreadsheet_id) {
                    $syncService->pushSync($dja->source_spreadsheet_id, 'DJA DMI', $dja->task_id, $this->status, $this->hold_remarks);
                }
            }
        }

        $this->isModalOpen = false;
        $this->dispatch('notify', ['icon' => 'success', 'message' => 'Status updated successfully.']);
        auth()->user()->notify(new SystemNotification(['type' => 'success', 'title' => 'Sistem', 'message' => 'Status updated successfully.']));
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
        $activeDate = now()->hour >= 18 ? now()->format('Y-m-d') : now()->subDays(1)->format('Y-m-d');

        $query->where(function ($q) use ($activeDate) {
            $q->whereHas('dailyJobAssignment', function ($q2) use ($activeDate) {
                $q2->whereDate('date', $activeDate);
            })->orWhere(function ($q2) use ($activeDate) {
                $q2->whereNull('dja_id')->whereDate('date', $activeDate);
            });
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

        if ($this->dateFilter) {
            $query->whereDate('date', $this->dateFilter);
        }

        $query->orderBy('date', 'asc');

        if ($this->activeTab === 'planned') {
            $query->whereNotNull('dja_id');
        } else {
            $query->whereNull('dja_id');
        }

        return view('livewire.modules.dmi-log.index', [
            'logs' => $query->with('dailyJobAssignment')->get(),
        ])->layout('components.layouts.app', ['title' => 'DMI Logs']);
    }
}
