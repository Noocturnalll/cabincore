<?php

namespace App\Services\Ims;

use App\Models\Ims\Stock;
use App\Models\Ims\StockMovement;
use Exception;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Reserve stock for a transaction
     */
    public function reserve($itemId, $locationId, $qty)
    {
        return DB::transaction(function () use ($itemId, $locationId, $qty) {
            $stock = Stock::lockForUpdate()->firstOrCreate([
                'item_id' => $itemId,
                'location_id' => $locationId,
            ]);

            $available = $stock->qty_on_hand - $stock->qty_reserved;
            if ($available < $qty) {
                throw new Exception('Insufficient stock available.');
            }

            $stock->qty_reserved += $qty;
            $stock->save();

            return $stock;
        });
    }

    /**
     * Release reserved stock
     */
    public function releaseReservation($itemId, $locationId, $qty)
    {
        return DB::transaction(function () use ($itemId, $locationId, $qty) {
            $stock = Stock::lockForUpdate()->where('item_id', $itemId)->where('location_id', $locationId)->first();
            if ($stock) {
                $stock->qty_reserved = max(0, $stock->qty_reserved - $qty);
                $stock->save();
            }

            return $stock;
        });
    }

    /**
     * Commit out stock
     */
    public function commitOut($itemId, $locationId, $qty, $transactionId = null, $notes = null)
    {
        return DB::transaction(function () use ($itemId, $locationId, $qty, $transactionId, $notes) {
            $stock = Stock::lockForUpdate()->firstOrCreate([
                'item_id' => $itemId,
                'location_id' => $locationId,
            ]);

            if ($stock->qty_on_hand < $qty || $stock->qty_reserved < $qty) {
                throw new Exception('Insufficient stock or reservation to commit out.');
            }

            $balanceBefore = $stock->qty_on_hand ?? 0;
            $stock->qty_on_hand -= $qty;
            $stock->qty_reserved -= $qty;
            $stock->save();

            $this->logMovement($itemId, $locationId, 'out', -$qty, $balanceBefore, $stock->qty_on_hand, $transactionId, null, $notes);

            return $stock;
        });
    }

    /**
     * Receive stock
     */
    public function receive($itemId, $locationId, $qty, $transactionId = null, $type = 'in', $notes = null, $repairCode = null)
    {
        return DB::transaction(function () use ($itemId, $locationId, $qty, $transactionId, $type, $notes, $repairCode) {
            $stock = Stock::lockForUpdate()->firstOrCreate([
                'item_id' => $itemId,
                'location_id' => $locationId,
            ]);

            $balanceBefore = $stock->qty_on_hand ?? 0;
            $stock->qty_on_hand += $qty;
            $stock->save();

            $this->logMovement($itemId, $locationId, $type, $qty, $balanceBefore, $stock->qty_on_hand, $transactionId, $repairCode, $notes);

            return $stock;
        });
    }

    /**
     * Adjust stock
     */
    public function adjust($itemId, $locationId, $newQty, $reason, $transactionId = null)
    {
        return DB::transaction(function () use ($itemId, $locationId, $newQty, $reason, $transactionId) {
            $stock = Stock::lockForUpdate()->firstOrCreate([
                'item_id' => $itemId,
                'location_id' => $locationId,
            ]);

            if ($newQty < $stock->qty_reserved) {
                throw new Exception('New quantity cannot be less than reserved quantity.');
            }

            $balanceBefore = $stock->qty_on_hand ?? 0;
            $diff = $newQty - $balanceBefore;

            if ($diff != 0) {
                $stock->qty_on_hand = $newQty;
                $stock->save();

                $type = $diff > 0 ? 'adjust_plus' : 'adjust_minus';
                $this->logMovement($itemId, $locationId, $type, $diff, $balanceBefore, $newQty, $transactionId, null, $reason);
            }

            return $stock;
        });
    }

    /**
     * Transfer stock
     */
    public function transfer($itemId, $fromLocationId, $toLocationId, $qty, $transactionId = null, $notes = null)
    {
        return DB::transaction(function () use ($itemId, $fromLocationId, $toLocationId, $qty, $transactionId, $notes) {
            // Lock in consistent order to prevent deadlock
            $locs = [$fromLocationId, $toLocationId];
            sort($locs);

            foreach ($locs as $loc) {
                Stock::lockForUpdate()->firstOrCreate(['item_id' => $itemId, 'location_id' => $loc]);
            }

            $fromStock = Stock::where('item_id', $itemId)->where('location_id', $fromLocationId)->first();
            $toStock = Stock::where('item_id', $itemId)->where('location_id', $toLocationId)->first();

            if ($fromStock->qty_on_hand - $fromStock->qty_reserved < $qty) {
                throw new Exception('Insufficient stock to transfer.');
            }

            $fromBefore = $fromStock->qty_on_hand;
            $fromStock->qty_on_hand -= $qty;
            $fromStock->save();

            $toBefore = $toStock->qty_on_hand;
            $toStock->qty_on_hand += $qty;
            $toStock->save();

            $this->logMovement($itemId, $fromLocationId, 'transfer_out', -$qty, $fromBefore, $fromStock->qty_on_hand, $transactionId, null, $notes);
            $this->logMovement($itemId, $toLocationId, 'transfer_in', $qty, $toBefore, $toStock->qty_on_hand, $transactionId, null, $notes);

            return true;
        });
    }

    /**
     * Internal method to log movement
     */
    protected function logMovement($itemId, $locationId, $movementType, $qtyChange, $balanceBefore, $balanceAfter, $transactionId = null, $repairCode = null, $notes = null)
    {
        StockMovement::create([
            'item_id' => $itemId,
            'location_id' => $locationId,
            'movement_type' => $movementType,
            'qty_change' => $qtyChange,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'transaction_id' => $transactionId,
            'repair_code' => $repairCode,
            'notes' => $notes,
            'created_by' => auth()->id() ?? null,
        ]);
    }
}
