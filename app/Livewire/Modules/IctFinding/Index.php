<?php

namespace App\Livewire\Modules\IctFinding;

use Livewire\Component;
use App\Models\IctFinding;
use App\Imports\IctFindingImport;
use App\Exports\IctFindingExport;
use Maatwebsite\Excel\Facades\Excel;
use Livewire\WithFileUploads;

class Index extends Component
{
    use WithFileUploads;

    public $search = '';
    public $dateFilter = '';
    
    // For import
    public $file;
    public $isImportModalOpen = false;

    // For editing
    public $editingId = null;
    public $editRemarks = '';
    public $editStatus = 'Open';
    public $showEditModal = false;

    public function mount()
    {
        $this->dateFilter = now()->format('Y-m-d'); // Default to today
    }

    public function import()
    {
        $this->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            Excel::import(new IctFindingImport, $this->file);
            session()->flash('success', 'Data ICT Finding berhasil diimport!');
            $this->reset('file');
            $this->isImportModalOpen = false;
        } catch (\Exception $e) {
            session()->flash('error', 'Gagal mengimport data: ' . $e->getMessage());
        }
    }

    public function export()
    {
        return Excel::download(new IctFindingExport($this->search, $this->dateFilter), 'ict-findings.xlsx');
    }

    public function editFinding($id)
    {
        $finding = IctFinding::find($id);
        if ($finding) {
            $this->editingId = $id;
            $this->editRemarks = $finding->remarks;
            $this->editStatus = $finding->status;
            $this->showEditModal = true;
        }
    }

    public function saveFinding()
    {
        $finding = IctFinding::find($this->editingId);
        if ($finding) {
            $finding->update([
                'remarks' => $this->editRemarks,
                'status' => $this->editStatus,
            ]);
            $this->showEditModal = false;
            session()->flash('success', 'Finding berhasil diupdate!');
        }
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
    }

    public function render()
    {
        $query = IctFinding::query();

        if ($this->search) {
            $query->where(function($q) {
                $q->where('no_finding', 'like', '%' . $this->search . '%')
                  ->orWhere('aircraft_registration', 'like', '%' . $this->search . '%')
                  ->orWhere('defect_description', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->dateFilter) {
            $query->whereDate('date', $this->dateFilter);
        }

        $findings = $query->orderBy('date', 'desc')->get();

        return view('livewire.modules.ict-finding.index', [
            'findings' => $findings,
        ])->layout('components.layouts.app');
    }
}
