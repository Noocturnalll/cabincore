<?php

namespace App\Livewire\Modules\NsrdiOverdue;

use App\Exports\NsrdiOverdueExport;
use App\Imports\NsrdiOverdueImport;
use App\Models\NsrdiLog;
use App\Models\NsrdiOverdue;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class Index extends Component
{
    use WithFileUploads, WithPagination;

    public $isImportModalOpen = false;

    public $isEditModalOpen = false;

    public $selectedId = null;

    public $editStatus = 'Open';

    public $editStatusFinal = '';

    public $editRemarks = '';

    public $file;

    public $search = '';

    public function openEditModal($id)
    {
        $overdue = NsrdiOverdue::findOrFail($id);
        $this->selectedId = $overdue->id;
        $this->editStatus = $overdue->status ?? 'Open';
        $this->editStatusFinal = $overdue->status_final ?? '';
        $this->editRemarks = $overdue->remarks ?? '';
        $this->isEditModalOpen = true;
    }

    public function updateOverdue()
    {
        if (! $this->selectedId) {
            return;
        }

        $overdue = NsrdiOverdue::findOrFail($this->selectedId);

        $data = [
            'status' => $this->editStatus,
            'status_final' => $this->editStatusFinal,
            'remarks' => $this->editRemarks,
        ];

        if ($this->editStatus === 'Closed' && ! $overdue->closed_at) {
            $data['closed_at'] = now();
        } elseif ($this->editStatus === 'Open') {
            $data['closed_at'] = null;
        }

        $overdue->update($data);

        $this->isEditModalOpen = false;
        $this->selectedId = null;
        $this->dispatch('notify', ['icon' => 'success', 'message' => 'Data NSRDI Overdue berhasil diperbarui.']);
    }

    public function importData()
    {
        $this->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            // Kita truncate (kosongkan) dulu atau tidak? Biasanya tabel overdue di-refresh
            // Tapi kita biarkan saja nambah, atau bisa ditambahkan opsi truncate nanti
            Excel::import(new NsrdiOverdueImport, $this->file);

            $this->isImportModalOpen = false;
            $this->file = null;
            $this->dispatch('notify', ['icon' => 'success', 'message' => 'Data NSRDI Overdue berhasil diimport dan disingkronkan.']);
        } catch (\Exception $e) {
            session()->flash('error', 'Terjadi kesalahan saat mengimport data: '.$e->getMessage());
        }
    }

    public function syncData()
    {
        $overdues = NsrdiOverdue::where('status', 'Open')->get();
        $syncedCount = 0;

        foreach ($overdues as $overdue) {
            $isClosed = NsrdiLog::where('nsrdi_number', $overdue->nsrdi_number)
                ->where(function ($query) {
                    $query->where('status', 'Closed')
                        ->orWhere('status', 'CLOSED');
                })
                ->exists();

            if ($isClosed) {
                $overdue->update(['status' => 'Closed']);
                $syncedCount++;
            }
        }

        $this->dispatch('notify', ['icon' => 'success', 'message' => "$syncedCount data berhasil disingkronkan menjadi Closed."]);
    }

    public function exportData()
    {
        return Excel::download(
            new NsrdiOverdueExport($this->search),
            'NsrdiOverdue-'.date('Y-m-d').'.xlsx'
        );
    }

    public function render()
    {
        $query = NsrdiOverdue::query();

        if ($this->search) {
            $query->where('aircraft_registration', 'like', '%'.$this->search.'%')
                ->orWhere('nsrdi_number', 'like', '%'.$this->search.'%')
                ->orWhere('description', 'like', '%'.$this->search.'%')
                ->orWhere('operator', 'like', '%'.$this->search.'%')
                ->orWhere('mddr', 'like', '%'.$this->search.'%');
        }

        return view('livewire.modules.nsrdi-overdue.index', [
            'logs' => $query->latest()->paginate(15),
        ])->layout('components.layouts.app', ['title' => 'NSRDI Overdue']);
    }
}
