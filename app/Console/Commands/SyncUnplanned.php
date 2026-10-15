<?php

namespace App\Console\Commands;

use App\Models\DailyJobAssignment;
use App\Models\SyncSetting;
use App\Services\Dja\UnplannedSheetPuller;
use App\Services\Dja\UnplannedSheetPusher;
use Illuminate\Console\Command;

class SyncUnplanned extends Command
{
    protected $signature = 'sync:unplanned';

    protected $description = 'Light two-way unplanned sync: retry failed writes to the GSS, then pull the Unplanned tabs';

    public function handle(UnplannedSheetPuller $puller, UnplannedSheetPusher $pusher): int
    {
        $id = SyncSetting::spreadsheetIdFor(SyncSetting::Dja)
            ?? DailyJobAssignment::withTrashed()->whereNotNull('source_spreadsheet_id')->latest('updated_at')->value('source_spreadsheet_id');
        if (! $id) {
            return self::SUCCESS;
        }

        try {
            $healed = $pusher->retryFailed();
            $s = $puller->pull($id);
            $this->info("unplanned: +{$s['created']} baru, {$s['updated']} diperbarui, {$s['conflicts']} konflik, {$healed} tulis ulang berhasil.");
        } catch (\Throwable $e) {
            $this->warn('unplanned sync: '.$e->getMessage());
        }

        return self::SUCCESS;
    }
}
