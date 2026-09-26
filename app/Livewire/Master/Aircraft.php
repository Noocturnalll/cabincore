<?php

namespace App\Livewire\Master;

use App\Models\Aircraft as AircraftModel;
use Livewire\Component;
use Livewire\WithPagination;

class Aircraft extends Component
{
    use WithPagination;

    public $search = '';

    public $aircraft_id;

    public $registration;

    public $tipe;

    public $maskapai;

    public $status = 'Aktif';

    public $isEditMode = false;

    public $isOpen = false;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function create()
    {
        $this->resetInputFields();
        $this->isOpen = true;
        $this->isEditMode = false;
    }

    public function edit($id)
    {
        $aircraft = AircraftModel::findOrFail($id);
        $this->aircraft_id = $id;
        $this->registration = $aircraft->registration;
        $this->tipe = $aircraft->tipe;
        $this->maskapai = $aircraft->maskapai;
        $this->status = $aircraft->status;

        $this->isOpen = true;
        $this->isEditMode = true;
    }

    public function store()
    {
        $this->validate([
            'registration' => 'required|string|max:20|unique:aircraft,registration',
            'tipe' => 'required|string|max:100',
            'maskapai' => 'required|string|max:100',
            'status' => 'required|string',
        ]);

        AircraftModel::create([
            'registration' => $this->registration,
            'tipe' => $this->tipe,
            'maskapai' => $this->maskapai,
            'status' => $this->status,
        ]);

        $this->isOpen = false;
        $this->resetInputFields();
    }

    public function update()
    {
        $this->validate([
            'registration' => 'required|string|max:20|unique:aircraft,registration,'.$this->aircraft_id,
            'tipe' => 'required|string|max:100',
            'maskapai' => 'required|string|max:100',
            'status' => 'required|string',
        ]);

        $aircraft = AircraftModel::findOrFail($this->aircraft_id);
        $aircraft->update([
            'registration' => $this->registration,
            'tipe' => $this->tipe,
            'maskapai' => $this->maskapai,
            'status' => $this->status,
        ]);

        $this->isOpen = false;
        $this->resetInputFields();
    }

    public function delete($id)
    {
        AircraftModel::findOrFail($id)->delete();
    }

    public function close()
    {
        $this->isOpen = false;
        $this->resetInputFields();
    }

    public function resetInputFields()
    {
        $this->aircraft_id = null;
        $this->registration = '';
        $this->tipe = '';
        $this->maskapai = '';
        $this->status = 'Aktif';
    }

    public function render()
    {
        $aircrafts = AircraftModel::where('registration', 'like', '%'.$this->search.'%')
            ->orWhere('tipe', 'like', '%'.$this->search.'%')
            ->orWhere('maskapai', 'like', '%'.$this->search.'%')
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('livewire.master.aircraft', [
            'aircrafts' => $aircrafts,
        ])->layout('components.layouts.app');
    }
}
