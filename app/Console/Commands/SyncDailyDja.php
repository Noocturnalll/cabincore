<?php

namespace App\Console\Commands;

use App\Helpers\RoleHelper;
use App\Models\DailyJobAssignment;
use App\Models\SyncSetting;
use App\Models\User;
use App\Notifications\SystemNotification;
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

        $wasFailing = SyncSetting::for(SyncSetting::Dja)->last_status === 'failed';

        $result = $syncService->syncDja($spreadsheetId);

        if ($result['success']) {
            $this->info($result['message']);

            return self::SUCCESS;
        }

        $this->error($result['message']);

        // Tell the admins once per failure streak (not every 15 minutes) so a broken sync is noticed
        if (! $wasFailing) {
            User::whereHas('roles', fn ($q) => $q->where('name', RoleHelper::SUPER_ADMIN))->where('status', 'active')->get()->each->notify(new SystemNotification([
                'type' => 'error',
                'title' => 'Sync DJA gagal',
                'message' => $result['message'],
            ]));
        }

        return self::FAILURE;
    }
}
