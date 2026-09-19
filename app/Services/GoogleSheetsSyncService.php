<?php

namespace App\Services;

use Google\Client;
use Google\Service\Sheets;
use App\Models\DailyJobAssignment;
use App\Models\WoLog;
use App\Models\CmlLog;
use App\Models\DmiLog;
use App\Models\NsrdiLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class GoogleSheetsSyncService
{
    protected $client;
    protected $service;

    public function __construct()
    {
        if (class_exists(Client::class)) {
            $this->client = new Client();
            $this->client->setApplicationName('Cabin Core DJA Sync');
            $this->client->setScopes([\Google\Service\Sheets::SPREADSHEETS]);
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
        if (!$this->service || !file_exists(storage_path('app/google-credentials.json'))) {
            Log::error("Cannot sync: Missing Google Credentials or SDK.");
            return false;
        }

        $tabs = ['DJA', 'DJA DMI', 'DJA NSRD', 'DJA NSRDI'];

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
                    $this->processRow($row, $headerMap, $tabName, $spreadsheetId);
                }
            } catch (\Exception $e) {
                // Ignore 404s for tabs that might not exist (like if DJA NSRD is used instead of DJA NSRDI)
                Log::warning("Skipped tab {$tabName}: " . $e->getMessage());
            }
        }
        
        return true;
    }

    protected function processRow($row, $headerMap, $tabName, $spreadsheetId)
    {
        // Helper to find column index safely
        $getIndex = function($possibleNames) use ($headerMap) {
            foreach ($possibleNames as $name) {
                if (isset($headerMap[$name])) return $headerMap[$name];
            }
            return -1;
        };

        $catIdx  = $getIndex(['CATEGORY', 'TRADE', 'DIVISI']);
        $ataIdx  = $getIndex(['ATA', 'ATA CHAPTER', 'CHAPTER']);
        $descIdx = $getIndex(['DESCRIPTION', 'WO DESCRIPTION', 'TASK CARD DESCRIPTION', 'DESC']);
        $taskIdx = $getIndex(['TASK ID', 'WO NUMBER', 'NO WO', 'WO']);
        $acIdx   = $getIndex(['AC REG', 'A/C REG', 'AIRCRAFT', 'REG', 'AIRCRAFT REGISTRATION']);

        $category    = $catIdx >= 0 && isset($row[$catIdx]) ? trim($row[$catIdx]) : '';
        $ata         = $ataIdx >= 0 && isset($row[$ataIdx]) ? trim($row[$ataIdx]) : '';
        $description = $descIdx >= 0 && isset($row[$descIdx]) ? trim($row[$descIdx]) : '';
        $taskId      = $taskIdx >= 0 && isset($row[$taskIdx]) ? trim($row[$taskIdx]) : '';
        $acReg       = $acIdx >= 0 && isset($row[$acIdx]) ? trim($row[$acIdx]) : '';
        $date        = Carbon::today()->toDateString(); 

        $shouldSync = false;

        // A. Logika untuk Sheet NSRDI
        if (in_array($tabName, ['DJA NSRD', 'DJA NSRDI'])) {
            if (in_array(strtoupper($category), ['CBM', 'PAINTING'])) {
                $shouldSync = true;
            }
        } 
        // B. Logika untuk Sheet WO & DMI
        else if (in_array($tabName, ['DJA', 'DJA DMI'])) {
            // Bersihkan ATA (ambil 2 digit pertama jika format aneh)
            preg_match('/^(\d{2})/', $ata, $matches);
            $ataPrefix = $matches[1] ?? '';

            // Lapis 1 (Penolakan Mutlak)
            if (in_array($ataPrefix, ['72', '32'])) {
                $shouldSync = false;
            } 
            // Lapis 2 (Penerimaan Mutlak)
            else if (in_array($ataPrefix, ['11', '23', '25', '33', '35', '38', '44', '52', '56'])) {
                $shouldSync = true;
            } 
            // Lapis 3 (Dictionary Matching / Deskripsi)
            else {
                $wo_keywords = [
                    'LIFE VEST', 'UNDERSEAT', 'INFANT', 'ESCAPE SLIDE', 'OXYGEN', 'OXYGEN MASK', 'MEGAPHONE', 'FIRE EXTINGUISHER', 'FIRE BOTTLE', 'FIREX', 'PORTABLE FIREX',
                    'POTABLE WATER', 'WATER FILTER', 'STERILIZATION', 'WASTE COMPARTMENT', 'VACUUM', 'LAVATORY',
                    'PASSENGER CABIN', 'SEAT', 'ROLLER BLIND', 'COMPARTMENT WINDOWS', 'GALLEY',
                    'PEST CONTROL', 'CLEANING', 'CHEMICAL'
                ];
                
                $dmi_keywords = [
                    'WINDOW LIGHT', 'CEILING LIGHT', 'ENTRY LIGHT', 'ILLUMINATE', 'NOT ILL', 'ALWAYS ILLUMINATE', 
                    'SEAT', 'RECLINE', 'AUTORECLINE', 'UPRIGHT POSITION', 'SEAT BELT', 'TRAY TABLE',
                    'FASTEN', 'FASTEN SEAT BELT', 'SMOKING', 'NO SMOKING SIGN',
                    'LAV', 'LAVATORY', 'FLUSHING', 'HANDSET', 'CFD'
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
            if ($tabName === 'DJA DMI') $jobType = 'DMI';
            if (in_array($tabName, ['DJA NSRD', 'DJA NSRDI'])) $jobType = 'AOC/NSRDI';

            // Cegah error jika taskId kosong
            if (empty($taskId)) {
                $taskId = 'AUTO-' . strtoupper(uniqid()); 
            }

            $dja = DailyJobAssignment::updateOrCreate(
                ['task_id' => $taskId, 'date' => $date],
                [
                    'source_spreadsheet_id' => $spreadsheetId,
                    'aircraft_registration' => $acReg,
                    'job_type' => $jobType,
                    'description' => $description,
                    'station' => 'BTH'
                ]
            );

            if ($jobType === 'R01/WO') {
                WoLog::firstOrCreate(['dja_id' => $dja->id], [
                    'date' => $date, 'aircraft_registration' => $acReg, 'description' => $description, 'status' => 'Open'
                ]);
            } elseif ($jobType === 'DMI') {
                DmiLog::firstOrCreate(['dja_id' => $dja->id], [
                    'date' => $date, 'aircraft_registration' => $acReg, 'description' => $description, 'status' => 'Open'
                ]);
            } elseif ($jobType === 'AOC/NSRDI') {
                NsrdiLog::firstOrCreate(['dja_id' => $dja->id], [
                    'date' => $date, 'aircraft_registration' => $acReg, 'description' => $description, 'status' => 'Open'
                ]);
            }
        }
    }

    public function pushSync($spreadsheetId, $tabName, $cellRange, $status, $remarks)
    {
        if (!$this->service || !file_exists(storage_path('app/google-credentials.json'))) {
            return false;
        }

        try {
            $body = new \Google\Service\Sheets\ValueRange([
                'values' => [
                    [$status, $remarks]
                ]
            ]);

            $params = [
                'valueInputOption' => 'USER_ENTERED'
            ];

            $this->service->spreadsheets_values->update($spreadsheetId, "{$tabName}!{$cellRange}", $body, $params);
            return true;
        } catch (\Exception $e) {
            Log::error("Push sync failed: " . $e->getMessage());
            return false;
        }
    }
}
