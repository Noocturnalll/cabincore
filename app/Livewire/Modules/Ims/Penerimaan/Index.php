<?php

namespace App\Livewire\Modules\Ims\Penerimaan;

use Livewire\Component;
use App\Models\Ims\Item;
use App\Models\Ims\Location;
use App\Services\Ims\StockService;
use App\Models\Ims\Transaction;

class Index extends Component
{
    public $itemId;
    public $locationId;
    public $qty;
    public $notes;
    
    public $searchItem = '';

    protected $rules = [
        'itemId' => 'required|exists:ims_items,id',
        'locationId' => 'required|exists:ims_locations,id',
        'qty' => 'required|integer|min:1',
        'notes' => 'nullable|string'
    ];

    public function submit(StockService $stockService)
    {
        $this->validate();

        try {
            $stockService->receive(
                $this->itemId,
                $this->locationId,
                $this->qty,
                null,
                'in',
                $this->notes
            );

            session()->flash('success', 'Stok berhasil ditambahkan.');
            
            $this->reset(['itemId', 'locationId', 'qty', 'notes', 'searchItem']);
        } catch (\Exception $e) {
            session()->flash('error', 'Gagal menambahkan stok: ' . $e->getMessage());
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

        return view('livewire.modules.ims.penerimaan.index', [
            'items' => $items,
            'locations' => $locations,
        ])->layout('components.layouts.app', ['title' => 'Penerimaan Barang - IMS']);
    }
}
