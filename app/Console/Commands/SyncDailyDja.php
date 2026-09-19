<?php

namespace App\Console\Commands;

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
    public function handle(\App\Services\GoogleSheetsSyncService $syncService)
    {
        // For demonstration, we fetch the most recent DJA spreadsheet ID.
        // In reality, this might come from a settings table or today's active record.
        $latestDja = \App\Models\DailyJobAssignment::latest('created_at')->first();
        if (!$latestDja || empty($latestDja->source_spreadsheet_id)) {
            $this->info("No active spreadsheet ID found.");
            return;
        }

        $spreadsheetId = $latestDja->source_spreadsheet_id;
        $this->info("Syncing DJA for spreadsheet: $spreadsheetId");

        // Execute pull sync
        $success = $syncService->pullSync($spreadsheetId);

        if ($success) {
            // Ghost Task Protocol:
            // For a real implementation, we would compare the freshly pulled IDs with existing local IDs.
            // If local ID exists but wasn't pulled, we set dja_id = null and soft delete.
            // Here is the skeleton logic:
            
            // $pulledTaskIds = ...; // collected during pullSync
            // $ghostTasks = DailyJobAssignment::where('source_spreadsheet_id', $spreadsheetId)
            //    ->whereNotIn('task_id', $pulledTaskIds)->get();
            
            // foreach($ghostTasks as $ghost) {
            //     $logClass = $this->getLogClass($ghost->job_type);
            //     if($logClass) {
            //          $log = $logClass::where('dja_id', $ghost->id)->first();
            //          if($log) {
            //              $log->dja_id = null;
            //              $log->hold_remarks = $log->hold_remarks . "\n[SYSTEM] Task removed from DJA by Planner. Converted to Unplanned.";
            //              $log->save();
            //          }
            //     }
            //     $ghost->delete(); // Soft delete
            // }

            $this->info("Sync completed successfully.");
        } else {
            $this->error("Sync failed.");
        }
    }
}
