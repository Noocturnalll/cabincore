<?php

namespace App\Livewire\Modules\Ims\Penerimaan;

use App\Livewire\Traits\WithItemPicker;
use App\Models\Ims\Location;
use App\Services\Ims\StockService;
use Livewire\Component;

class Index extends Component
{
    use WithItemPicker;

    public $itemId;

    public $locationId;

    public $qty;

    public $notes;

    protected $rules = [
        'itemId' => 'required|exists:ims_items,id',
        'locationId' => 'required|exists:ims_locations,id',
        'qty' => 'required|integer|min:1',
        'notes' => 'nullable|string',
    ];

    public function submit(StockService $stockService)
    {
        $this->authorizeStockAction('ims.stock.in');
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
            session()->flash('error', 'Gagal menambahkan stok: '.$e->getMessage());
        }
    }

    public function render()
    {
        $items = $this->pickerItems();
        $locations = Location::where('is_active', true)->orderBy('name')->get();

        return view('livewire.modules.ims.penerimaan.index', [
            'items' => $items,
            'locations' => $locations,
        ])->layout('components.layouts.app', ['title' => 'Penerimaan Barang - IMS']);
    }
}
