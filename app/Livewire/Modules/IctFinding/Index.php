<?php

namespace App\Livewire\Modules\IctFinding;

use App\Exports\IctFindingExport;
use App\Imports\IctFindingImport;
use App\Livewire\Traits\WithLogTable;
use App\Models\IctFinding;
use App\Notifications\SystemNotification;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class Index extends Component
{
    use WithFileUploads, WithLogTable, WithPagination;

    public $search = '';

    public $dateFilter = '';

    /** '' = semua, 'Open', 'Closed' */
    public $statusFilter = '';

    // dipakai oleh WithLogTable::setTab()
    public $activeTab = '';

    // For import
    public $file;

    public $isImportModalOpen = false;

    // For editing
    public $editingId = null;

    public $editRemarks = '';

    public $editStatus = 'Open';

    public $showEditModal = false;

    public function import()
    {
        $this->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            Excel::import(new IctFindingImport, $this->file);
            $this->dispatch('notify', ['icon' => 'success', 'message' => 'Data ICT Finding berhasil diimport!']);
            auth()->user()->notify(new SystemNotification(['type' => 'success', 'title' => 'Sistem', 'message' => 'Data ICT Finding berhasil diimport!']));
            $this->reset('file');
            $this->isImportModalOpen = false;
        } catch (\Exception $e) {
            $this->dispatch('notify', ['icon' => 'error', 'message' => 'Gagal mengimport data: '.$e->getMessage()]);
            auth()->user()->notify(new SystemNotification(['type' => 'error', 'title' => 'Sistem', 'message' => 'Gagal mengimport data: '.$e->getMessage()]));
        }
    }

    public function export()
    {
        return Excel::download(new IctFindingExport($this->search, $this->dateFilter, $this->statusFilter), 'ict-findings.xlsx');
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

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function saveFinding()
    {
        $this->validate([
            'editStatus' => 'required|in:Open,Closed',
            'editRemarks' => 'nullable|string|max:2000',
        ]);

        $finding = IctFinding::find($this->editingId);
        if ($finding) {
            $finding->update([
                'remarks' => $this->editRemarks,
                'status' => $this->editStatus,
            ]);
            $this->showEditModal = false;
            $this->dispatch('notify', ['icon' => 'success', 'message' => 'Finding berhasil diupdate!']);
            auth()->user()->notify(new SystemNotification(['type' => 'success', 'title' => 'Sistem', 'message' => 'Finding berhasil diupdate!']));
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
            $query->where(function ($q) {
                $q->where('no_finding', 'like', '%'.$this->search.'%')
                    ->orWhere('aircraft_registration', 'like', '%'.$this->search.'%')
                    ->orWhere('defect_description', 'like', '%'.$this->search.'%')
                    ->orWhere('operator', 'like', '%'.$this->search.'%')
                    ->orWhere('remarks', 'like', '%'.$this->search.'%')
                    ->orWhere('status', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->dateFilter) {
            $query->whereDate('date', $this->dateFilter);
        }

        if (in_array($this->statusFilter, ['Open', 'Closed'], true)) {
            $query->where('status', $this->statusFilter);
        }

        // Open lebih dulu (perlu tindak lanjut), lalu terbaru
        $findings = $query
            ->orderByRaw("CASE WHEN status = 'Open' THEN 0 ELSE 1 END")
            ->orderBy('date', 'desc')
            ->orderByDesc('id')
            ->paginate($this->perPage);

        return view('livewire.modules.ict-finding.index', [
            'findings' => $findings,
        ])->layout('components.layouts.app', ['title' => 'Findings ICT PI']);
    }
}
