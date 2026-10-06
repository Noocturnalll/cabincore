<?php

namespace App\Imports;

use App\Models\NsrdiLog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class NsrdiImport implements ToModel, WithHeadingRow
{
    protected function parseDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return Date::excelToDateTimeObject($value)->format('Y-m-d');
            }

            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    public function model(array $row): Model|array|null
    {
        $nsrdiNumber = $row['nsrdi_number'] ?? $row['nsrdi'] ?? $row['defect'] ?? $row['doc_no'] ?? null;
        $acReg = $row['aircraft_registration'] ?? $row['ac_reg'] ?? $row['ac'] ?? $row['reg'] ?? null;

        if (! $nsrdiNumber && ! $acReg) {
            return null;
        }

        $codeReason = $row['hold_reason_category'] ?? $row['code_reason'] ?? $row['code_open'] ?? null;
        $remarks = $row['hold_remarks'] ?? $row['reason_open'] ?? $row['remarks'] ?? null;

        $data = [
            'work_group' => $row['work_group'] ?? $row['wg'] ?? null,
            'aircraft_registration' => $acReg,
            'nsrdi_number' => $nsrdiNumber,
            'description' => $row['finding_description'] ?? $row['description'] ?? $row['defect_description'] ?? null,
            'category' => $row['category'] ?? null,
            'report_date' => $this->parseDate($row['report_date'] ?? null),
            'due_date' => $this->parseDate($row['due_date'] ?? null),
            'part_number' => $row['part_number'] ?? $row['pn'] ?? null,
            'part_description' => $row['part_description'] ?? null,
            'defer' => $row['defer'] ?? null,
            'aoc' => $row['aoc'] ?? null,
            'type' => $row['type'] ?? null,
            'plan_station' => $row['plan_station'] ?? $row['plan_sta'] ?? null,
            'plan_date' => $this->parseDate($row['plan_date'] ?? $row['date'] ?? null),
            'remarks' => $row['remarks'] ?? null,
            'status' => $row['status'] ?? 'Open',
            'close_date' => $this->parseDate($row['close_date'] ?? null),
            'act_station' => $row['act_station'] ?? $row['act_sta'] ?? null,
            'hold_reason_category' => $codeReason,
            'hold_remarks' => $remarks,
            'code_open' => $codeReason,
            'reason_open' => $remarks,
            'import_source' => 'excel',
        ];

        if ($nsrdiNumber) {
            return NsrdiLog::updateOrCreate(
                ['nsrdi_number' => $nsrdiNumber],
                $data
            );
        }

        return new NsrdiLog($data);
    }
}
