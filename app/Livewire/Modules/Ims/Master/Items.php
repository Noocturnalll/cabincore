<?php

namespace App\Livewire\Modules\Ims\Master;

use App\Models\Ims\Category;
use App\Models\Ims\Item;
use App\Models\Ims\Location;
use App\Models\Ims\TransactionItem;
use App\Models\Ims\Unit;
use Illuminate\Database\QueryException;
use Livewire\Component;
use Livewire\WithPagination;

class Items extends Component
{
    use WithPagination;

    public $search = '';

    public $isOpen = false;

    public $isEdit = false;

    public $editId = null;

    public $form = [
        'part_number' => '',
        'name' => '',
        'description' => '',
        'category_id' => '',
        'unit_id' => '',
        'default_location_id' => '',
        'min_stock' => 0,
        'tracking_type' => 'quantity',
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
        $record = Item::findOrFail($id);
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
            'form.part_number' => 'required|string|max:255',
            'form.name' => 'required|string|max:255',
            'form.description' => 'nullable|string',
            'form.category_id' => 'required|exists:ims_categories,id',
            'form.unit_id' => 'required|exists:ims_units,id',
            'form.default_location_id' => 'nullable|exists:ims_locations,id',
            'form.min_stock' => 'nullable|numeric|min:0',
            'form.tracking_type' => 'required|in:quantity,serial',
            'form.is_active' => 'boolean',
        ]);

        if ($this->isEdit) {
            $record = Item::findOrFail($this->editId);
            $record->update($this->form);
            session()->flash('success', 'Data barang berhasil diperbarui.');
        } else {
            Item::create($this->form);
            session()->flash('success', 'Data barang berhasil ditambahkan.');
        }

        $this->isOpen = false;
    }

    public function delete($id)
    {
        $record = Item::findOrFail($id);

        if ($record->stocks()->exists() || TransactionItem::where('item_id', $record->id)->exists()) {
            session()->flash('error', 'Barang sudah memiliki stok atau riwayat transaksi. Nonaktifkan saja.');

            return;
        }

        try {
            $record->delete();
            session()->flash('success', 'Data barang berhasil dihapus.');
        } catch (QueryException $e) {
            // sudah punya stok / riwayat transaksi
            session()->flash('error', 'Barang tidak dapat dihapus karena sudah memiliki stok atau riwayat. Nonaktifkan saja.');
        }
    }

    public function resetForm()
    {
        $this->form = [
            'part_number' => '',
            'name' => '',
            'description' => '',
            'category_id' => '',
            'unit_id' => '',
            'default_location_id' => '',
            'min_stock' => 0,
            'tracking_type' => 'quantity',
            'is_active' => true,
        ];
        $this->editId = null;
    }

    public function render()
    {
        $query = Item::with(['category', 'unit', 'location']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('part_number', 'like', '%'.$this->search.'%');
            });
        }

        return view('livewire.modules.ims.master.items', [
            'records' => $query->paginate(15),
            'categories' => Category::where('is_active', true)->get(),
            'units' => Unit::where('is_active', true)->get(),
            'locations' => Location::where('is_active', true)->get(),
        ])->layout('components.layouts.app', ['title' => 'Data Barang - IMS Master']);
    }
}
