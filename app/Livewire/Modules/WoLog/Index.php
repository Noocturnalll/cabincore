<?php

namespace App\Livewire\Modules\WoLog;

use App\Models\WoLog;
use Livewire\Component;

use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\WoImport;

class Index extends Component
{
    use WithPagination, WithFileUploads;

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

    public function importWo()
    {
        $this->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            Excel::import(new WoImport, $this->file);
            $this->isImportModalOpen = false;
            $this->file = null;
            session()->flash('success', 'Data WO berhasil diimport.');
        } catch (\Exception $e) {
            session()->flash('error', 'Terjadi kesalahan saat mengimport data: ' . $e->getMessage());
        }
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function openStatusModal($id)
    {
        $this->selectedLogId = $id;
        $log = WoLog::find($id);
        if ($log) {
            $this->status = $log->status === 'Closed' ? 'Closed' : 'Open';
            $this->hold_reason_category = $log->hold_reason_category;
            $this->hold_remarks = $log->hold_remarks;
            $this->isModalOpen = true;
        }
    }

    public function updateStatus(\App\Services\GoogleSheetsSyncService $syncService)
    {
        $this->validate([
            'status' => 'required|in:Open,Closed',
            'hold_reason_category' => 'required_if:status,Open',
        ]);

        $log = WoLog::find($this->selectedLogId);
        if ($log) {
            $log->status = $this->status;
            $log->hold_reason_category = $this->status === 'Open' ? $this->hold_reason_category : null;
            $log->hold_remarks = $this->status === 'Open' ? $this->hold_remarks : null;
            $log->save();

            if ($log->dja_id) {
                $dja = \App\Models\DailyJobAssignment::find($log->dja_id);
                if ($dja && $dja->source_spreadsheet_id) {
                    $syncService->pushSync($dja->source_spreadsheet_id, 'DJA', 'K2', $this->status, $this->hold_remarks);
                }
            }
        }

        $this->isModalOpen = false;
        session()->flash('success', 'Status updated successfully.');
    }

    public function render()
    {
        $query = WoLog::query();
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
                $q->where('aircraft_registration', 'like', '%' . $this->search . '%')
                  ->orWhere('status', 'like', '%' . $this->search . '%')
                  ->orWhere('wo_number', 'like', '%' . $this->search . '%')
                  ->orWhere('hold_reason_category', 'like', '%' . $this->search . '%');
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

        return view('livewire.modules.wo-log.index', [
            'logs' => $query->get(),
        ])->layout('components.layouts.app', ['title' => 'WO Logs']);
    }
}
