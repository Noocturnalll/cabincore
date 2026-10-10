<?php

namespace App\Livewire\Master;

use App\Models\Division;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class Divisions extends Component
{
    use WithPagination;

    public function updatingSearch()
    {
        $this->resetPage();
    }

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
        $this->dispatch('notify', ['icon' => 'success', 'message' => 'Divisi berhasil ditambahkan.']);
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
        $this->dispatch('notify', ['icon' => 'success', 'message' => 'Divisi berhasil diperbarui.']);
    }

    public function delete($id)
    {
        $division = Division::findOrFail($id);
        $userCount = $division->users()->count();

        if ($userCount > 0) {
            User::where('division_id', $division->id)->update(['division_id' => null]);
        }

        $division->delete();
        $msg = $userCount > 0
            ? "Divisi {$division->name} dihapus ({$userCount} pengguna dilepas dari divisi)."
            : "Divisi {$division->name} berhasil dihapus.";
        $this->dispatch('notify', ['icon' => 'success', 'message' => $msg]);
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
        $this->resetValidation();
    }

    public function render()
    {
        $divisions = Division::where('name', 'like', '%'.$this->search.'%')
            ->withCount('users')
            ->orderBy('name', 'asc')
            ->paginate(15);

        return view('livewire.master.divisions', [
            'divisions' => $divisions,
        ])->layout('components.layouts.app', ['title' => 'Master Divisi']);
    }
}
