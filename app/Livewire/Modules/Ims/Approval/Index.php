<?php

namespace App\Livewire\Modules\Ims\Approval;

use App\Models\Ims\Transaction;
use App\Models\Ims\TransactionItem;
use App\Services\Ims\DocumentNumberService;
use App\Services\Ims\ImsNotifier;
use App\Services\Ims\StockService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Approvals plus what happens after an OUT request is approved:
 *  - ims.approval.act   approve / reject (never your own request unless config ims.approval.allow_self_approval)
 *  - ims.stock.handover record the physical hand-over, and receive a loan back
 *
 * Stock leaves at approval (commitOut). A loan therefore has to be received back explicitly, which adds the
 * quantity to stock again and links a child IN transaction to the loan.
 */
class Index extends Component
{
    use WithPagination;

    public $activeTab = 'pending'; // pending | history | loans

    public $rejectReason = '';

    public $selectedTransactionId = null;

    // hand-over modal
    public $handoverId = null;

    public $picked_up_by_name = '';

    public $handover_note = '';

    public function mount()
    {
        abort_unless(auth()->user()?->can('ims.approval.view'), 403, 'Anda tidak memiliki akses untuk melihat halaman ini.');
    }

    public function updatingActiveTab()
    {
        $this->resetPage();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = in_array($tab, ['pending', 'history', 'loans'], true) ? $tab : 'pending';
        $this->selectedTransactionId = null;
        $this->resetPage();
    }

    private function authorizeAction(string $permission, string $message): void
    {
        abort_unless(auth()->user()?->can($permission), 403, $message);
    }

