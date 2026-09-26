<?php

namespace App\Livewire\Master;

use App\Models\Airport;
use Livewire\Component;

class Airports extends Component
{
    public $search = '';

    public $airport_id;

    public $kode;

    public $nama;

    public $kota;

    public $status = 'Aktif';

    public $isEditMode = false;

    public $isOpen = false;

    public function create()
    {
        $this->resetInputFields();
        $this->isOpen = true;
        $this->isEditMode = false;
    }

    public function edit($id)
    {
        $airport = Airport::findOrFail($id);
        $this->airport_id = $id;
        $this->kode = $airport->kode;
        $this->nama = $airport->nama;
        $this->kota = $airport->kota;
        $this->status = $airport->status;

        $this->isOpen = true;
        $this->isEditMode = true;
    }

    public function store()
    {
        $this->validate([
            'kode' => 'required|string|max:10|unique:airports,kode',
            'nama' => 'required|string|max:100',
            'kota' => 'required|string|max:100',
            'status' => 'required|string',
        ]);

        Airport::create([
            'kode' => $this->kode,
            'nama' => $this->nama,
            'kota' => $this->kota,
            'status' => $this->status,
        ]);

        $this->isOpen = false;
        $this->resetInputFields();
        // You might want to dispatch a toast notification here
    }

    public function update()
    {
        $this->validate([
            'kode' => 'required|string|max:10|unique:airports,kode,'.$this->airport_id,
            'nama' => 'required|string|max:100',
            'kota' => 'required|string|max:100',
            'status' => 'required|string',
        ]);

        $airport = Airport::findOrFail($this->airport_id);
        $airport->update([
            'kode' => $this->kode,
            'nama' => $this->nama,
            'kota' => $this->kota,
            'status' => $this->status,
        ]);

        $this->isOpen = false;
        $this->resetInputFields();
    }

    public function delete($id)
    {
        Airport::findOrFail($id)->delete();
    }

    public function close()
    {
        $this->isOpen = false;
        $this->resetInputFields();
    }

    public function resetInputFields()
    {
        $this->airport_id = null;
        $this->kode = '';
        $this->nama = '';
        $this->kota = '';
        $this->status = 'Aktif';
    }

    public function render()
    {
        $airports = Airport::where('kode', 'like', '%'.$this->search.'%')
            ->orWhere('nama', 'like', '%'.$this->search.'%')
            ->orWhere('kota', 'like', '%'.$this->search.'%')
            ->orderBy('id', 'desc')
            ->get();

        return view('livewire.master.airports', [
            'airports' => $airports,
        ])->layout('components.layouts.app');
    }
}
