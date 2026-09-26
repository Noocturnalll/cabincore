<?php

namespace App\Livewire\Modules\AircraftCleaning;

use App\Models\AircraftCleaning;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use App\Notifications\SystemNotification;

class DailyReport extends Component
{
    use WithFileUploads, WithPagination;

    public $activeTab = 'Transit'; // Tabs: Transit, General, DCI, DCE
    public $isImportModalOpen = false;
    public $file;
    public $search = '';
    public $dateFilter = '';

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function importData()
    {
        $this->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            Excel::import(new \App\Imports\AircraftCleaningImport, $this->file);
            $this->isImportModalOpen = false;
            $this->file = null;
            $this->dispatch('notify', ['icon' => 'success', 'message' => 'Data Aircraft Cleaning berhasil diimport.']);
            auth()->user()->notify(new SystemNotification(['type' => 'success', 'title' => 'Sistem', 'message' => 'Data Aircraft Cleaning berhasil diimport.']));
        } catch (\Exception $e) {
            $this->dispatch('notify', ['icon' => 'error', 'message' => 'Terjadi kesalahan saat mengimport data: '.$e->getMessage()]);
            auth()->user()->notify(new SystemNotification(['type' => 'error', 'title' => 'Sistem', 'message' => 'Terjadi kesalahan saat mengimport data: '.$e->getMessage()]));
        }
    }

    public function exportExcel()
    {
        return Excel::download(new \App\Exports\AircraftCleaningExport($this->search, $this->dateFilter, $this->activeTab), 'AircraftCleaningExport-'.date('Y-m-d').'.xlsx');
    }

    public function render()
    {
        $query = AircraftCleaning::query();
        
        $activeDate = now()->hour >= 18 ? now()->format('Y-m-d') : now()->subDays(1)->format('Y-m-d');

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
        } else {
            // Default to today if needed, for now we will show all or active date
        }

        $query->orderBy('date', 'desc');

        if ($this->activeTab) {
            $query->where('type', $this->activeTab);

            // DCI and DCE only show 'Closed' in Daily Report
            if (in_array($this->activeTab, ['DCI', 'DCE'])) {
                $query->where('status', 'Closed');
            }
        }

        return view('livewire.modules.aircraft-cleaning.daily-report', [
            'logs' => $query->get(),
        ])->layout('components.layouts.app', ['title' => 'Daily Report - Aircraft Cleaning']);
    }
}
