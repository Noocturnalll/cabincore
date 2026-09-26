<?php

namespace App\Livewire\Modules\AircraftCleaning;

use App\Models\AircraftCleaning;
use App\Notifications\SystemNotification;
use Livewire\Component;
use Livewire\WithPagination;

class Interior extends Component
{
    use WithPagination;

    public $search = '';

    public $isModalOpen = false;

    public $deleteId = null;

    // Form fields
    public $cleaningId;

    public $aircraft_registration;

    public $station;

    public $date;

    public $shift;

    public $status = 'Aktif';

    public $remarks;

    public $operator;

    protected $rules = [
        'aircraft_registration' => 'required|string|max:255',
        'station' => 'required|string|max:50',
        'date' => 'required|date',
        'shift' => 'required|string|max:50',
        'status' => 'required|string',
        'remarks' => 'nullable|string',
        'operator' => 'nullable|string|max:255',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();

        $cleanings = AircraftCleaning::where('type', 'DCI')
            ->when($user->jabatan !== 'Super Admin' && $user->jabatan !== 'Manager', function ($query) use ($user) {
                $query->where('station', $user->station);
            })
            ->where(function ($query) {
                $query->where('aircraft_registration', 'like', '%'.$this->search.'%')
                    ->orWhere('operator', 'like', '%'.$this->search.'%')
                    ->orWhere('station', 'like', '%'.$this->search.'%')
                    ->orWhere('shift', 'like', '%'.$this->search.'%')
                    ->orWhere('status', 'like', '%'.$this->search.'%')
                    ->orWhere('remarks', 'like', '%'.$this->search.'%');
            })
            ->orderBy('date', 'desc')
            ->paginate(10);

        return view('livewire.modules.aircraft-cleaning.interior', [
            'cleanings' => $cleanings,
        ])->layout('components.layouts.app', ['title' => 'Interior Cleaning']);
    }

    public function create()
    {
        $this->resetInputFields();
        $this->station = auth()->user()->station ?? '';
        $this->date = date('Y-m-d');
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        $this->resetInputFields();
        $record = AircraftCleaning::findOrFail($id);
        $this->cleaningId = $id;
        $this->aircraft_registration = $record->aircraft_registration;
        $this->station = $record->station;
        $this->date = $record->date;
        $this->shift = $record->shift;
        $this->status = $record->status;
        $this->remarks = $record->remarks;
        $this->operator = $record->operator;

        $this->isModalOpen = true;
    }

    public function save()
    {
        $this->validate();

        AircraftCleaning::updateOrCreate(
            ['id' => $this->cleaningId],
            [
                'aircraft_registration' => $this->aircraft_registration,
                'station' => $this->station,
                'date' => $this->date,
                'shift' => $this->shift,
                'type' => 'DCI',
                'status' => $this->status,
                'remarks' => $this->remarks,
                'operator' => $this->operator,
            ]
        );

        $this->dispatch('notify', ['icon' => 'success', 'message' => 'Data Interior Cleaning berhasil disimpan!']);
        auth()->user()->notify(new SystemNotification(['type' => 'success', 'title' => 'Sistem', 'message' => 'Data Interior Cleaning berhasil disimpan!']));
        $this->closeModal();
    }

    public function deleteConfirm($id)
    {
        $this->deleteId = $id;
    }

    public function delete()
    {
        if ($this->deleteId) {
            AircraftCleaning::find($this->deleteId)->delete();
            $this->deleteId = null;
            $this->dispatch('notify', ['icon' => 'success', 'message' => 'Data berhasil dihapus!']);
            auth()->user()->notify(new SystemNotification(['type' => 'success', 'title' => 'Sistem', 'message' => 'Data berhasil dihapus!']));
        }
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->resetInputFields();
    }

    private function resetInputFields()
    {
        $this->cleaningId = null;
        $this->aircraft_registration = '';
        $this->station = auth()->user()->station ?? '';
        $this->date = '';
        $this->shift = '';
        $this->status = 'Aktif';
        $this->remarks = '';
        $this->operator = '';
        $this->resetValidation();
    }
}
