<?php

namespace App\Livewire\Modules\Ims\Opname;

use Livewire\Component;
use App\Models\Ims\Item;
use App\Models\Ims\Location;
use App\Models\Ims\Stock;
use App\Services\Ims\StockService;

class Index extends Component
{
    public $itemId;
    public $locationId;
    public $currentQty = 0;
    public $newQty;
    public $reason;
    
    public $searchItem = '';

    protected $rules = [
        'itemId' => 'required|exists:ims_items,id',
        'locationId' => 'required|exists:ims_locations,id',
        'newQty' => 'required|integer|min:0',
        'reason' => 'required|string'
    ];

    public function updatedItemId()
    {
        $this->updateCurrentQty();
    }

    public function updatedLocationId()
    {
        $this->updateCurrentQty();
    }

    public function updateCurrentQty()
    {
        if ($this->itemId && $this->locationId) {
            $stock = Stock::where('item_id', $this->itemId)->where('location_id', $this->locationId)->first();
            $this->currentQty = $stock ? $stock->qty_on_hand : 0;
            $this->newQty = $this->currentQty;
        } else {
            $this->currentQty = 0;
            $this->newQty = null;
        }
    }

    public function submit(StockService $stockService)
    {
        $this->validate();

        try {
            $stockService->adjust(
                $this->itemId,
                $this->locationId,
                $this->newQty,
                $this->reason
            );

            session()->flash('success', 'Stok berhasil disesuaikan.');
            
            $this->reset(['itemId', 'locationId', 'newQty', 'reason', 'searchItem']);
            $this->currentQty = 0;
        } catch (\Exception $e) {
            session()->flash('error', 'Gagal menyesuaikan stok: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $itemsQuery = Item::where('is_active', true);
        
        if ($this->searchItem) {
            $itemsQuery->where(function($q) {
                $q->where('name', 'like', '%' . $this->searchItem . '%')
                  ->orWhere('part_number', 'like', '%' . $this->searchItem . '%');
            });
        }
        
        $items = $itemsQuery->limit(20)->get();
        $locations = Location::where('is_active', true)->get();

        return view('livewire.modules.ims.opname.index', [
            'items' => $items,
            'locations' => $locations,
        ])->layout('components.layouts.app', ['title' => 'Stock Opname - IMS']);
    }
}
