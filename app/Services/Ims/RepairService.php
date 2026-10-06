<?php

namespace App\Services\Ims;

class RepairService
{
    public function moveToWaiting($transactionItem)
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($transactionItem) {
            $repairCode = 'RPR-' . now()->format('Ym') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            return \App\Models\Ims\RepairWaiting::create([
                'repair_code' => $repairCode,
                'item_id' => $transactionItem->item_id,
                'qty' => $transactionItem->qty,
                'notes' => 'Received from transaction ' . $transactionItem->transaction_id,
                'received_by' => auth()->id() ?? 1,
                'received_at' => now(),
            ]);
        });
    }

    public function moveToInProgress($repairCode, $data)
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($repairCode, $data) {
            $waiting = \App\Models\Ims\RepairWaiting::where('repair_code', $repairCode)->firstOrFail();
            
            $inProgress = \App\Models\Ims\RepairInProgress::create([
                'repair_code' => $waiting->repair_code,
                'item_id' => $waiting->item_id,
                'qty' => $waiting->qty,
                'started_by' => auth()->id() ?? 1,
                'started_at' => now(),
            ]);

            $waiting->delete();
            return $inProgress;
        });
    }

    public function moveToCompleted($repairCode, $data)
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($repairCode, $data) {
            $inProgress = \App\Models\Ims\RepairInProgress::where('repair_code', $repairCode)->firstOrFail();
            
            $completed = \App\Models\Ims\RepairCompleted::create([
                'repair_code' => $inProgress->repair_code,
                'item_id' => $inProgress->item_id,
                'qty' => $inProgress->qty,
                'condition' => $data['condition'] ?? 'serviceable',
                'finished_by' => auth()->id() ?? 1,
                'finished_at' => now(),
            ]);

            $inProgress->delete();
            return $completed;
        });
    }

    public function returnToStock($repairCode, $locationId, StockService $stockService)
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($repairCode, $locationId, $stockService) {
            $completed = \App\Models\Ims\RepairCompleted::where('repair_code', $repairCode)->firstOrFail();
            
            $stockService->receive($completed->item_id, $locationId, $completed->qty, null, 'repair_return');
            
            $completed->delete();
            return true;
        });
    }
}
