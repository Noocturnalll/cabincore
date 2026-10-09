<?php

namespace App\Services\Ims;

use App\Models\Ims\RepairCompleted;
use App\Models\Ims\RepairInProgress;
use App\Models\Ims\RepairLog;
use App\Models\Ims\RepairWaiting;
use Illuminate\Support\Facades\DB;

/**
 * Repair rack: Menunggu -> Diproses -> Selesai -> (kembali ke stok).
 *
 * Each stage is its own table. Moving to the next stage creates the new row and soft-deletes the previous one, so
 * the history stays. Every move is written to ims_repair_logs. The part is not serviceable stock while it is in
 * repair, so intake does not touch stock; only a serviceable result returned to stock adds quantity again.
 */
class RepairService
{
    public const PRIORITIES = ['low' => 'Rendah', 'normal' => 'Normal', 'high' => 'Tinggi'];

    public const RESULTS = [
        'serviceable' => 'Serviceable (dapat dipakai)',
        'unserviceable' => 'Unserviceable (belum dapat dipakai)',
        'scrap' => 'Scrap / BER (tidak ekonomis diperbaiki)',
    ];

    public const SOURCES = ['aircraft' => 'Lepasan pesawat', 'stock' => 'Dari stok / gudang', 'loan_return' => 'Pengembalian pinjaman'];

    public function __construct(private DocumentNumberService $numbers, private ImsNotifier $notifier) {}

    /** @param array<string, mixed> $data item_id, qty, fault_description, priority, source, aircraft_registration, location_id, notes */
    public function receive(array $data): RepairWaiting
    {
        return DB::transaction(function () use ($data) {
            $code = $this->numbers->generate('repair');

            $waiting = RepairWaiting::create([
                'repair_code' => $code,
                'item_id' => $data['item_id'],
                'serial_id' => $data['serial_id'] ?? null,
                'qty' => $data['qty'],
                'fault_description' => $data['fault_description'],
                'priority' => $data['priority'] ?? 'normal',
                'origin_transaction_id' => $data['origin_transaction_id'] ?? null,
                'location_id' => $data['location_id'] ?? null,
                'source' => $data['source'] ?? 'aircraft',
                'aircraft_registration' => $data['aircraft_registration'] ?? null,
                'notes' => $data['notes'] ?? null,
                'received_at' => now(),
                'received_by' => auth()->id(),
                'created_by' => auth()->id(),
            ]);

            $this->log($code, null, 'intake', $data['fault_description']);
            $this->notifier->toPermission('ims.repair.manage', 'Barang rusak masuk', "{$code} menunggu ACC tim repair.", 'warning');

            return $waiting;
        });
    }

    /** The repair team accepts the part: it now sits on Rak Repair 1 (queue). */
    public function accept(string $repairCode): RepairWaiting
    {
        return DB::transaction(function () use ($repairCode) {
            $waiting = RepairWaiting::where('repair_code', $repairCode)->whereNull('accepted_at')->lockForUpdate()->firstOrFail();
            $waiting->update(['accepted_at' => now(), 'accepted_by' => auth()->id()]);

            $this->log($repairCode, 'intake', 'waiting', 'ACC penerimaan');
            $this->notifier->toUser($waiting->received_by, 'Barang rusak di-ACC', "{$repairCode} diterima tim repair (Rak Repair 1).", 'success');

            return $waiting;
        });
    }

