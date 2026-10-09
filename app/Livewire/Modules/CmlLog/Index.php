<?php

namespace App\Livewire\Modules\CmlLog;

use App\Exports\CmlExport;
use App\Imports\CmlImport;
use App\Livewire\Traits\WithAdvancedFilter;
use App\Livewire\Traits\WithLogTable;
use App\Models\CmlLog;
use App\Notifications\SystemNotification;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class Index extends Component
{
    use WithAdvancedFilter, WithFileUploads, WithLogTable, WithPagination;

    public $file;

    public $isImportModalOpen = false;

    public function mount()
    {
        $this->mountWithAdvancedFilter();
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
        // Export exactly what the filters on screen select
        return Excel::download(
            new CmlExport($this->search, $this->dateStart, '', $this->dateEnd, $this->filterStation),
            'CmlExport-'.date('Y-m-d').'.xlsx'
        );
    }

    public function render()
    {
        $query = CmlLog::query()->with('dailyJobAssignment');

        $searchFields = ['aircraft_registration', 'status', 'doc_type', 'no_doc', 'station', 'operator', 'ac_status', 'description'];
        $query = $this->scopeAdvancedFilter($query, $searchFields);

        return view('livewire.modules.cml-log.index', [
            'logs' => $query->paginate($this->perPage),
        ])->layout('components.layouts.app', ['title' => 'CML Logs']);
    }
}
