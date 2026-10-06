<?php

namespace App\Console\Commands;

use App\Models\SyncSetting;
use App\Services\AcMovementSyncService;
use Illuminate\Console\Command;

class SyncAcMovement extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:ac-movement {--sheet= : URL atau ID spreadsheet AC Movement baru (disimpan sebagai sheet aktif)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pulls Terminal 1, Terminal 2, AC RON and AC STBY from the saved AC Movement Google Sheet';

    /**
     * Execute the console command.
     */
    public function handle(AcMovementSyncService $syncService): int
    {
        if ($this->option('sheet')) {
            $newId = SyncSetting::extractSpreadsheetId($this->option('sheet'));
            if (! $newId) {
                $this->error('URL / ID spreadsheet tidak valid.');

                return self::FAILURE;
            }
            SyncSetting::saveSpreadsheetId(SyncSetting::AcMovement, $newId);
        }

        $spreadsheetId = SyncSetting::spreadsheetIdFor(SyncSetting::AcMovement);

        if (! $spreadsheetId) {
            $this->info('Belum ada spreadsheet AC Movement tersimpan. Masukkan link sheet di menu AC Movement atau jalankan dengan --sheet=URL.');

            return self::SUCCESS;
        }

        $result = $syncService->pullSync($spreadsheetId);

        if ($result['success']) {
            $this->info($result['message']);

            return self::SUCCESS;
        }

        $this->error($result['message']);

        return self::FAILURE;
    }
}
