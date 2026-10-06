<?php

namespace App\Services;

use App\Models\DailyJobAssignment;
use App\Models\DmiLog;
use App\Models\NsrdiLog;
use App\Models\SyncSetting;
use App\Models\WoLog;
use Carbon\Carbon;
use Google\Service\Sheets\ValueRange;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class GoogleSheetsSyncService
{
    public const Tabs = ['DJA', 'DJA DMI', 'DJA NSRD', 'DJA NSRDI'];

    protected $service;

    protected ?string $lastError = null;

    public function __construct(protected GoogleSheetsReader $reader)
    {
        $this->service = $reader->isReady() ? $reader->service() : null;
    }

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Full DJA sync for one spreadsheet: pull tasks, then apply the Ghost Task Protocol.
     *
     * @return array{success: bool, message: string, synced: int, removed: int}
     */
    public function syncDja(string $spreadsheetId): array
    {
        $pulledTaskIds = $this->pullSync($spreadsheetId);

        if (! is_array($pulledTaskIds)) {
            $message = 'Sync DJA gagal: '.($this->lastError ?? 'unknown error');
            SyncSetting::recordResult(SyncSetting::Dja, false, $message);

            return ['success' => false, 'message' => $message, 'synced' => 0, 'removed' => 0];
        }

        // Jika ada tab yang gagal dibaca, jangan hapus task apapun (bisa jadi task-nya ada di tab tersebut)
        $removed = $this->lastError ? 0 : $this->removeGhostTasks($spreadsheetId, $pulledTaskIds);
        $synced = count(array_unique($pulledTaskIds));
        $message = "DJA tersinkron: {$synced} task, {$removed} task dihapus dari DJA.";

        if ($this->lastError) {
            $message .= ' Catatan: '.$this->lastError;
        }

        SyncSetting::recordResult(SyncSetting::Dja, true, $message);

        return ['success' => true, 'message' => $message, 'synced' => $synced, 'removed' => $removed];
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

        if (! $this->service) {
            $this->lastError = 'File kredensial Google (storage/app/google-credentials.json) tidak ditemukan.';
            Log::error('Cannot sync: Missing Google Credentials or SDK.');

            return false;
        }

        try {
            $tabs = $this->reader->resolveTabs(self::Tabs, $this->reader->tabTitles($spreadsheetId));
        } catch (\Throwable $e) {
            $this->lastError = 'Spreadsheet tidak bisa dibuka. Pastikan sheet sudah di-share ke email service account. Detail: '.mb_substr($e->getMessage(), 0, 300);
            Log::error("DJA sync: cannot open spreadsheet {$spreadsheetId}: ".$e->getMessage());

            return false;
        }

        if ($tabs === []) {
            $this->lastError = 'Tidak ada tab DJA / DJA DMI / DJA NSRDI di spreadsheet ini.';

            return false;
        }

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

                $header = $this->reader->locateHeader($values, [
                    'TASK ID', 'WO NUMBER', 'NO WO', 'WO', 'AC REG', 'A/C REG', 'AIRCRAFT', 'REG',
                    'DESCRIPTION', 'WO DESCRIPTION', 'TASK CARD DESCRIPTION', 'CATEGORY', 'TRADE', 'ATA', 'STATUS',
                ], 1);

                $headerIndex = $header['index'] ?? 0;
                $headerMap = $header['map'] ?? [];

                foreach (array_slice($values, $headerIndex + 1) as $row) {
                    $taskId = $this->processRow($row, $headerMap, $tabName, $spreadsheetId);
                    if ($taskId) {
                        $pulledTaskIds[] = $taskId;
                    }
                }
            } catch (\Throwable $e) {
                $failedTabs[] = $actualTitle;
                Log::warning("Skipped tab {$actualTitle}: ".$e->getMessage());
            }
        }

        if ($tabsRead === 0) {
            $this->lastError = 'Semua tab gagal dibaca: '.implode(', ', $failedTabs);

            return false;
        }

        if ($failedTabs) {
            $this->lastError = 'Tab gagal dibaca: '.implode(', ', $failedTabs);
        }

        return $pulledTaskIds;
    }

    protected function processRow($row, $headerMap, $tabName, $spreadsheetId)
    {
        // Helper to find column index safely, with fallback to hardcoded column index based on tab
        $getIndex = function ($possibleNames, $fallbackIndex) use ($headerMap) {
            foreach ($possibleNames as $name) {
                if (isset($headerMap[$name])) {
                    return $headerMap[$name];
                }
            }

            return $fallbackIndex;
        };

        // Fallback indexes based on DjaSheetImport mappings
        $catFallback = -1;
        $ataFallback = -1;
        $descFallback = -1;
        $taskFallback = -1;
        $acFallback = -1;

        if ($tabName === 'DJA') {
            $catFallback = 4;
            $descFallback = 5;
            $taskFallback = 3;
            $acFallback = 2;
        } elseif ($tabName === 'DJA DMI') {
            $catFallback = 5;
            $descFallback = 2;
            $taskFallback = 4;
            $acFallback = 1;
        } elseif (in_array($tabName, ['DJA NSRD', 'DJA NSRDI'])) {
            $catFallback = 5;
            $descFallback = 4;
            $taskFallback = 3;
            $acFallback = 2;
        }

        $catIdx = $getIndex(['CATEGORY', 'TRADE', 'DIVISI'], $catFallback);
        $ataIdx = $getIndex(['ATA', 'ATA CHAPTER', 'CHAPTER'], $ataFallback);
        $descIdx = $getIndex(['DESCRIPTION', 'WO DESCRIPTION', 'TASK CARD DESCRIPTION', 'DESC'], $descFallback);
        $taskIdx = $getIndex(['TASK ID', 'WO NUMBER', 'NO WO', 'WO'], $taskFallback);
        $acIdx = $getIndex(['AC REG', 'A/C REG', 'AIRCRAFT', 'REG', 'AIRCRAFT REGISTRATION'], $acFallback);

        $category = $catIdx >= 0 && isset($row[$catIdx]) ? trim($row[$catIdx]) : '';
        $ata = $ataIdx >= 0 && isset($row[$ataIdx]) ? trim($row[$ataIdx]) : '';
        $description = $descIdx >= 0 && isset($row[$descIdx]) ? trim($row[$descIdx]) : '';
        $taskId = $taskIdx >= 0 && isset($row[$taskIdx]) ? trim($row[$taskIdx]) : '';
        $acReg = $acIdx >= 0 && isset($row[$acIdx]) ? trim($row[$acIdx]) : '';

        $parseDate = function ($val) {
            if (empty($val)) {
                return null;
            }
            if (is_numeric($val)) {
                return Carbon::instance(Date::excelToDateTimeObject($val))->format('Y-m-d');
            }
            try {
                return Carbon::parse($val)->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        };

        $activeDate = now()->hour >= 18 ? now()->format('Y-m-d') : now()->subDays(1)->format('Y-m-d');

        $dateIdx = 20; // Kolom ke-21 / Refresh Date
        $dateStr = isset($row[$dateIdx]) ? trim($row[$dateIdx]) : null;
        $date = $parseDate($dateStr) ?? $activeDate;

        $shouldSync = false;

        // A. Logika untuk Sheet NSRDI
        if (in_array($tabName, ['DJA NSRD', 'DJA NSRDI'])) {
            if (in_array(strtoupper($category), ['CBM', 'PAINTING'])) {
                $shouldSync = true;
            }
        }
        // B. Logika untuk Sheet WO & DMI
        elseif (in_array($tabName, ['DJA', 'DJA DMI'])) {
            // Bersihkan ATA (ambil 2 digit pertama jika format aneh)
            preg_match('/^(\d{2})/', $ata, $matches);
            $ataPrefix = $matches[1] ?? '';

            // Lapis 1 (Penolakan Mutlak)
            if (in_array($ataPrefix, ['72', '32'])) {
                $shouldSync = false;
            }
            // Lapis 2 (Penerimaan Mutlak)
            elseif (in_array($ataPrefix, ['11', '23', '25', '33', '35', '38', '44', '52', '56'])) {
                $shouldSync = true;
            }
            // Lapis 3 (Dictionary Matching / Deskripsi)
            else {
                $wo_keywords = [
                    'LIFE VEST', 'UNDERSEAT', 'INFANT', 'ESCAPE SLIDE', 'OXYGEN', 'OXYGEN MASK', 'MEGAPHONE', 'FIRE EXTINGUISHER', 'FIRE BOTTLE', 'FIREX', 'PORTABLE FIREX',
                    'POTABLE WATER', 'WATER FILTER', 'STERILIZATION', 'WASTE COMPARTMENT', 'VACUUM', 'LAVATORY',
                    'PASSENGER CABIN', 'SEAT', 'ROLLER BLIND', 'COMPARTMENT WINDOWS', 'GALLEY',
                    'PEST CONTROL', 'CLEANING', 'CHEMICAL',
                ];

                $dmi_keywords = [
                    'WINDOW LIGHT', 'CEILING LIGHT', 'ENTRY LIGHT', 'ILLUMINATE', 'NOT ILL', 'ALWAYS ILLUMINATE',
                    'SEAT', 'RECLINE', 'AUTORECLINE', 'UPRIGHT POSITION', 'SEAT BELT', 'TRAY TABLE',
                    'FASTEN', 'FASTEN SEAT BELT', 'SMOKING', 'NO SMOKING SIGN',
                    'LAV', 'LAVATORY', 'FLUSHING', 'HANDSET', 'CFD',
                ];

                $keywords = ($tabName === 'DJA DMI') ? $dmi_keywords : $wo_keywords;

                foreach ($keywords as $kw) {
                    if (stripos($description, $kw) !== false) {
                        $shouldSync = true;
                        break;
                    }
                }
            }
        }

        // Simpan ke DB jika lolos filter
        if ($shouldSync) {
            $jobType = 'R01/WO';
            if ($tabName === 'DJA DMI') {
                $jobType = 'DMI';
            }
            if (in_array($tabName, ['DJA NSRD', 'DJA NSRDI'])) {
                $jobType = 'AOC/NSRDI';
            }

            // Task ID stabil untuk baris tanpa nomor, supaya sync berulang tidak membuat duplikat
            if (empty($taskId)) {
                $taskId = 'AUTO-'.strtoupper(substr(md5($tabName.'|'.$acReg.'|'.$description), 0, 12));
            }

            $dja = DailyJobAssignment::updateOrCreate(
                ['task_id' => $taskId, 'date' => $date],
                [
                    'source_spreadsheet_id' => $spreadsheetId,
                    'aircraft_registration' => $acReg,
                    'job_type' => $jobType,
                    'description' => $description,
                    'station' => 'BTH',
                ]
            );

            if ($jobType === 'R01/WO') {
                WoLog::firstOrCreate(['dja_id' => $dja->id], [
                    'date' => $date, 'aircraft_registration' => $acReg, 'description' => $description, 'status' => 'Open',
                ]);
            } elseif ($jobType === 'DMI') {
                DmiLog::firstOrCreate(['dja_id' => $dja->id], [
                    'date' => $date, 'aircraft_registration' => $acReg, 'description' => $description, 'status' => 'Open',
                ]);
            } elseif ($jobType === 'AOC/NSRDI') {
                NsrdiLog::firstOrCreate(['dja_id' => $dja->id], [
                    'plan_date' => $date, 'report_date' => $date, 'aircraft_registration' => $acReg, 'description' => $description, 'status' => 'Open',
                ]);
            }

            return $taskId;
        }

        return null;
    }

    public function pushSync($spreadsheetId, $tabName, $taskId, $status, $remarks)
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
            $range = "'".str_replace("'", "''", $actualTitle)."'";

            if (empty($values)) {
                return false;
            }

            $header = $this->reader->locateHeader($values, ['TASK ID', 'WO NUMBER', 'NO WO', 'WO', 'STATUS', 'REMARKS', 'DESCRIPTION', 'AC REG', 'REG'], 1);
            $headerIndex = $header['index'] ?? 0;
            $headerMap = $header['map'] ?? [];
            $values = array_slice($values, $headerIndex + 1);

            $getIndex = function ($possibleNames, $fallbackIndex) use ($headerMap) {
                foreach ($possibleNames as $name) {
                    if (isset($headerMap[$name])) {
                        return $headerMap[$name];
                    }
                }

                return $fallbackIndex;
            };

            $taskFallback = -1;
            if ($tabName === 'DJA') {
                $taskFallback = 3;
            } elseif ($tabName === 'DJA DMI') {
                $taskFallback = 4;
            } elseif ($isNsrdi) {
                $taskFallback = 3;
            }

            $taskIdx = $getIndex(['TASK ID', 'WO NUMBER', 'NO WO', 'WO'], $taskFallback);
            $statusIdx = $getIndex(['STATUS', 'STATE'], 10); // default column K
            $remarksIdx = $getIndex(['REMARKS', 'REASON', 'HOLD REMARKS', 'NOTE'], 11); // default column L

            if ($taskIdx < 0) {
                Log::error("Push sync failed: Task ID column not found in tab {$tabName}");

                return false;
            }

            $targetRowIndex = -1;
            foreach ($values as $idx => $row) {
                $currentTask = isset($row[$taskIdx]) ? trim($row[$taskIdx]) : '';
                if ($currentTask === $taskId) {
                    $targetRowIndex = $headerIndex + $idx + 2; // rows above header + header + 1-based indexing
                    break;
                }
            }

            if ($targetRowIndex === -1) {
                Log::error("Push sync failed: Task ID {$taskId} not found in tab {$tabName}");

                return false;
            }

            $statusColLetter = Coordinate::stringFromColumnIndex($statusIdx + 1);
            $remarksColLetter = Coordinate::stringFromColumnIndex($remarksIdx + 1);

            $params = ['valueInputOption' => 'USER_ENTERED'];

            if ($remarksIdx === $statusIdx + 1) {
                $cellRange = "{$statusColLetter}{$targetRowIndex}:{$remarksColLetter}{$targetRowIndex}";
                $body = new ValueRange(['values' => [[$status, $remarks]]]);
                $this->service->spreadsheets_values->update($spreadsheetId, "{$range}!{$cellRange}", $body, $params);
            } else {
                $bodyStatus = new ValueRange(['values' => [[$status]]]);
                $this->service->spreadsheets_values->update($spreadsheetId, "{$range}!{$statusColLetter}{$targetRowIndex}", $bodyStatus, $params);

                $bodyRemarks = new ValueRange(['values' => [[$remarks]]]);
                $this->service->spreadsheets_values->update($spreadsheetId, "{$range}!{$remarksColLetter}{$targetRowIndex}", $bodyRemarks, $params);
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Push sync failed: '.$e->getMessage());

            return false;
        }
    }
}
