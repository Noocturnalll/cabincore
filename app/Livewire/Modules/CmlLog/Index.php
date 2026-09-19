<?php

namespace App\Livewire\Modules\CmlLog;

use App\Models\CmlLog;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\CmlImport;

class Index extends Component
{
    use WithFileUploads;

    public $file;
    public $isImportModalOpen = false;
    
    public $search = '';
    public $dateFilter = '';

    public function importCml()
    {
        $this->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240', // 10MB Max
        ]);

        try {
            Excel::import(new CmlImport, $this->file);
            $this->isImportModalOpen = false;
            $this->file = null;
            session()->flash('message', 'Data CML berhasil diimport.');
        } catch (\Exception $e) {
            session()->flash('error', 'Terjadi kesalahan saat mengimport data: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $query = CmlLog::query();
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
                  ->orWhere('doc_type', 'like', '%' . $this->search . '%')
                  ->orWhere('no_doc', 'like', '%' . $this->search . '%');
            });
        }
        
        if ($this->dateFilter) {
            $query->whereDate('date', $this->dateFilter);
        }
        
        $query->orderBy('date', 'asc');

        return view('livewire.modules.cml-log.index', [
            'logs' => $query->get(),
        ])->layout('components.layouts.app', ['title' => 'CML Logs']);
    }
}
