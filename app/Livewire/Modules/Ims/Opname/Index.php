<?php

namespace App\Livewire\Modules\Ims\Opname;

use App\Livewire\Traits\WithItemPicker;
use App\Models\Ims\Location;
use App\Models\Ims\Stock;
use App\Services\Ims\StockService;
use Livewire\Component;

class Index extends Component
{
    use WithItemPicker;

    public $itemId;

    public $locationId;

    public $currentQty = 0;

    public $newQty;

    public $reason;

    protected $rules = [
        'itemId' => 'required|exists:ims_items,id',
        'locationId' => 'required|exists:ims_locations,id',
        'newQty' => 'required|integer|min:0',
        'reason' => 'required|string',
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
        $this->authorizeStockAction('ims.stock.adjust');
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
            session()->flash('error', 'Gagal menyesuaikan stok: '.$e->getMessage());
        }
    }

    public function render()
    {
        $items = $this->pickerItems();
        $locations = Location::where('is_active', true)->orderBy('name')->get();

        return view('livewire.modules.ims.opname.index', [
            'items' => $items,
            'locations' => $locations,
        ])->layout('components.layouts.app', ['title' => 'Stock Opname - IMS']);
    }
}
