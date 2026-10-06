<?php

namespace App\Livewire\Modules\Ims\Approval;

use Livewire\Component;
use App\Models\Ims\Transaction;
use App\Services\Ims\ApprovalService;
use App\Services\Ims\StockService;
use Illuminate\Support\Facades\DB;

class Index extends Component
{
    public $activeTab = 'pending'; // pending | history
    public $rejectReason = '';
    public $selectedTransactionId = null;

    public function approve($transactionId, ApprovalService $approvalService, StockService $stockService)
    {
        try {
            DB::transaction(function () use ($transactionId, $approvalService, $stockService) {
                $transaction = Transaction::where('id', $transactionId)
                    ->where('status', 'pending_approval')
                    ->lockForUpdate()
                    ->firstOrFail();
                
                $transaction->status = 'approved';
                $transaction->approved_by = auth()->id() ?? 1;
                $transaction->approved_at = now();
                $transaction->save();

                // Apply stock changes
                foreach ($transaction->items as $item) {
                    if ($transaction->type == 'out') {
                        $stockService->commitOut($item->item_id, $item->location_id, $item->qty, $transaction->id);
                    } elseif ($transaction->type == 'in') {
                        $stockService->receive($item->item_id, $item->location_id, $item->qty, $transaction->id, 'in');
                    } elseif ($transaction->type == 'transfer') {
                        // Assuming location_id in transaction_items is the source, and we have a target_location_id somewhere
                        // For phase 2, we just handle what's defined.
                    }
                }
            });

            session()->flash('success', 'Transaksi berhasil disetujui.');
        } catch (\Exception $e) {
            session()->flash('error', 'Gagal menyetujui transaksi: ' . $e->getMessage());
        }
    }

    public function confirmReject($transactionId)
    {
        $this->selectedTransactionId = $transactionId;
        $this->rejectReason = '';
    }

    public function reject(ApprovalService $approvalService, StockService $stockService)
    {
        $this->validate([
            'rejectReason' => 'required|min:5'
        ]);

        try {
            DB::transaction(function () use ($stockService) {
                $transaction = Transaction::where('id', $this->selectedTransactionId)
                    ->where('status', 'pending_approval')
                    ->lockForUpdate()
                    ->firstOrFail();
                
                $transaction->status = 'rejected';
                $transaction->rejected_by = auth()->id() ?? 1;
                $transaction->rejected_at = now();
                $transaction->rejected_reason = $this->rejectReason;
                $transaction->save();

                // Release reservations if it was an outgoing request
                if ($transaction->type == 'out') {
                    foreach ($transaction->items as $item) {
                        $stockService->releaseReservation($item->item_id, $item->location_id, $item->qty);
                    }
                }
            });

            $this->selectedTransactionId = null;
            session()->flash('success', 'Transaksi ditolak.');
        } catch (\Exception $e) {
            session()->flash('error', 'Gagal menolak transaksi: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $query = Transaction::with(['items.item', 'requester'])
            ->orderBy('created_at', 'desc');

        if ($this->activeTab == 'pending') {
            $query->where('status', 'pending_approval');
        } else {
            $query->whereIn('status', ['approved', 'rejected']);
        }

        return view('livewire.modules.ims.approval.index', [
            'transactions' => $query->paginate(15)
        ])->layout('components.layouts.app', ['title' => 'Persetujuan - IMS']);
    }
}
