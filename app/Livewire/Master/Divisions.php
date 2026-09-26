<?php

namespace App\Livewire\Master;

use App\Models\Division;
use Livewire\Component;

class Divisions extends Component
{
    public $search = '';

    public $division_id;

    public $name;

    public $status = 'Aktif';

    public $isOpen = false;

    public $isEditMode = false;

    public function create()
    {
        $this->resetInputFields();
        $this->isOpen = true;
        $this->isEditMode = false;
    }

    public function edit($id)
    {
        $division = Division::findOrFail($id);
        $this->division_id = $id;
        $this->name = $division->name;
        $this->status = $division->status;

        $this->isOpen = true;
        $this->isEditMode = true;
    }

    public function store()
    {
        $this->validate([
            'name' => 'required|string|max:100|unique:divisions,name',
            'status' => 'required|string|in:Aktif,Nonaktif',
        ]);

        Division::create([
            'name' => $this->name,
            'status' => $this->status,
        ]);

        $this->isOpen = false;
        $this->resetInputFields();
    }

    public function update()
    {
        $this->validate([
            'name' => 'required|string|max:100|unique:divisions,name,'.$this->division_id,
            'status' => 'required|string|in:Aktif,Nonaktif',
        ]);

        $division = Division::findOrFail($this->division_id);
        $division->update([
            'name' => $this->name,
            'status' => $this->status,
        ]);

        $this->isOpen = false;
        $this->resetInputFields();
    }

    public function delete($id)
    {
        $division = Division::findOrFail($id);

        if ($division->users()->count() > 0) {
            $this->js("alert('Gagal menghapus: Divisi ini masih digunakan oleh {$division->users()->count()} pengguna aktif.');");

            return;
        }

        $division->delete();
    }

    public function close()
    {
        $this->isOpen = false;
        $this->resetInputFields();
    }

    public function resetInputFields()
    {
        $this->division_id = null;
        $this->name = '';
        $this->status = 'Aktif';
    }

    public function render()
    {
        $divisions = Division::where('name', 'like', '%'.$this->search.'%')
            ->orderBy('name', 'asc')
            ->get();

        return view('livewire.master.divisions', [
            'divisions' => $divisions,
        ])->layout('components.layouts.app', ['title' => 'Master Divisi']);
    }
}
