<?php

namespace App\Livewire\Modules\Ims\Master;

use App\Models\Ims\AircraftType;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class AircraftTypes extends Component
{
    public function mount()
    {
        abort_unless(auth()->user()?->can('ims.master.manage'), 403, 'Anda tidak memiliki akses ke halaman ini.');
    }

    use WithPagination;

    public $search = '';

    public $isOpen = false;

    public $isEdit = false;

    public $editId = null;

    public $form = [
        'code' => '',
        'manufacturer' => '',
        'model' => '',
        'is_active' => true,
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function create()
    {
        $this->resetForm();
        $this->isEdit = false;
        $this->isOpen = true;
    }

    public function edit($id)
    {
        $this->resetForm();
        $record = AircraftType::findOrFail($id);
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
            'form.manufacturer' => 'required|string|max:255',
            'form.model' => 'required|string|max:255',
            'form.is_active' => 'boolean',
        ]);

        if ($this->isEdit) {
            $record = AircraftType::findOrFail($this->editId);
            $record->update($this->form);
            session()->flash('success', 'Data berhasil diperbarui.');
        } else {
            AircraftType::create($this->form);
            session()->flash('success', 'Data berhasil ditambahkan.');
        }

        $this->isOpen = false;
    }

    public function delete($id)
    {
        $record = AircraftType::findOrFail($id);

        if (DB::table('ims_item_aircraft_type')->where('aircraft_type_id', $record->id)->exists()) {
            session()->flash('error', 'Tipe pesawat masih dikaitkan dengan barang. Nonaktifkan saja.');

            return;
        }

        try {
            $record->delete();
            session()->flash('success', 'Data berhasil dihapus.');
        } catch (QueryException $e) {
            // still referenced by items / stock / transactions
            session()->flash('error', 'Data tidak dapat dihapus karena masih dipakai. Nonaktifkan saja.');
        }
    }

    public function resetForm()
    {
        $this->form['code'] = '';
        $this->form['manufacturer'] = '';
        $this->form['model'] = '';
        $this->form['is_active'] = true;
        $this->editId = null;
    }

    public function render()
    {
        $query = AircraftType::query();

        if ($this->search) {
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$this->search.'%')
                ->orWhere('code', 'like', '%'.$this->search.'%'));
        }

        return view('livewire.modules.ims.master.'.strtolower('AircraftTypes'), [
            'records' => $query->paginate(15),
        ])->layout('components.layouts.app', ['title' => 'Tipe Pesawat - IMS Master']);
    }
}
