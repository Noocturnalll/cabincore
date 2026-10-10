<?php

namespace App\Livewire\Modules\Ims\Peminjaman;

use App\Models\Ims\Item;
use App\Models\Ims\Location;
use App\Models\Ims\Transaction;
use App\Models\Ims\TransactionItem;
use App\Services\Ims\DocumentNumberService;
use App\Services\Ims\ImsNotifier;
use App\Services\Ims\StockService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Index extends Component
{
    public $picklist = [];

    public $purpose_description = '';

    public $usage_type = 'consume'; // consume | loan

    public $expected_return_date = '';

    public $reference_no = '';

    public $aircraft_registration = '';

    public $department_id = '';

    public $activeTab = 'picklist'; // picklist | history

    public function mount()
    {
        abort_unless(auth()->user()?->can('ims.request.create'), 403, 'Anda tidak memiliki akses untuk mengajukan permintaan.');
        $this->loadPicklist();
    }

    public function loadPicklist()
    {
        $this->picklist = session()->get('ims_picklist', []);

        // Enhance picklist with latest stock info
        foreach ($this->picklist as $id => $item) {
            $model = Item::with('unit')->withSum('stocks as total_on_hand', 'qty_on_hand')->withSum('stocks as total_reserved', 'qty_reserved')->find($id);
            if ($model) {
                $available = ($model->total_on_hand ?? 0) - ($model->total_reserved ?? 0);
                $this->picklist[$id]['available'] = $available;
                $this->picklist[$id]['unit'] = $model->unit->code ?? '';
                // Default source location (pick the one with most stock for now)
                $bestStock = $model->stocks()->orderByDesc(DB::raw('qty_on_hand - qty_reserved'))->first();
                $this->picklist[$id]['location_id'] = $bestStock ? $bestStock->location_id : null;
                $this->picklist[$id]['location_name'] = $bestStock ? $bestStock->location->name : 'N/A';
            }
        }
    }

    public function updateQty($itemId, $qty)
    {
        if (isset($this->picklist[$itemId])) {
            $qty = max(1, (int) $qty);
            $available = $this->picklist[$itemId]['available'];
            if ($qty > $available) {
                $qty = $available;
            } // Prevent requesting more than available
            $this->picklist[$itemId]['qty'] = $qty;
            session()->put('ims_picklist', $this->picklist);
        }
    }

    public function removeItem($itemId)
    {
        if (isset($this->picklist[$itemId])) {
            unset($this->picklist[$itemId]);
            session()->put('ims_picklist', $this->picklist);
            $this->dispatch('picklist-updated', count($this->picklist));
        }
    }

    public function submitRequest(DocumentNumberService $docService, StockService $stockService)
    {
        $this->validate([
            'purpose_description' => 'required|min:10',
            'usage_type' => 'required|in:consume,loan',
            'expected_return_date' => 'required_if:usage_type,loan',
            'picklist' => 'required|array|min:1',
        ]);

        if (empty($this->picklist)) {
            session()->flash('error', 'Picklist is empty.');

            return;
        }

        try {
            DB::transaction(function () use ($docService, $stockService) {
                $code = $docService->generate('out');

                $transaction = Transaction::create([
                    'code' => $code,
                    'type' => 'out',
                    'status' => 'pending_approval',
                    'usage_type' => $this->usage_type,
                    'expected_return_date' => $this->usage_type == 'loan' ? $this->expected_return_date : null,
                    'purpose_description' => $this->purpose_description,
                    'reference_no' => $this->reference_no,
                    'aircraft_registration' => $this->aircraft_registration,
                    'department_id' => auth()->user()->division_id ?? null,
                    'requested_by' => auth()->id() ?? 1, // fallback to 1 if no auth in testing
                    'requested_at' => now(),
                    'submitted_at' => now(),
                ]);

                foreach ($this->picklist as $item) {
                    if (! $item['location_id']) {
                        throw new \Exception("Lokasi sumber untuk {$item['name']} tidak ditemukan.");
                    }

                    // Reserve the stock!
                    $stockService->reserve($item['id'], $item['location_id'], $item['qty']);

                    TransactionItem::create([
                        'transaction_id' => $transaction->id,
                        'item_id' => $item['id'],
                        'location_id' => $item['location_id'],
                        'qty' => $item['qty'],
                    ]);
                }
            });

            $code = Transaction::where('requested_by', auth()->id())->latest('id')->value('code');
            app(ImsNotifier::class)->toPermission('ims.approval.act', 'Permintaan barang baru', ($code ?? 'Permintaan').' dari '.auth()->user()->name.' menunggu persetujuan.', 'warning');

            // Clear picklist
            session()->forget('ims_picklist');
            $this->picklist = [];
            $this->dispatch('picklist-updated', 0);

            // Notification or session flash
            session()->flash('success', 'Permintaan berhasil diajukan dan sedang menunggu persetujuan.');
            $this->activeTab = 'history';

        } catch (\Exception $e) {
            session()->flash('error', 'Gagal mengajukan permintaan: '.$e->getMessage());
        }
    }

    public function cancelRequest($transactionId, StockService $stockService)
    {
        try {
            DB::transaction(function () use ($transactionId, $stockService) {
                $transaction = Transaction::where('id', $transactionId)
                    ->where('requested_by', auth()->id() ?? 1)
                    ->whereIn('status', ['draft', 'pending_approval'])
                    ->firstOrFail();

                // Release reserved stocks
                foreach ($transaction->items as $item) {
                    $stockService->releaseReservation($item->item_id, $item->location_id, $item->qty);
                }

                $transaction->status = 'cancelled';
                $transaction->save();
            });

            session()->flash('success', 'Permintaan berhasil dibatalkan dan kunci stok dilepas.');
        } catch (\Exception $e) {
            session()->flash('error', 'Gagal membatalkan permintaan: '.$e->getMessage());
        }
    }

    public function render()
    {
        $history = [];
        if ($this->activeTab == 'history') {
            $history = Transaction::with('items.item')
                ->where('type', 'out')
                ->where('requested_by', auth()->id() ?? 1)
                ->orderByDesc('created_at')
                ->paginate(10);
        }

        return view('livewire.modules.ims.peminjaman.index', [
            'history' => $history,
        ])->layout('components.layouts.app', ['title' => 'Pengeluaran Barang - IMS']);
    }
}
