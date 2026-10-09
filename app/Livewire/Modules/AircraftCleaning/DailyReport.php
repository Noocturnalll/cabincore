<?php

namespace App\Livewire\Modules\AircraftCleaning;

use App\Exports\AircraftCleaningExport;
use App\Imports\AircraftCleaningImport;
use App\Livewire\Traits\WithLogTable;
use App\Models\AircraftCleaning;
use App\Notifications\SystemNotification;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class DailyReport extends Component
{
    use WithFileUploads, WithLogTable, WithPagination;

    public $activeTab = 'Transit'; // Tabs: Transit, General, DCI, DCE

    public $isImportModalOpen = false;

    public $file;

    public $search = '';

    public $dateFilter = '';

    public function importData()
    {
        $this->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            Excel::import(new AircraftCleaningImport, $this->file);
            $this->isImportModalOpen = false;
            $this->file = null;
            $this->dispatch('notify', ['icon' => 'success', 'message' => 'Data Aircraft Cleaning berhasil diimport.']);
            auth()->user()->notify(new SystemNotification(['type' => 'success', 'title' => 'Sistem', 'message' => 'Data Aircraft Cleaning berhasil diimport.']));
        } catch (\Exception $e) {
            session()->flash('error', 'Terjadi kesalahan saat mengimport data: '.$e->getMessage());
            $this->dispatch('notify', ['icon' => 'error', 'message' => 'Terjadi kesalahan saat mengimport data: '.$e->getMessage()]);
            auth()->user()->notify(new SystemNotification(['type' => 'error', 'title' => 'Sistem', 'message' => 'Terjadi kesalahan saat mengimport data: '.$e->getMessage()]));
        }
    }

    public function exportExcel()
    {
        return Excel::download(new AircraftCleaningExport($this->search, $this->dateFilter, $this->activeTab), 'AircraftCleaningExport-'.date('Y-m-d').'.xlsx');
    }

    public function render()
    {
        $query = AircraftCleaning::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('aircraft_registration', 'like', '%'.$this->search.'%')
                    ->orWhere('status', 'like', '%'.$this->search.'%')
                    ->orWhere('station', 'like', '%'.$this->search.'%')
                    ->orWhere('operator', 'like', '%'.$this->search.'%')
                    ->orWhere('remarks', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->dateFilter) {
            $query->whereDate('date', $this->dateFilter);
        }

        $query->orderByDesc('date')->orderByDesc('id');

        if ($this->activeTab) {
            $query->where('type', $this->activeTab);

            // DCI and DCE only show 'Closed' in Daily Report
            if (in_array($this->activeTab, ['DCI', 'DCE'])) {
                $query->where('status', 'Closed');
            }
        }

        return view('livewire.modules.aircraft-cleaning.daily-report', [
            'logs' => $query->paginate($this->perPage),
        ])->layout('components.layouts.app', ['title' => 'Daily Report - Aircraft Cleaning']);
    }
}
