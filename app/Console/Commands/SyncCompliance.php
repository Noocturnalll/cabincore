<?php

namespace App\Console\Commands;

use App\Models\SyncSetting;
use App\Services\Compliance\ComplianceSyncService;
use Illuminate\Console\Command;

class SyncCompliance extends Command
{
    protected $signature = 'sync:compliance {--sheet= : URL atau ID spreadsheet compliance (disimpan)}';

    protected $description = 'Mirrors the daily briefing / attendant list / 5R evidence sheet (tab Entries) into the app';

    public function handle(ComplianceSyncService $service): int
    {
        if ($this->option('sheet')) {
            $id = SyncSetting::extractSpreadsheetId($this->option('sheet'));
            if (! $id) {
                $this->error('URL / ID spreadsheet tidak valid.');

                return self::FAILURE;
            }
            SyncSetting::saveSpreadsheetId(ComplianceSyncService::Key, $id);
        }

        $stats = $service->sync();
        if ($stats === null) {
            $this->error($service->lastError() ?? 'Sinkronisasi gagal.');

            return self::FAILURE;
        }

        $this->info("{$stats['read']} entri dibaca: {$stats['created']} baru, {$stats['updated']} diperbarui.");

        return self::SUCCESS;
    }
}
