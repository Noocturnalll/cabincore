<?php

namespace App\Livewire\Master;

use App\Models\Division;
use App\Models\JobCategory;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Categories extends Component
{
    use WithPagination;

    public $search = '';

    public $category_id;

    public $kode;

    public $nama;

    public $divisi;

    public $aktif = 1;

    public $isEditMode = false;

    public $isOpen = false;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    /** Active divisions, plus any value an existing category still carries so old rows stay editable. */
    public function divisionOptions(): array
    {
        return Division::where('status', 'Aktif')->orderBy('name')->pluck('name')
            ->merge(JobCategory::query()->distinct()->pluck('divisi'))->filter()->unique()->values()->all();
    }

    public function create()
    {
        $this->resetInputFields();
        $this->isOpen = true;
        $this->isEditMode = false;
    }

    public function edit($id)
    {
        $category = JobCategory::findOrFail($id);
        $this->category_id = $id;
        $this->kode = $category->kode;
        $this->nama = $category->nama;
        $this->divisi = $category->divisi;
        $this->aktif = $category->aktif;

        $this->isOpen = true;
        $this->isEditMode = true;
    }

    public function store()
    {
        $this->kode = strtoupper(trim((string) $this->kode));
        $this->validate([
            'kode' => 'required|string|max:50|unique:job_categories,kode',
            'nama' => 'required|string|max:255',
            'divisi' => ['required', Rule::in($this->divisionOptions())],
            'aktif' => 'required|boolean',
        ]);

        JobCategory::create([
            'kode' => $this->kode,
            'nama' => $this->nama,
            'divisi' => $this->divisi,
            'aktif' => $this->aktif,
        ]);

        $this->isOpen = false;
        $this->resetInputFields();
        $this->dispatch('notify', ['icon' => 'success', 'message' => 'Kategori berhasil ditambahkan.']);
    }

    public function update()
    {
        $this->kode = strtoupper(trim((string) $this->kode));
        $this->validate([
            'kode' => 'required|string|max:50|unique:job_categories,kode,'.$this->category_id,
            'nama' => 'required|string|max:255',
            'divisi' => ['required', Rule::in($this->divisionOptions())],
            'aktif' => 'required|boolean',
        ]);

        $category = JobCategory::findOrFail($this->category_id);
        $category->update([
            'kode' => $this->kode,
            'nama' => $this->nama,
            'divisi' => $this->divisi,
            'aktif' => $this->aktif,
        ]);

        $this->isOpen = false;
        $this->resetInputFields();
        $this->dispatch('notify', ['icon' => 'success', 'message' => 'Kategori berhasil diperbarui.']);
    }

    public function delete($id)
    {
        JobCategory::findOrFail($id)->delete();
        $this->dispatch('notify', ['icon' => 'success', 'message' => 'Kategori berhasil dihapus.']);
    }

    public function close()
    {
        $this->isOpen = false;
        $this->resetInputFields();
    }

    public function resetInputFields()
    {
        $this->category_id = null;
        $this->kode = '';
        $this->nama = '';
        $this->divisi = '';
        $this->aktif = 1;
        $this->resetValidation();
    }

    public function render()
    {
        $categories = JobCategory::query()
            ->when($this->search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('kode', 'like', '%'.$this->search.'%')
                ->orWhere('nama', 'like', '%'.$this->search.'%')
                ->orWhere('divisi', 'like', '%'.$this->search.'%')))
            ->orderBy('divisi')
            ->orderBy('kode')
            ->paginate(15);

        return view('livewire.master.categories', [
            'categories' => $categories,
        ])->layout('components.layouts.app', ['title' => 'Master Kategori Pekerjaan']);
    }
}
