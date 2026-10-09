<?php

namespace App\Services\Sources;

use App\Models\SyncSetting;
use App\Services\Compliance\ComplianceSyncService;

/** Registry of the sheets the system reads (config/sources.php): link, last result, and a way to run each sync. */
class DataSources
{
    /** @return array<string, array<string, mixed>> */
    public function all(): array
    {
        $out = [];
        foreach (config('sources.sources') as $key => $def) {
            $setting = SyncSetting::where('key', $key)->first();
            $out[$key] = $def + ['key' => $key, 'tab' => null, 'every' => null, 'default' => null] + [
                'spreadsheet_id' => $this->spreadsheetId($key),
                'last_synced_at' => $setting?->last_synced_at,
                'last_status' => $setting?->last_status,
                'last_message' => $setting?->last_message,
                'syncable' => isset($def['handler']),
            ];
        }

        return $out;
    }

    public function spreadsheetId(string $key): ?string
    {
        $def = config("sources.sources.$key");

        return SyncSetting::spreadsheetIdFor($key) ?: ($def['default'] ?? null);
    }

    /** Accepts a full URL or an id. @return bool false when the input is not a spreadsheet link */
    public function setSpreadsheet(string $key, string $input): bool
    {
        $id = SyncSetting::extractSpreadsheetId($input);
        if (! $id || ! isset(config('sources.sources')[$key]['handler'])) {
            return false;
        }
        SyncSetting::saveSpreadsheetId($key, $id);

        return true;
    }

    /** @return array{ok: bool, message: string} */
    public function run(string $key): array
    {
        $def = config("sources.sources.$key");
        if (! $def || ! isset($def['handler'])) {
            return ['ok' => false, 'message' => 'Sumber ini tidak disinkronkan dari sini.'];
        }
        $id = $this->spreadsheetId($key);
        if (! $id) {
            return ['ok' => false, 'message' => 'Link spreadsheet belum diisi.'];
        }

        try {
            if ($def['handler'] === ComplianceSyncService::class) {
                $service = app(ComplianceSyncService::class);
                $stats = $service->sync();   // records its own result
                $result = $stats === null
                    ? ['ok' => false, 'message' => $service->lastError() ?? 'Sinkronisasi gagal.']
                    : ['ok' => true, 'message' => "{$stats['read']} entri dibaca, {$stats['created']} baru, {$stats['updated']} diperbarui."];

                return $result;
            }
            $result = app($def['handler'])->sync($id, $def['tab'] ?? '');
        } catch (\Throwable $e) {
            $result = ['ok' => false, 'message' => 'Gagal: '.$e->getMessage()];
        }

        SyncSetting::recordResult($key, $result['ok'], $result['message']);

        return $result;
    }
}
