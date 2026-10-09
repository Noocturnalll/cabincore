<?php

namespace App\Livewire\Master;

use App\Models\Position;
use Livewire\Component;
use Livewire\WithPagination;

class Positions extends Component
{
    use WithPagination;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public $search = '';

    public $position_id;

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
        $position = Position::findOrFail($id);
        $this->position_id = $id;
        $this->name = $position->name;
        $this->status = $position->status;

        $this->isOpen = true;
        $this->isEditMode = true;
    }

    public function store()
    {
        $this->validate([
            'name' => 'required|string|max:100|unique:positions,name',
            'status' => 'required|string|in:Aktif,Nonaktif',
        ]);

        Position::create([
            'name' => $this->name,
            'status' => $this->status,
        ]);

        $this->isOpen = false;
        $this->resetInputFields();
        $this->dispatch('notify', ['icon' => 'success', 'message' => 'Jabatan berhasil ditambahkan.']);
    }

    public function update()
    {
        $this->validate([
            'name' => 'required|string|max:100|unique:positions,name,'.$this->position_id,
            'status' => 'required|string|in:Aktif,Nonaktif',
        ]);

        $position = Position::findOrFail($this->position_id);
        $position->update([
            'name' => $this->name,
            'status' => $this->status,
        ]);

        $this->isOpen = false;
        $this->resetInputFields();
        $this->dispatch('notify', ['icon' => 'success', 'message' => 'Jabatan berhasil diperbarui.']);
    }

    public function delete($id)
    {
        $position = Position::findOrFail($id);

        if ($position->users()->count() > 0) {
            $this->dispatch('notify', ['icon' => 'error', 'message' => "Gagal menghapus: Jabatan ini masih digunakan oleh {$position->users()->count()} pengguna.", 'timer' => 6000]);

            return;
        }

        $position->delete();
        $this->dispatch('notify', ['icon' => 'success', 'message' => 'Jabatan berhasil dihapus.']);
    }

    public function close()
    {
        $this->isOpen = false;
        $this->resetInputFields();
    }

    public function resetInputFields()
    {
        $this->position_id = null;
        $this->name = '';
        $this->status = 'Aktif';
        $this->resetValidation();
    }

    public function render()
    {
        $positions = Position::where('name', 'like', '%'.$this->search.'%')
            ->withCount('users')
            ->orderBy('name', 'asc')
            ->paginate(15);

        return view('livewire.master.positions', [
            'positions' => $positions,
        ])->layout('components.layouts.app', ['title' => 'Master Jabatan']);
    }
}
