<?php

namespace App\Livewire\Modules\Ims\Master;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Ims\Category;

class Categories extends Component
{
    use WithPagination;

    public $search = '';
    public $isOpen = false;
    public $isEdit = false;
    public $editId = null;

    public $form = [
        'code' => '',
        'name' => '',
        'ata_chapter' => '',
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
        $record = Category::findOrFail($id);
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
        'form.ata_chapter' => 'required|string|max:255',
        'form.is_active' => 'boolean'
        ]);

        if ($this->isEdit) {
            $record = Category::findOrFail($this->editId);
            $record->update($this->form);
            session()->flash('success', 'Data berhasil diperbarui.');
        } else {
            Category::create($this->form);
            session()->flash('success', 'Data berhasil ditambahkan.');
        }

        $this->isOpen = false;
    }

    public function delete($id)
    {
        $record = Category::findOrFail($id);
        $record->delete();
        session()->flash('success', 'Data berhasil dihapus.');
    }

    public function resetForm()
    {
        $this->form['code'] = '';
        $this->form['name'] = '';
        $this->form['ata_chapter'] = '';
        $this->form['is_active'] = true;
        $this->editId = null;
    }

    public function render()
    {
        $query = Category::query();
        
        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('code', 'like', '%' . $this->search . '%');
        }

        return view('livewire.modules.ims.master.' . strtolower('Categories'), [
            'records' => $query->paginate(15)
        ])->layout('components.layouts.app', ['title' => 'Kategori Barang - IMS Master']);
    }
}