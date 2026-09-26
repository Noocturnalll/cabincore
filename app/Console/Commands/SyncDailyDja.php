<?php

namespace App\Console\Commands;

use App\Models\DailyJobAssignment;
use App\Models\DmiLog;
use App\Models\NsrdiLog;
use App\Models\WoLog;
use App\Services\GoogleSheetsSyncService;
use Illuminate\Console\Command;

class SyncDailyDja extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:daily-dja';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Polls the active Google Sheet DJA and synchronizes tasks';

    /**
     * Execute the console command.
     */
    public function handle(GoogleSheetsSyncService $syncService)
    {
        // For demonstration, we fetch the most recent DJA spreadsheet ID.
        // In reality, this might come from a settings table or today's active record.
        $latestDja = DailyJobAssignment::latest('created_at')->first();
        if (! $latestDja || empty($latestDja->source_spreadsheet_id)) {
            $this->info('No active spreadsheet ID found.');

            return;
        }

        $spreadsheetId = $latestDja->source_spreadsheet_id;
        $this->info("Syncing DJA for spreadsheet: $spreadsheetId");

        // Execute pull sync
        $pulledTaskIds = $syncService->pullSync($spreadsheetId);

        if (is_array($pulledTaskIds)) {
            // Ghost Task Protocol:
            // Compare the freshly pulled IDs with existing local IDs.
            // If local ID exists but wasn't pulled, we set dja_id = null and soft delete.
            $ghostTasks = DailyJobAssignment::where('source_spreadsheet_id', $spreadsheetId)
                ->whereNotIn('task_id', $pulledTaskIds)->get();

            foreach ($ghostTasks as $ghost) {
                $logClass = null;
                if ($ghost->job_type === 'R01/WO') {
                    $logClass = WoLog::class;
                } elseif ($ghost->job_type === 'DMI') {
                    $logClass = DmiLog::class;
                } elseif ($ghost->job_type === 'AOC/NSRDI') {
                    $logClass = NsrdiLog::class;
                }

                if ($logClass) {
                    $log = $logClass::where('dja_id', $ghost->id)->first();
                    if ($log) {
                        $log->dja_id = null;
                        $log->hold_remarks = $log->hold_remarks."\n[SYSTEM] Task removed from DJA by Planner. Converted to Unplanned.";
                        $log->save();
                    }
                }
                $ghost->delete(); // Soft delete
            }

            $this->info('Sync completed successfully.');
        } else {
            $this->error('Sync failed.');
        }
    }
}
