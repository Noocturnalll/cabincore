<?php

namespace App\Services;

use App\Models\DailyJobAssignment;
use App\Models\DmiLog;
use App\Models\NsrdiLog;
use App\Models\WoLog;
use Carbon\Carbon;
use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class GoogleSheetsSyncService
{
    protected $client;

    protected $service;

    public function __construct()
    {
        if (class_exists(Client::class)) {
            $this->client = new Client;
            $this->client->setApplicationName('Cabin Core DJA Sync');
            $this->client->setScopes([Sheets::SPREADSHEETS]);
            $this->client->setAccessType('offline');

            $path = storage_path('app/google-credentials.json');
            if (file_exists($path)) {
                $this->client->setAuthConfig($path);
            } else {
                Log::warning("Google credentials not found at $path");
            }

            $this->service = new Sheets($this->client);
        }
    }

    public function pullSync($spreadsheetId)
    {
        if (! $this->service || ! file_exists(storage_path('app/google-credentials.json'))) {
            Log::error('Cannot sync: Missing Google Credentials or SDK.');

            return false;
        }

        $tabs = ['DJA', 'DJA DMI', 'DJA NSRD', 'DJA NSRDI'];
        $pulledTaskIds = [];

        foreach ($tabs as $tabName) {
            try {
                $response = $this->service->spreadsheets_values->get($spreadsheetId, $tabName);
                $values = $response->getValues();

                if (empty($values)) {
                    continue;
                }

                $headers = array_shift($values);

                // Header Mapping
                $headerMap = [];
                foreach ($headers as $index => $header) {
                    $headerMap[strtoupper(trim($header))] = $index;
                }

                foreach ($values as $row) {
                    $taskId = $this->processRow($row, $headerMap, $tabName, $spreadsheetId);
                    if ($taskId) {
                        $pulledTaskIds[] = $taskId;
                    }
                }
            } catch (\Exception $e) {
                // Ignore 404s for tabs that might not exist (like if DJA NSRD is used instead of DJA NSRDI)
                Log::warning("Skipped tab {$tabName}: ".$e->getMessage());
            }
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

            // Cegah error jika taskId kosong
            if (empty($taskId)) {
                $taskId = 'AUTO-'.strtoupper(uniqid());
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
                    'date' => $date, 'aircraft_registration' => $acReg, 'description' => $description, 'status' => 'Open',
                ]);
            }

            return $taskId;
        }

        return null;
    }

    public function pushSync($spreadsheetId, $tabName, $taskId, $status, $remarks)
    {
        if (! $this->service || ! file_exists(storage_path('app/google-credentials.json'))) {
            return false;
        }

        try {
            $response = $this->service->spreadsheets_values->get($spreadsheetId, $tabName);
            $values = $response->getValues();

            if (empty($values)) {
                return false;
            }

            $headers = array_shift($values);
            $headerMap = [];
            foreach ($headers as $index => $header) {
                $headerMap[strtoupper(trim($header))] = $index;
            }

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
            } elseif (in_array($tabName, ['DJA NSRD', 'DJA NSRDI'])) {
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
                    $targetRowIndex = $idx + 2; // +1 for header, +1 for 1-based indexing in sheets
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
                $this->service->spreadsheets_values->update($spreadsheetId, "{$tabName}!{$cellRange}", $body, $params);
            } else {
                $bodyStatus = new ValueRange(['values' => [[$status]]]);
                $this->service->spreadsheets_values->update($spreadsheetId, "{$tabName}!{$statusColLetter}{$targetRowIndex}", $bodyStatus, $params);

                $bodyRemarks = new ValueRange(['values' => [[$remarks]]]);
                $this->service->spreadsheets_values->update($spreadsheetId, "{$tabName}!{$remarksColLetter}{$targetRowIndex}", $bodyRemarks, $params);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Push sync failed: '.$e->getMessage());

            return false;
        }
    }
}