    public function approve($transactionId, StockService $stockService)
    {
        $this->authorizeAction('ims.approval.act', 'Anda tidak memiliki akses untuk menyetujui transaksi.');

        try {
            DB::transaction(function () use ($transactionId, $stockService) {
                $transaction = Transaction::where('id', $transactionId)
                    ->where('status', 'pending_approval')
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! config('ims.approval.allow_self_approval') && (int) $transaction->requested_by === (int) auth()->id()) {
                    throw new \DomainException('Anda tidak dapat menyetujui permintaan Anda sendiri.');
                }

                $transaction->status = 'approved';
                $transaction->approved_by = auth()->id();
                $transaction->approved_at = now();
                $transaction->save();

                // Apply stock changes
                foreach ($transaction->items as $item) {
                    if ($transaction->type == 'out') {
                        $stockService->commitOut($item->item_id, $item->location_id, $item->qty, $transaction->id);
                    } elseif ($transaction->type == 'in') {
                        $stockService->receive($item->item_id, $item->location_id, $item->qty, $transaction->id, 'in');
                    }
                }
            });

            $approved = Transaction::find($transactionId);
            app(ImsNotifier::class)->toUser($approved?->requested_by, 'Permintaan barang disetujui', "{$approved?->code} disetujui, stok sudah dikurangi. Silakan ambil barang.", 'success');
            session()->flash('success', 'Transaksi berhasil disetujui.');
        } catch (\Exception $e) {
            session()->flash('error', 'Gagal menyetujui transaksi: '.$e->getMessage());
        }
    }

    public function confirmReject($transactionId)
    {
        $this->authorizeAction('ims.approval.act', 'Anda tidak memiliki akses untuk menolak transaksi.');
        $this->selectedTransactionId = $transactionId;
        $this->rejectReason = '';
    }

    public function reject(StockService $stockService)
    {
        $this->authorizeAction('ims.approval.act', 'Anda tidak memiliki akses untuk menolak transaksi.');

        $this->validate([
            'rejectReason' => 'required|min:5',
        ]);

        try {
            DB::transaction(function () use ($stockService) {
                $transaction = Transaction::where('id', $this->selectedTransactionId)
                    ->where('status', 'pending_approval')
                    ->lockForUpdate()
                    ->firstOrFail();

                $transaction->status = 'rejected';
                $transaction->rejected_by = auth()->id();
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

            $rejected = Transaction::find($this->selectedTransactionId);
            app(ImsNotifier::class)->toUser($rejected?->requested_by, 'Permintaan barang ditolak', "{$rejected?->code} ditolak: {$this->rejectReason}", 'error');
            $this->selectedTransactionId = null;
            session()->flash('success', 'Transaksi ditolak.');
        } catch (\Exception $e) {
            session()->flash('error', 'Gagal menolak transaksi: '.$e->getMessage());
        }
    }

    // ── hand-over & loan return ──────────────────────────────────────────

    public function openHandover($transactionId)
    {
        $this->authorizeAction('ims.stock.handover', 'Anda tidak memiliki akses untuk serah terima barang.');

        $transaction = Transaction::where('type', 'out')->where('status', 'approved')->whereNull('picked_up_at')->findOrFail($transactionId);
        $this->handoverId = $transaction->id;
        $this->picked_up_by_name = (string) ($transaction->requester->name ?? '');
        $this->handover_note = '';
        $this->resetValidation();
    }

    public function closeHandover()
    {
        $this->handoverId = null;
        $this->resetValidation();
    }

    public function saveHandover()
    {
        $this->authorizeAction('ims.stock.handover', 'Anda tidak memiliki akses untuk serah terima barang.');

        $this->validate([
            'picked_up_by_name' => 'required|string|min:3|max:100',
            'handover_note' => 'nullable|string|max:1000',
        ], [], ['picked_up_by_name' => 'nama penerima']);

        $transaction = Transaction::where('type', 'out')->where('status', 'approved')->whereNull('picked_up_at')->findOrFail($this->handoverId);

        if ((int) $transaction->requested_by === (int) auth()->id()) {
            throw new \DomainException('Anda tidak dapat menyerahkan barang untuk permintaan Anda sendiri.');
        }

        $transaction->update([
            'picked_up_by_name' => trim($this->picked_up_by_name),
            'picked_up_at' => now(),
            'handover_note' => $this->handover_note ?: null,
            'handed_over_by' => auth()->id(),
        ]);

        $this->closeHandover();
        session()->flash('success', "Serah terima {$transaction->code} tercatat.");
    }

    /** Receive a whole loan back: stock is added again at the location it left from. */
    public function receiveLoan($transactionId, StockService $stockService, DocumentNumberService $numbers)
    {
        $this->authorizeAction('ims.stock.handover', 'Anda tidak memiliki akses untuk menerima pengembalian.');

        try {
            DB::transaction(function () use ($transactionId, $stockService, $numbers) {
                $loan = Transaction::where('id', $transactionId)
                    ->where('type', 'out')->where('usage_type', 'loan')->where('status', 'approved')
                    ->lockForUpdate()->firstOrFail();

                if (Transaction::where('parent_transaction_id', $loan->id)->where('usage_type', 'loan_return')->exists()) {
                    throw new \DomainException('Pinjaman ini sudah dikembalikan.');
                }

                $return = Transaction::create([
                    'code' => $numbers->generate('in'),
                    'type' => 'in',
                    'status' => 'approved',
                    'usage_type' => 'loan_return',
                    'purpose_description' => 'Pengembalian pinjaman '.$loan->code,
                    'reference_no' => $loan->code,
                    'parent_transaction_id' => $loan->id,
                    'requested_by' => auth()->id(),
                    'requested_at' => now(),
                    'submitted_at' => now(),
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);

                foreach ($loan->items as $line) {
                    TransactionItem::create([
                        'transaction_id' => $return->id,
                        'item_id' => $line->item_id,
                        'location_id' => $line->location_id,
                        'qty' => $line->qty,
                    ]);
                    $stockService->receive($line->item_id, $line->location_id, $line->qty, $return->id, 'loan_return', 'Pengembalian '.$loan->code);
                }
            });

            session()->flash('success', 'Pengembalian pinjaman diterima dan stok bertambah.');
        } catch (\Exception $e) {
            session()->flash('error', 'Gagal menerima pengembalian: '.$e->getMessage());
        }
    }

    public function render()
    {
        $query = Transaction::with(['items.item', 'items.location', 'requester'])
            ->withExists(['returns as is_returned' => fn ($q) => $q->where('usage_type', 'loan_return')]);

        if ($this->activeTab === 'pending') {
            $query->where('status', 'pending_approval')->orderBy('created_at', 'desc');
        } elseif ($this->activeTab === 'loans') {
            $query->where('type', 'out')->where('usage_type', 'loan')->where('status', 'approved')
                ->whereDoesntHave('returns', fn ($q) => $q->where('usage_type', 'loan_return'))
                ->orderBy('expected_return_date');
        } else {
            $query->whereIn('status', ['approved', 'rejected'])->orderBy('created_at', 'desc');
        }

        $loansOutstanding = Transaction::where('type', 'out')->where('usage_type', 'loan')->where('status', 'approved')
            ->whereDoesntHave('returns', fn ($q) => $q->where('usage_type', 'loan_return'));

        return view('livewire.modules.ims.approval.index', [
            'transactions' => $query->paginate(15),
            'counts' => [
                'pending' => Transaction::where('status', 'pending_approval')->count(),
                'loans' => (clone $loansOutstanding)->count(),
                'overdue' => (clone $loansOutstanding)->whereDate('expected_return_date', '<', now()->toDateString())->count(),
            ],
        ])->layout('components.layouts.app', ['title' => 'Persetujuan - IMS']);
    }
}
