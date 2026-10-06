<?php

namespace App\Livewire\Modules\Ims\Master;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Ims\Location;

class Locations extends Component
{
    use WithPagination;

    public $search = '';
    public $isOpen = false;
    public $isEdit = false;
    public $editId = null;

    public $form = [
        'code' => '',
        'name' => '',
        'type' => '',
        'is_active' => true,
    ];

    public function updatingSearch() { $this->resetPage(); }

    public function create()
    {
        $this->resetForm();
        $this->isEdit = false;
        $this->isOpen = true;
    }

    public function edit($id)
    {
        $this->resetForm();
        $record = Location::findOrFail($id);
        $this->editId = $id;
        $this->isEdit = true;
        
        foreach (array_keys($this->form) as $key) {
            $this->form[$key] = $record->{$key};
        }
        
        $this->isOpen = true;
    }

    public function save()
    {
        $this->validate([
            'form.code' => 'required|string|max:255',
        'form.name' => 'required|string|max:255',
        'form.type' => 'required|in:warehouse,rack,shelf,repair_area,quarantine',
        'form.is_active' => 'boolean'
        ]);

        if ($this->isEdit) {
            $record = Location::findOrFail($this->editId);
            $record->update($this->form);
            session()->flash('success', 'Data berhasil diperbarui.');
        } else {
            Location::create($this->form);
            session()->flash('success', 'Data berhasil ditambahkan.');
        }

        $this->isOpen = false;
    }

    public function delete($id)
    {
        $record = Location::findOrFail($id);
        $record->delete();
        session()->flash('success', 'Data berhasil dihapus.');
    }

    public function resetForm()
    {
        $this->form['code'] = '';
        $this->form['name'] = '';
        $this->form['type'] = '';
        $this->form['is_active'] = true;
        $this->editId = null;
    }

    public function render()
    {
        $query = Location::query();
        
        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('code', 'like', '%' . $this->search . '%');
        }

        return view('livewire.modules.ims.master.' . strtolower('Locations'), [
            'records' => $query->paginate(15)
        ])->layout('components.layouts.app', ['title' => 'Lokasi & Rak - IMS Master']);
    }
}