    /** @param array<string, mixed> $data technician_id, vendor_id, work_order_no, estimated_completion_date, progress_notes */
    public function start(string $repairCode, array $data = []): RepairInProgress
    {
        return DB::transaction(function () use ($repairCode, $data) {
            $waiting = RepairWaiting::where('repair_code', $repairCode)->lockForUpdate()->firstOrFail();
            if (! $waiting->accepted_at) {
                throw new \DomainException('Barang belum di-ACC oleh tim repair.');
            }

            $progress = RepairInProgress::create([
                'repair_code' => $waiting->repair_code,
                'item_id' => $waiting->item_id,
                'serial_id' => $waiting->serial_id,
                'qty' => $waiting->qty,
                'fault_description' => $waiting->fault_description,
                'priority' => $waiting->priority,
                'origin_transaction_id' => $waiting->origin_transaction_id,
                'location_id' => $waiting->location_id,
                'started_at' => now(),
                'technician_id' => ($data['technician_id'] ?? null) ?: null,
                'vendor_id' => ($data['vendor_id'] ?? null) ?: ($waiting->vendor_id ?: null),
                'work_order_no' => ($data['work_order_no'] ?? null) ?: null,
                'estimated_completion_date' => ($data['estimated_completion_date'] ?? null) ?: null,
                'progress_notes' => ($data['progress_notes'] ?? null) ?: null,
                'created_by' => auth()->id(),
            ]);

            $waiting->delete();
            $this->log($repairCode, 'waiting', 'in_progress', $data['progress_notes'] ?? null);

            return $progress;
        });
    }

    /** @param array<string, mixed> $data result, findings, action_taken, certificate_no, repaired_by_name */
    public function complete(string $repairCode, array $data): RepairCompleted
    {
        return DB::transaction(function () use ($repairCode, $data) {
            $progress = RepairInProgress::where('repair_code', $repairCode)->lockForUpdate()->firstOrFail();

            $completed = RepairCompleted::create([
                'repair_code' => $progress->repair_code,
                'item_id' => $progress->item_id,
                'serial_id' => $progress->serial_id,
                'qty' => $progress->qty,
                'fault_description' => $progress->fault_description,
                'priority' => $progress->priority,
                'origin_transaction_id' => $progress->origin_transaction_id,
                'location_id' => $progress->location_id,
                'completed_at' => now(),
                'result' => $data['result'],
                'findings' => $data['findings'] ?? null,
                'action_taken' => $data['action_taken'] ?? null,
                'certificate_no' => ($data['certificate_no'] ?? null) ?: null,
                'repaired_by_name' => $data['repaired_by_name'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $progress->delete();
            $this->log($repairCode, 'in_progress', 'completed', 'Hasil: '.$data['result']);
            $filedBy = RepairLog::where('repair_code', $repairCode)->orderBy('id')->value('actor_id');
            $this->notifier->toUser($filedBy, 'Repair selesai', "{$repairCode} selesai di Rak Repair 3 (hasil: {$data['result']}).", $data['result'] === 'serviceable' ? 'success' : 'warning');

            return $completed;
        });
    }

    /** Serviceable parts go back on the shelf; the quantity is added to stock and traced to the repair code. */
    public function returnToStock(string $repairCode, int $locationId, StockService $stock): RepairCompleted
    {
        return DB::transaction(function () use ($repairCode, $locationId, $stock) {
            $completed = RepairCompleted::where('repair_code', $repairCode)->lockForUpdate()->firstOrFail();

            if ($completed->result !== 'serviceable') {
                throw new \DomainException('Hanya barang berstatus serviceable yang dapat dikembalikan ke stok.');
            }
            if ($completed->returned_to_stock_at) {
                throw new \DomainException('Barang ini sudah dikembalikan ke stok.');
            }

            $stock->receive($completed->item_id, $locationId, $completed->qty, null, 'repair_return', 'Kembali dari repair '.$repairCode, $repairCode);

            $completed->update(['returned_to_stock_at' => now(), 'location_id' => $locationId]);
            $this->log($repairCode, 'completed', 'stock', 'Kembali ke stok');

            return $completed;
        });
    }

    private function log(string $code, ?string $from, string $to, ?string $note): void
    {
        RepairLog::create(['repair_code' => $code, 'from_stage' => $from ?? '-', 'to_stage' => $to, 'actor_id' => auth()->id(), 'note' => $note]);
    }
}
