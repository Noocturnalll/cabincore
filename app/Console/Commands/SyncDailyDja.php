<?php

namespace App\Console\Commands;

use App\Models\DailyJobAssignment;
use App\Models\SyncSetting;
use App\Services\GoogleSheetsSyncService;
use Illuminate\Console\Command;

class SyncDailyDja extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:daily-dja {--sheet= : URL atau ID spreadsheet DJA baru (disimpan sebagai sheet aktif)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Polls the active Google Sheet DJA and synchronizes tasks';

    /**
     * Execute the console command.
     */
    public function handle(GoogleSheetsSyncService $syncService): int
    {
        if ($this->option('sheet')) {
            $newId = SyncSetting::extractSpreadsheetId($this->option('sheet'));
            if (! $newId) {
                $this->error('URL / ID spreadsheet tidak valid.');

                return self::FAILURE;
            }
            SyncSetting::saveSpreadsheetId(SyncSetting::Dja, $newId);
        }

        $spreadsheetId = SyncSetting::spreadsheetIdFor(SyncSetting::Dja)
            ?? DailyJobAssignment::withTrashed()->whereNotNull('source_spreadsheet_id')->latest('updated_at')->value('source_spreadsheet_id');

        if (! $spreadsheetId) {
            $this->info('Belum ada spreadsheet DJA aktif. Masukkan link sheet di menu DJA atau jalankan dengan --sheet=URL.');

            return self::SUCCESS;
        }

        $this->info("Syncing DJA for spreadsheet: {$spreadsheetId}");

        $result = $syncService->syncDja($spreadsheetId);

        if ($result['success']) {
            $this->info($result['message']);

            return self::SUCCESS;
        }

        $this->error($result['message']);

        return self::FAILURE;
    }
}
