<?php

namespace App\Livewire\Modules\Ims\Master;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Ims\Supplier;

class Suppliers extends Component
{
    use WithPagination;

    public $search = '';
    public $isOpen = false;
    public $isEdit = false;
    public $editId = null;

    public $form = [
        'code' => '',
        'name' => '',
        'contact_person' => '',
        'phone' => '',
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
        $record = Supplier::findOrFail($id);
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
        'form.contact_person' => 'required|string|max:255',
        'form.phone' => 'required|string|max:255',
        'form.is_active' => 'boolean'
        ]);

        if ($this->isEdit) {
            $record = Supplier::findOrFail($this->editId);
            $record->update($this->form);
            session()->flash('success', 'Data berhasil diperbarui.');
        } else {
            Supplier::create($this->form);
            session()->flash('success', 'Data berhasil ditambahkan.');
        }

        $this->isOpen = false;
    }

    public function delete($id)
    {
        $record = Supplier::findOrFail($id);
        $record->delete();
        session()->flash('success', 'Data berhasil dihapus.');
    }

    public function resetForm()
    {
        $this->form['code'] = '';
        $this->form['name'] = '';
        $this->form['contact_person'] = '';
        $this->form['phone'] = '';
        $this->form['is_active'] = true;
        $this->editId = null;
    }

    public function render()
    {
        $query = Supplier::query();
        
        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('code', 'like', '%' . $this->search . '%');
        }

        return view('livewire.modules.ims.master.' . strtolower('Suppliers'), [
            'records' => $query->paginate(15)
        ])->layout('components.layouts.app', ['title' => 'Data Supplier - IMS Master']);
    }
}