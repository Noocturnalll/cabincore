<?php

namespace App\Services;

use App\Models\DailyJobAssignment;
use App\Models\DmiLog;
use App\Models\NsrdiLog;
use App\Models\SyncSetting;
use App\Models\WoLog;
use App\Services\Dja\DailyReportArchiver;
use App\Services\Dja\DjaIngestor;
use App\Services\Dja\DjaRonSyncService;
use App\Services\Dja\DjaRowMapper;
use Google\Service\Sheets\BatchUpdateValuesRequest;
use Google\Service\Sheets\ValueRange;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class GoogleSheetsSyncService
{
    public const Tabs = ['DJA', 'DJA DMI', 'DJA NSRD', 'DJA NSRDI'];

    protected $service;

    protected ?string $lastError = null;

    /** @var array<string, int> counters of the last pull */
    protected array $lastStats = [];

    public function __construct(
        protected GoogleSheetsReader $reader,
        protected ?DjaIngestor $ingestor = null,
        protected ?DjaRowMapper $mapper = null,
    ) {
        $this->service = $reader->isReady() ? $reader->service() : null;
        $this->ingestor ??= app(DjaIngestor::class);
        $this->mapper ??= new DjaRowMapper;
    }

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    /** @return array<string, int> */
    public function lastStats(): array
    {
        return $this->lastStats;
    }

    /**
     * Full DJA sync for one spreadsheet: pull tasks, then apply the Ghost Task Protocol.
     *
     * @return array{success: bool, message: string, synced: int, removed: int, archived?: int, stats: array<string, int>}
     */
    public function syncDja(string $spreadsheetId): array
    {
        $pulledTaskIds = $this->pullSync($spreadsheetId);

        if (! is_array($pulledTaskIds)) {
            $message = 'Sync DJA gagal: '.($this->lastError ?? 'unknown error');
            SyncSetting::recordResult(SyncSetting::Dja, false, $message);

            return ['success' => false, 'message' => $message, 'synced' => 0, 'removed' => 0, 'stats' => []];
        }

        // Jika ada tab yang gagal dibaca, jangan hapus task apapun (bisa jadi task-nya ada di tab tersebut)
        $removed = $this->lastError ? 0 : $this->removeGhostTasks($spreadsheetId, $pulledTaskIds);
        $synced = count(array_unique($pulledTaskIds));
        $this->ingestor->pruneStale();

        // A sync after the 18:00 cutoff must not wait for the next scheduler tick to bank yesterday's logs
        $archived = array_sum(app(DailyReportArchiver::class)->run());

        $s = $this->lastStats;
        $message = "DJA tersinkron: {$synced} task (baru {$s['created']}, diperbarui {$s['updated']}), {$removed} task dihapus dari DJA.";
        if (($s['review'] ?? 0) > 0) {
            $message .= " {$s['review']} baris perlu review.";
        }
        if (($s['rejected'] ?? 0) > 0) {
            $message .= " {$s['rejected']} baris ditolak aturan.";
        }

        if ($archived > 0) {
            $message .= " {$archived} log masuk Daily Report.";
        }

        // Synchronize RON from Sheet 1 ('List AC')
        $ronResult = $this->syncRonSheet($spreadsheetId);
        if ($ronResult && $ronResult['total_stations'] > 0) {
            $message .= " Data RON {$ronResult['total_stations']} station ({$ronResult['total_aircraft']} A/C) berhasil disinkronkan dari {$ronResult['tab_title']}.";
        }

        if ($this->lastError) {
            $message .= ' Catatan: '.$this->lastError;
        }

        SyncSetting::recordResult(SyncSetting::Dja, true, $message);

        return ['success' => true, 'message' => $message, 'synced' => $synced, 'removed' => $removed, 'archived' => $archived, 'stats' => $s, 'ron' => $ronResult];
    }

    /**
     * Synchronize RON aircraft from Sheet 1 ('List AC') into CapacityStation and CapacityReport.
     *
     * @return array{total_stations: int, total_aircraft: int, tab_title: string}|null
     */
    public function syncRonSheet(string $spreadsheetId): ?array
    {
        if (! $this->service) {
            return null;
        }

        try {
            $allTitles = $this->reader->tabTitles($spreadsheetId);
            if (empty($allTitles)) {
                return null;
            }

            // Find tab matching 'List AC' or fallback to the 1st sheet ($allTitles[0])
            $targetTitle = null;
            foreach ($allTitles as $title) {
                $norm = strtoupper(trim($title));
                if (str_contains($norm, 'LIST AC') || str_contains($norm, 'LIST_AC') || $norm === 'AC' || $norm === 'A/C') {
                    $targetTitle = $title;
                    break;
                }
            }

            $targetTitle ??= $allTitles[0];

            $values = $this->reader->values($spreadsheetId, $targetTitle);
            if (empty($values)) {
                return null;
            }

            return app(DjaRonSyncService::class)->syncFromArray($values, $targetTitle);
        } catch (\Throwable $e) {
            Log::warning("Failed to sync RON from Sheet 1 ({$spreadsheetId}): ".$e->getMessage());

            return null;
        }
    }

    /**
     * Ghost Task Protocol: tasks of this spreadsheet no longer present in the sheet are detached from their logs
     * (converted to Unplanned) and soft deleted.
     *
     * @param  array<int, string>  $pulledTaskIds
     */
    public function removeGhostTasks(string $spreadsheetId, array $pulledTaskIds): int
    {
        $ghostTasks = DailyJobAssignment::where('source_spreadsheet_id', $spreadsheetId)
            ->whereNotIn('task_id', $pulledTaskIds)
            ->get();

        foreach ($ghostTasks as $ghost) {
            $logClass = match ($ghost->job_type) {
                'R01/WO' => WoLog::class,
                'DMI' => DmiLog::class,
                'AOC/NSRDI' => NsrdiLog::class,
                default => null,
            };

            if ($logClass) {
                $log = $logClass::where('dja_id', $ghost->id)->first();
                if ($log) {
                    $log->dja_id = null;
                    $log->hold_remarks = trim($log->hold_remarks."\n[SYSTEM] Task removed from DJA by Planner. Converted to Unplanned.");
                    $log->save();
                }
            }

            $ghost->delete();
        }

        return $ghostTasks->count();
    }

    /**
     * Pulls DJA tabs into the database. Returns the synced task IDs, or false when nothing could be read.
     *
     * @return array<int, string>|false
     */
    public function pullSync($spreadsheetId)
    {
        $this->lastError = null;
        $this->lastStats = [];

        if (! $this->service) {
            $this->lastError = 'File kredensial Google (storage/app/google-credentials.json) tidak ditemukan.';
            Log::error('Cannot sync: Missing Google Credentials or SDK.');

            return false;
        }

        try {
            $tabs = $this->reader->resolveTabs(self::Tabs, $this->reader->tabTitles($spreadsheetId));
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            $email = GoogleSheetsReader::getServiceAccountEmail() ?? 'cabin-on-duty@rock-cairn-508901-m1.iam.gserviceaccount.com';

            if (str_contains($msg, '403') || str_contains($msg, 'PERMISSION_DENIED') || str_contains($msg, 'does not have permission')) {
                $this->lastError = "Izin ditolak (403): Google Sheet belum di-share ke email Service Account. Silakan buka Sheet Anda -> Klik Bagikan (Share) -> Tambahkan email: {$email} (Viewer).";
            } elseif (str_contains($msg, '404') || str_contains($msg, 'NOT_FOUND')) {
                $this->lastError = 'Spreadsheet tidak ditemukan (404). Pastikan URL atau ID Google Sheet sudah benar.';
            } else {
                $this->lastError = 'Spreadsheet tidak bisa dibuka. Pastikan sheet valid dan dapat diakses. Detail: '.mb_substr($msg, 0, 180);
            }

            Log::error("DJA sync: cannot open spreadsheet {$spreadsheetId}: ".$e->getMessage());

            return false;
        }

        if ($tabs === []) {
            $this->lastError = 'Tidak ada tab DJA / DJA DMI / DJA NSRDI di spreadsheet ini.';

            return false;
        }

        $this->ingestor->begin();
        $pulledTaskIds = [];
        $tabsRead = 0;
        $failedTabs = [];

        foreach ($tabs as $tabName => $actualTitle) {
            try {
                $values = $this->reader->values($spreadsheetId, $actualTitle);
                $tabsRead++;

                if (empty($values)) {
                    continue;
                }

                $header = $this->reader->locateHeader($values, DjaRowMapper::knownHeaders(), 1);
                $headerIndex = $header['index'] ?? 0;
                $headerMap = $header['map'] ?? [];

                foreach (array_slice($values, $headerIndex + 1) as $row) {
                    $taskId = $this->ingestor->ingest($tabName, $row, $headerMap, $spreadsheetId);
                    if ($taskId) {
                        $pulledTaskIds[] = $taskId;
                    }
                }
            } catch (\Throwable $e) {
                $failedTabs[] = $actualTitle;
                Log::warning("Skipped tab {$actualTitle}: ".$e->getMessage());
            }
        }

        $this->lastStats = $this->ingestor->stats();

        if ($tabsRead === 0) {
            $this->lastError = 'Semua tab gagal dibaca: '.implode(', ', $failedTabs);

            return false;
        }

        if ($failedTabs) {
            $this->lastError = 'Tab gagal dibaca: '.implode(', ', $failedTabs);
        }

        return $pulledTaskIds;
    }

    /**
     * Writes a status change back to the planner sheet in one request: status, reason code and remarks
     * (and the close date for NSRDI), each into the column that tab uses for it.
     *
     * @return bool true when the sheet was updated
     */
    public function pushSync($spreadsheetId, $tabName, $taskId, $status, $remarks, $code = null)
    {
        if (! $this->service || empty($spreadsheetId)) {
            return false;
        }

        try {
            $isNsrdi = in_array($tabName, ['DJA NSRD', 'DJA NSRDI']);
            $candidates = $isNsrdi ? ['DJA NSRDI', 'DJA NSRD'] : [$tabName];
            $resolved = $this->reader->resolveTabs($candidates, $this->reader->tabTitles($spreadsheetId));
            $actualTitle = reset($resolved);

            if (! $actualTitle) {
                Log::error("Push sync failed: tab {$tabName} not found in spreadsheet {$spreadsheetId}");

                return false;
            }

            $values = $this->reader->values($spreadsheetId, $actualTitle);
            if (empty($values)) {
                return false;
            }

            $header = $this->reader->locateHeader($values, DjaRowMapper::knownHeaders(), 1);
            $headerIndex = $header['index'] ?? 0;
            $headerMap = $header['map'] ?? [];
            $rows = array_slice($values, $headerIndex + 1);

            $kind = DjaRowMapper::kindForTab($tabName);
            $col = fn (string $field) => $this->mapper->columnFor($kind, $field, $headerMap);

            $taskIdx = $col('task_id');
            $statusIdx = $col('status');
            if ($taskIdx === null || $statusIdx === null) {
                Log::error("Push sync failed: Task ID / Status column not found in tab {$tabName}");

                return false;
            }

            $targetRow = null;
            foreach ($rows as $idx => $row) {
                if (trim((string) ($row[$taskIdx] ?? '')) === (string) $taskId) {
                    $targetRow = $headerIndex + $idx + 2; // 1-based sheet row
                    break;
                }
            }

            if ($targetRow === null) {
                Log::error("Push sync failed: Task ID {$taskId} not found in tab {$tabName}");

                return false;
            }

            $isOpen = $status === 'Open';
            $cells = [$statusIdx => $status];

            if ($kind === 'wo') {
                // WO has dedicated code + reason columns
                if (($codeIdx = $col('code_open')) !== null) {
                    $cells[$codeIdx] = $isOpen ? (string) $code : '';
                }
                if (($reasonIdx = $col('reason_open')) !== null) {
                    $cells[$reasonIdx] = $isOpen ? (string) $remarks : '';
                }
            } else {
                // DMI / NSRDI only have a remarks column: keep the code visible as a prefix
                if (($remarksIdx = $col('remarks')) !== null) {
                    $cells[$remarksIdx] = $isOpen ? trim(($code ? "[{$code}] " : '').$remarks) : '';
                }
            }

            if ($kind === 'nsrdi' && ($closeIdx = $col('close_date')) !== null) {
                $cells[$closeIdx] = $status === 'Closed' ? now()->format('d/m/Y') : '';
            }

            $range = "'".str_replace("'", "''", $actualTitle)."'";
            $data = [];
            foreach ($cells as $index => $value) {
                $letter = Coordinate::stringFromColumnIndex($index + 1);
                $data[] = new ValueRange(['range' => "{$range}!{$letter}{$targetRow}", 'values' => [[$value]]]);
            }

            $this->service->spreadsheets_values->batchUpdate($spreadsheetId, new BatchUpdateValuesRequest([
                'valueInputOption' => 'USER_ENTERED',
                'data' => $data,
            ]));

            return true;
        } catch (\Throwable $e) {
            Log::error('Push sync failed: '.$e->getMessage());

            return false;
        }
    }
}
