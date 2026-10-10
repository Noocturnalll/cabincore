<?php

namespace App\Imports\Sheets;

use App\Imports\CalculatedValueBinder;
use App\Models\CmlLog;
use App\Models\DailyJobAssignment;
use App\Models\DmiLog;
use App\Models\NsrdiLog;
use App\Models\WoLog;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class DailyReportSheetImport extends CalculatedValueBinder implements ToCollection, WithChunkReading, WithCustomValueBinder
{
    protected $sheetType;

    public function __construct(string $sheetType)
    {
        $this->sheetType = $sheetType;
    }

    public function chunkSize(): int
    {
        return 500;
    }

    protected $headerRow = [];

    protected $headerFound = false;

    protected $rowCount = 0;

    public function collection(Collection $rows): void
    {
        Log::info('Sheet type: '.$this->sheetType.' processing '.$rows->count().' rows.');

        foreach ($rows as $row) {
            $this->rowCount++;

            if (! $this->headerFound) {
                // Try to find header in the first 15 rows
                $rowValues = $row->toArray();
                $lowercaseRow = array_map(function ($v) {
                    return strtolower(trim((string) $v));
                }, $rowValues);

                if (in_array('ac reg', $lowercaseRow) || in_array('reg', $lowercaseRow)) {
                    foreach ($rowValues as $index => $colName) {
                        $this->headerRow[$index] = HeadingRowFormatter::format($colName);
                    }
                    $this->headerFound = true;
                    Log::info('Header found at row '.$this->rowCount.' in sheet '.$this->sheetType);
                }

                if ($this->rowCount >= 15 && ! $this->headerFound) {
                    Log::warning('Header not found in the first 15 rows for sheet: '.$this->sheetType);

                    return; // Skip if no header found
                }

                continue;
            }

            // Map row to associative array
            $assocRow = [];
            foreach ($this->headerRow as $index => $key) {
                if ($key) {
                    $assocRow[$key] = $row[$index] ?? null;
                }
            }

            // Check if the row is entirely empty or missing AC REG (like '```')
            $acRegVal = $assocRow['ac_reg'] ?? $assocRow['reg'] ?? null;
            if (empty(trim((string) $acRegVal)) || trim((string) $acRegVal) === '```') {
                continue;
            }

            $date = $this->parseDate($assocRow['date'] ?? null);

            if ($this->sheetType === 'CML') {
                $this->processCml($assocRow, $date);
            } elseif ($this->sheetType === 'WO') {
                $this->processWo($assocRow, $date, false);
            } elseif ($this->sheetType === 'DMI') {
                $this->processDmi($assocRow, $date, false);
            } elseif ($this->sheetType === 'NSRDI') {
                $this->processNsrdi($assocRow, $date, false);
            } elseif ($this->sheetType === 'UNPLANNED') {
                $docType = strtoupper(trim($assocRow['doc_type'] ?? ''));
                if ($docType === 'WO') {
                    $this->processWo($assocRow, $date, true);
                } elseif ($docType === 'NSRDI') {
                    $this->processNsrdi($assocRow, $date, true);
                } elseif ($docType === 'DMI') {
                    $this->processDmi($assocRow, $date, true);
                }
            }
        }
    }

    private function parseDate($dateVal)
    {
        if (! $dateVal) {
            return null;
        }
        try {
            if (is_numeric($dateVal)) {
                return Date::excelToDateTimeObject($dateVal)->format('Y-m-d');
            }

            // Jika user ngetik manual teks '8-Jan-27'
            return Carbon::parse($dateVal)->format('Y-m-d');
        } catch (\Exception $e) {
            return null; // Abaikan jika format tidak dikenali
        }
    }

    private function processCml($row, $date)
    {
        $acReg = $row['ac_reg'] ?? $row['reg'] ?? null;
        if (empty($acReg)) {
            return;
        }

        // Headers from Image 1: DATE, OPERATOR, AC REG, A/C STAT, STA, DOC TYPE, NO DOC, ACTION TAKEN, STATUS
        CmlLog::create([
            'dja_id' => null,
            'date' => $date,
            'operator' => $row['operator'] ?? null,
            'aircraft_registration' => $acReg,
            'ac_status' => $row['ac_stat'] ?? $row['a_c_stat'] ?? null,
            'station' => $row['sta'] ?? null,
            'doc_type' => 'CML', // Or $row['doc_type']
            'no_doc' => $row['no_doc'] ?? null,
            'description' => $row['action_taken'] ?? $row['action_taken_reason'] ?? null,
            'status' => ucfirst(strtolower($row['status'] ?? 'Closed')),
        ]);
    }

    private function processWo($row, $date, $isUnplanned = false)
    {
        $woNumber = $row['wo'] ?? $row['no_doc'] ?? null;
        if (! $woNumber) {
            return;
        }

        $existing = WoLog::where('wo_number', $woNumber)->first();

        $acReg = $row['ac_reg'] ?? $row['reg'] ?? ($existing->aircraft_registration ?? null);
        if (empty($acReg)) {
            return;
        }

        $djaId = null;
        if (! $isUnplanned && $date) {
            $dja = DailyJobAssignment::firstOrCreate(
                ['aircraft_registration' => $acReg, 'date' => $date],
                ['job_type' => 'R01/WO', 'station' => $row['act_sta'] ?? $row['sta'] ?? 'Unknown', 'task_id' => 'IMPORTED']
            );
            $djaId = $dja->id;
        }

        $data = [
            'aircraft_registration' => $acReg,
            'act_station' => $row['act_sta'] ?? $row['sta'] ?? ($existing->act_station ?? null),
            'plan_station' => $row['plan_sta'] ?? ($existing->plan_station ?? null),
            'description' => $row['wo_description'] ?? $row['defect_description'] ?? ($existing->description ?? null),
            'status' => ucfirst(strtolower($row['status'] ?? 'Closed')),
            'date' => $date ?? ($existing->date ?? null),
            'operator' => $row['operator'] ?? $row['aoc'] ?? ($existing->operator ?? null),
            'work_group' => $row['wg'] ?? ($existing->work_group ?? null),
            'wo_category' => $row['wo_cat'] ?? ($existing->wo_category ?? null),
            'pn_picklist' => $row['pn_picklist_no_picklist'] ?? $row['pn_picklist'] ?? ($existing->pn_picklist ?? null),
            'man_hour' => $this->parseDecimal($row['man_hours'] ?? ($existing->man_hour ?? null)),
            'type' => $row['type'] ?? ($existing->type ?? null),
            'remarks_ppc_to_lm' => $row['remarks_ppc_to_lm'] ?? ($existing->remarks_ppc_to_lm ?? null),
            'hold_reason_category' => $row['code_open'] ?? ($existing->hold_reason_category ?? null),
            'hold_remarks' => $row['reason_open'] ?? $row['remarks'] ?? ($existing->hold_remarks ?? null),
        ];

        if ($existing) {
            $data['dja_id'] = $existing->dja_id ?? $djaId;
            $data['is_submitted'] = true;
            $existing->update($data);
        } else {
            $data['dja_id'] = $djaId;
            $data['wo_number'] = $woNumber;
            $data['is_submitted'] = true;
            WoLog::create($data);
        }
    }

    private function processDmi($row, $date, $isUnplanned = false)
    {
        $dmiNumber = $row['dmi_no'] ?? $row['no_doc'] ?? null;
        if (! $dmiNumber) {
            return;
        }

        $existing = DmiLog::where('dmi_number', $dmiNumber)->first();

        $acReg = $row['reg'] ?? $row['ac_reg'] ?? ($existing->aircraft_registration ?? null);
        if (empty($acReg)) {
            return;
        }

        $djaId = null;
        if (! $isUnplanned && $date) {
            $dja = DailyJobAssignment::firstOrCreate(
                ['aircraft_registration' => $acReg, 'date' => $date],
                ['job_type' => 'DMI', 'station' => $row['act_sta'] ?? $row['sta'] ?? 'Unknown', 'task_id' => 'IMPORTED']
            );
            $djaId = $dja->id;
        }

        $data = [
            'aircraft_registration' => $acReg,
            'plan_station' => $row['plan_sta'] ?? ($existing->plan_station ?? null),
            'act_station' => $row['sta'] ?? ($existing->act_station ?? null),
            'description' => $row['dmi_description'] ?? $row['defect_description'] ?? ($existing->description ?? null),
            'status' => ucfirst(strtolower($row['status'] ?? 'Closed')),
            'date' => $date ?? ($existing->date ?? null),
            'pn_required' => $row['pn_required'] ?? ($existing->pn_required ?? null),
            'dmi_category' => $row['dmi_cat'] ?? ($existing->dmi_category ?? null),
            'category' => $row['cat'] ?? ($existing->category ?? null),
            'remarks' => $row['remark'] ?? $row['remarks'] ?? ($existing->remarks ?? null),
        ];

        if ($existing) {
            $data['dja_id'] = $existing->dja_id ?? $djaId;
            $data['is_submitted'] = true;
            $existing->update($data);
        } else {
            $data['dja_id'] = $djaId;
            $data['dmi_number'] = $dmiNumber;
            $data['is_submitted'] = true;
            DmiLog::create($data);
        }
    }

    private function processNsrdi($assocRow, $date, $isUnplanned = false)
    {
        $nsrdiNumber = $assocRow['nsrdi'] ?? $assocRow['no_doc'] ?? null;
        if (! $nsrdiNumber) {
            return;
        }

        $existing = NsrdiLog::where('nsrdi_number', $nsrdiNumber)->first();

        $acReg = $assocRow['ac_reg'] ?? $assocRow['reg'] ?? ($existing->aircraft_registration ?? null);
        if (empty($acReg)) {
            return;
        }

        // According to the new rules, for Planned NSRDI, the main date is 'PLAN DATE'.
        $planDateVal = $this->parseDate($assocRow['plan_date'] ?? null);
        $refreshDateVal = $this->parseDate($assocRow['refresh_date'] ?? null);

        // Ensure we get a date even if the column is named differently
        $djaDate = $date ?? $planDateVal ?? $this->parseDate($assocRow['report_date'] ?? null);

        $djaId = null;
        if (! $isUnplanned && $djaDate) {
            $dja = DailyJobAssignment::firstOrCreate(
                ['aircraft_registration' => $acReg, 'date' => $djaDate],
                ['job_type' => 'AOC/NSRDI', 'station' => $assocRow['act_sta'] ?? $assocRow['sta'] ?? 'Unknown', 'task_id' => 'IMPORTED']
            );
            $djaId = $dja->id;
        }

        $data = [
            'aircraft_registration' => $acReg,
            'act_station' => $assocRow['act_sta'] ?? $assocRow['sta'] ?? ($existing->act_station ?? null),
            'plan_station' => $assocRow['plan_sta'] ?? ($existing->plan_station ?? null),
            'description' => $assocRow['finding_description'] ?? $assocRow['defect_description'] ?? ($existing->description ?? null),
            'status' => ucfirst(strtolower($assocRow['status'] ?? 'Closed')),
            'report_date' => $date ?? $this->parseDate($assocRow['report_date'] ?? null) ?? ($existing->report_date ?? null),
            'refresh_date' => $refreshDateVal ?? ($existing->refresh_date ?? null),
            'plan_date' => $planDateVal ?? ($existing->plan_date ?? null),
            'work_group' => $assocRow['wg'] ?? ($existing->work_group ?? null),
            'category' => $assocRow['category'] ?? ($existing->category ?? null),
            'due_date' => $this->parseDate($assocRow['due_date'] ?? null) ?? ($existing->due_date ?? null),
            'part_number' => $assocRow['part_number'] ?? ($existing->part_number ?? null),
            'part_description' => $assocRow['part_description'] ?? ($existing->part_description ?? null),
            'defer' => $assocRow['defer'] ?? ($existing->defer ?? null),
            'aoc' => $assocRow['aoc'] ?? ($existing->aoc ?? null),
            'type' => $assocRow['type'] ?? ($existing->type ?? null),
            'remarks' => $assocRow['remarks'] ?? ($existing->remarks ?? null),
            'close_date' => $this->parseDate($assocRow['close_date'] ?? null) ?? ($existing->close_date ?? null),
            'hold_reason_category' => $assocRow['code_reason'] ?? ($existing->hold_reason_category ?? null),
            'hold_remarks' => $assocRow['reason_open'] ?? ($existing->hold_remarks ?? null),
        ];

        if ($existing) {
            $data['dja_id'] = $existing->dja_id ?? $djaId;
            $data['is_submitted'] = true;
            $existing->update($data);
        } else {
            $data['dja_id'] = $djaId;
            $data['nsrdi_number'] = $nsrdiNumber;
            $data['is_submitted'] = true;
            NsrdiLog::create($data);
        }
    }

    private function parseDecimal($val)
    {
        if (is_string($val)) {
            $val = str_replace(',', '.', trim($val));
        }

        return is_numeric($val) ? (float) $val : null;
    }
}
