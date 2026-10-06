<?php

namespace App\Livewire\Modules\Ims\Katalog;

use App\Models\Ims\Item;
use App\Models\Ims\Category;
use App\Models\Ims\Location;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $categoryId = '';
    public $locationId = '';
    public $status = ''; // available, low, empty, inactive
    
    // Picklist feature
    public $picklist = [];

    public function updatingSearch() { $this->resetPage(); }
    public function updatingCategoryId() { $this->resetPage(); }
    public function updatingLocationId() { $this->resetPage(); }
    public function updatingStatus() { $this->resetPage(); }

    public function mount()
    {
        $this->picklist = session()->get('ims_picklist', []);
    }

    public function addToPicklist($itemId, $qty = 1)
    {
        $item = Item::find($itemId);
        if (!$item || !$item->is_active) return;
        
        if (isset($this->picklist[$itemId])) {
            $this->picklist[$itemId]['qty'] += $qty;
        } else {
            $this->picklist[$itemId] = [
                'id' => $itemId,
                'name' => $item->name,
                'part_number' => $item->part_number,
                'qty' => $qty,
            ];
        }
        
        session()->put('ims_picklist', $this->picklist);
        $this->dispatch('picklist-updated', count($this->picklist));
    }

    public function render()
    {
        $query = Item::with(['category', 'unit', 'stocks.location'])->withSum('stocks as total_on_hand', 'qty_on_hand')->withSum('stocks as total_reserved', 'qty_reserved');

        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('part_number', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->categoryId) {
            $query->where('category_id', $this->categoryId);
        }

        if (!$this->status || $this->status != 'inactive') {
            $query->where('is_active', true);
        } else {
            $query->where('is_active', false);
        }

        $items = $query->orderBy('name')->paginate(config('ims.pagination', 15));

        return view('livewire.modules.ims.katalog.index', [
            'items' => $items,
            'categories' => Category::where('is_active', true)->get(),
            'locations' => Location::where('is_active', true)->get(),
        ])->layout('components.layouts.app', ['title' => 'Katalog Barang - IMS']);
    }
}
