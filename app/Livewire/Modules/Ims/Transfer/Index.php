<?php

namespace App\Livewire\Modules\Ims\Transfer;

use Livewire\Component;
use App\Models\Ims\Item;
use App\Models\Ims\Location;
use App\Models\Ims\Stock;
use App\Services\Ims\StockService;

class Index extends Component
{
    public $itemId;
    public $fromLocationId;
    public $toLocationId;
    public $qty;
    public $notes;
    
    public $availableQty = 0;
    public $searchItem = '';

    protected $rules = [
        'itemId' => 'required|exists:ims_items,id',
        'fromLocationId' => 'required|exists:ims_locations,id',
        'toLocationId' => 'required|exists:ims_locations,id|different:fromLocationId',
        'qty' => 'required|integer|min:1',
        'notes' => 'nullable|string'
    ];

    public function updatedItemId()
    {
        $this->updateAvailableQty();
    }

    public function updatedFromLocationId()
    {
        $this->updateAvailableQty();
    }

    public function updateAvailableQty()
    {
        if ($this->itemId && $this->fromLocationId) {
            $stock = Stock::where('item_id', $this->itemId)->where('location_id', $this->fromLocationId)->first();
            $this->availableQty = $stock ? ($stock->qty_on_hand - $stock->qty_reserved) : 0;
        } else {
            $this->availableQty = 0;
        }
    }

    public function submit(StockService $stockService)
    {
        $this->validate();

        if ($this->qty > $this->availableQty) {
            $this->addError('qty', 'Jumlah transfer melebihi stok yang tersedia.');
            return;
        }

        try {
            $stockService->transfer(
                $this->itemId,
                $this->fromLocationId,
                $this->toLocationId,
                $this->qty,
                null,
                $this->notes
            );

            session()->flash('success', 'Transfer stok berhasil dilakukan.');
            
            $this->reset(['itemId', 'fromLocationId', 'toLocationId', 'qty', 'notes', 'searchItem']);
            $this->availableQty = 0;
        } catch (\Exception $e) {
            session()->flash('error', 'Gagal melakukan transfer: ' . $e->getMessage());
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

        return view('livewire.modules.ims.transfer.index', [
            'items' => $items,
            'locations' => $locations,
        ])->layout('components.layouts.app', ['title' => 'Transfer Stok - IMS']);
    }
}
