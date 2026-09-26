<?php

namespace App\Livewire\Modules\CmlLog;

use App\Exports\CmlExport;
use App\Imports\CmlImport;
use App\Livewire\Traits\WithAdvancedFilter;
use App\Models\CmlLog;
use App\Notifications\SystemNotification;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

class Index extends Component
{
    use WithAdvancedFilter, WithFileUploads;

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
            $this->dispatch('notify', ['icon' => 'error', 'message' => 'Terjadi kesalahan saat mengimport data: '.$e->getMessage()]);
            auth()->user()->notify(new SystemNotification(['type' => 'error', 'title' => 'Sistem', 'message' => 'Terjadi kesalahan saat mengimport data: '.$e->getMessage()]));
        }
    }

    public function exportExcel()
    {
        $search = property_exists($this, 'search') ? $this->search : '';
        $dateFilter = property_exists($this, 'dateFilter') ? $this->dateFilter : '';
        $activeTab = property_exists($this, 'activeTab') ? $this->activeTab : '';

        return Excel::download(new CmlExport($search, $dateFilter, $activeTab), 'CmlExport-'.date('Y-m-d').'.xlsx');
    }

    public function render()
    {
        $query = CmlLog::query()->with('dailyJobAssignment');

        $searchFields = ['aircraft_registration', 'status', 'doc_type', 'no_doc', 'station', 'operator', 'ac_status', 'description'];
        $query = $this->scopeAdvancedFilter($query, $searchFields);

        return view('livewire.modules.cml-log.index', [
            'logs' => $query->with('dailyJobAssignment')->get(),
        ])->layout('components.layouts.app', ['title' => 'CML Logs']);
    }
}
