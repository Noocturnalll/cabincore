<?php

namespace App\Imports;

use App\Models\DailyJobAssignment;
use App\Models\WoLog;
use App\Models\DmiLog;
use App\Models\NsrdiLog;
use Maatwebsite\Excel\Concerns\ToModel;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class DjaSheetImport implements ToModel
{
    protected $tabName;

    public function __construct($tabName)
    {
        $this->tabName = $tabName;
    }

    public function model(array $row): \Illuminate\Database\Eloquent\Model|array|null
    {
        // Skip header row if detected
        if (isset($row[0]) && is_string($row[0]) && stripos($row[0], 'DATE') !== false) {
            return null;
        }

        $jobType = '';
        $taskId = '';
        $acReg = '';
        $description = '';
        $station = 'BTH';
        $dateFormatted = null;
        $djaDate = null;
        
        // Prepare arrays for log models
        $woData = [];
        $dmiData = [];
        $nsrdiData = [];

        $parseDate = function($val) {
            if (empty($val)) return null;
            if (is_numeric($val)) return Carbon::instance(Date::excelToDateTimeObject($val))->format('Y-m-d');
            try {
                return Carbon::parse($val)->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        };

        $parseStatus = function($val) {
            $val = ucfirst(strtolower(trim($val ?? '')));
            if (in_array($val, ['Open', 'Closed', 'Pending'])) {
                return $val;
            }
            return 'Open';
        };

        $truncate = function($val, $limit = 255) {
            if (empty($val)) return null;
            $val = (string)$val;
            return mb_strlen($val) > $limit ? mb_substr($val, 0, $limit) : $val;
        };

        $activeDate = now()->hour >= 18 ? now()->format('Y-m-d') : now()->subDays(1)->format('Y-m-d');

        if ($this->tabName === 'DJA') {
            $jobType = 'R01/WO';
            $djaDate = $parseDate($row[20] ?? null) ?? $activeDate;
            $acReg = $truncate($row[2] ?? '');
            $taskId = $truncate($row[3] ?? '');
            $description = (string)($row[5] ?? '');
            $station = $truncate($row[10] ?? 'BTH');
            
            $woData = [
                'date' => $djaDate,
                'work_group' => $truncate($row[1] ?? null),
                'aircraft_registration' => $acReg,
                'wo_number' => $taskId,
                'wo_category' => $truncate($row[4] ?? null),
                'description' => $description,
                'pn_picklist' => $truncate($row[6] ?? null),
                'man_hour' => isset($row[7]) ? (float)$row[7] : null,
                'operator' => $truncate($row[8] ?? null),
                'type' => $truncate($row[9] ?? null),
                'plan_station' => $truncate($row[10] ?? null),
                'remarks_ppc_to_lm' => (string)($row[11] ?? null),
                'act_station' => $truncate($row[12] ?? null),
                'status' => $parseStatus($row[13] ?? null),
                'code_open' => $truncate($row[14] ?? null),
                'reason_open' => $truncate($row[15] ?? null),
            ];
        } elseif ($this->tabName === 'DJA DMI') {
            $jobType = 'DMI';
            $djaDate = $parseDate($row[20] ?? null) ?? $activeDate;
            $acReg = $truncate($row[1] ?? '');
            $description = (string)($row[2] ?? '');
            $taskId = $truncate($row[4] ?? '');
            $station = $truncate($row[6] ?? 'BTH');
            
            $dmiData = [
                'date' => $djaDate,
                'aircraft_registration' => $acReg,
                'description' => $description,
                'pn_required' => $truncate($row[3] ?? null),
                'dmi_number' => $taskId,
                'dmi_category' => $truncate($row[5] ?? null),
                'plan_station' => $truncate($row[6] ?? null),
                'category' => $truncate($row[7] ?? null),
                'status' => $parseStatus($row[8] ?? null),
                'remarks' => (string)($row[9] ?? null),
            ];
        } elseif (in_array($this->tabName, ['DJA NSRD', 'DJA NSRDI'])) {
            $jobType = 'AOC/NSRDI';
            $djaDate = $parseDate($row[20] ?? null) ?? $activeDate;
            $acReg = $truncate($row[2] ?? '');
            $taskId = $truncate($row[3] ?? '');
            $description = (string)($row[4] ?? '');
            $station = $truncate($row[13] ?? 'BTH');
            
            $nsrdiData = [
                'plan_date' => $djaDate,
                'work_group' => $truncate($row[1] ?? null),
                'aircraft_registration' => $acReg,
                'nsrdi_number' => $taskId,
                'description' => $description,
                'category' => $truncate($row[5] ?? null),
                'report_date' => $parseDate($row[6] ?? null),
                'due_date' => $parseDate($row[7] ?? null),
                'part_number' => $truncate($row[8] ?? null),
                'part_description' => (string)($row[9] ?? null),
                'defer' => $truncate($row[10] ?? null),
                'aoc' => $truncate($row[11] ?? null),
                'type' => $truncate($row[12] ?? null),
                'plan_station' => $truncate($row[13] ?? null),
                'remarks' => (string)($row[14] ?? null),
                'status' => $parseStatus($row[15] ?? null),
                'close_date' => $parseDate($row[16] ?? null),
                'act_station' => null,
                'code_open' => null,
                'reason_open' => null,
            ];
        }

        if (empty($taskId)) {
            $taskId = 'AUTO-' . strtoupper(uniqid());
        }
        
        if (empty($acReg)) {
            return null; // Skip empty rows
        }

        $shouldSync = false;
        
        if ($this->tabName === 'DJA' || $this->tabName === 'DJA DMI') {
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

            $keywords = ($this->tabName === 'DJA DMI') ? $dmi_keywords : $wo_keywords;

            foreach ($keywords as $kw) {
                if (stripos($description, $kw) !== false) {
                    $shouldSync = true;
                    break;
                }
            }
        } elseif (in_array($this->tabName, ['DJA NSRD', 'DJA NSRDI'])) {
            $cat = $nsrdiData['category'] ?? '';
            if (in_array(strtoupper(trim($cat)), ['CBM', 'PAINTING'])) {
                $shouldSync = true;
            }
        }

        if ($shouldSync) {
            $activeDate = now()->hour >= 18 ? now()->format('Y-m-d') : now()->subDays(1)->format('Y-m-d');
            $djaDateVal = $djaDate ?? $activeDate;
            $dja = DailyJobAssignment::updateOrCreate(
                ['task_id' => $taskId, 'date' => $djaDateVal],
                [
                    'aircraft_registration' => $acReg,
                    'job_type' => $jobType,
                    'description' => $description,
                    'station' => $station
                ]
            );

            if ($jobType === 'R01/WO') {
                WoLog::updateOrCreate(['dja_id' => $dja->id], $woData);
            } elseif ($jobType === 'DMI') {
                DmiLog::updateOrCreate(['dja_id' => $dja->id], $dmiData);
            } elseif ($jobType === 'AOC/NSRDI') {
                NsrdiLog::updateOrCreate(['dja_id' => $dja->id], $nsrdiData);
            }
        }

        return null;
    }
}
