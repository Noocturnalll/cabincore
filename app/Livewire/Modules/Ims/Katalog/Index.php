<?php

namespace App\Livewire\Modules\Ims\Katalog;

use App\Models\Ims\Category;
use App\Models\Ims\Item;
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

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingCategoryId()
    {
        $this->resetPage();
    }

    public function updatingLocationId()
    {
        $this->resetPage();
    }

    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'categoryId', 'locationId', 'status']);
        $this->resetPage();
    }

    public function mount()
    {
        abort_unless(auth()->user()?->can('ims.catalog.view'), 403, 'Anda tidak memiliki akses ke katalog barang.');
        $this->picklist = session()->get('ims_picklist', []);
    }

    public function addToPicklist($itemId, $qty = 1)
    {
        $item = Item::find($itemId);
        if (! $item || ! $item->is_active) {
            return;
        }

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
        $this->dispatch('notify', ['icon' => 'success', 'message' => $item->name.' ditambahkan ke picklist.']);
    }

    public function render()
    {
        // available = on hand - reserved, optionally limited to one location
        $locationId = $this->locationId ? (int) $this->locationId : null;
        $stockSub = fn (string $column) => '(select coalesce(sum('.$column.'), 0) from ims_stocks where ims_stocks.item_id = ims_items.id'
            .($locationId ? ' and ims_stocks.location_id = '.$locationId : '').')';
        $availableSql = $stockSub('qty_on_hand').' - '.$stockSub('qty_reserved');

        $query = Item::with(['category', 'unit', 'stocks.location'])
            ->withSum('stocks as total_on_hand', 'qty_on_hand')
            ->withSum('stocks as total_reserved', 'qty_reserved');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('part_number', 'like', '%'.$this->search.'%')
                    ->orWhere('description', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->categoryId) {
            $query->where('category_id', $this->categoryId);
        }

        if ($locationId) {
            $query->whereExists(fn ($q) => $q->selectRaw('1')->from('ims_stocks')
                ->whereColumn('ims_stocks.item_id', 'ims_items.id')
                ->where('ims_stocks.location_id', $locationId)
                ->where('ims_stocks.qty_on_hand', '>', 0));
        }

        $query->where('is_active', $this->status !== 'inactive');

        match ($this->status) {
            'available' => $query->whereRaw("($availableSql) > ims_items.min_stock"),
            'low' => $query->whereRaw("($availableSql) > 0 and ($availableSql) <= ims_items.min_stock"),
            'empty' => $query->whereRaw("($availableSql) <= 0"),
            default => null,
        };

        $items = $query->orderBy('name')->paginate(config('ims.pagination', 15));

        return view('livewire.modules.ims.katalog.index', [
            'items' => $items,
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
            'locations' => Location::where('is_active', true)->orderBy('name')->get(),
        ])->layout('components.layouts.app', ['title' => 'Katalog Barang - IMS']);
    }
}
