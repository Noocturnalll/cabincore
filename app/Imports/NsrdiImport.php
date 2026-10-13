<?php

namespace App\Imports;

use App\Models\DailyJobAssignment;
use App\Models\NsrdiLog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class NsrdiImport implements ToModel, WithHeadingRow
{
    public function __construct(public ?string $defaultCategory = null) {}

    protected function parseDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return Date::excelToDateTimeObject((float) $value)->format('Y-m-d');
            }

            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function cleanStr(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $s = trim((string) $value);

        return $s === '' ? null : $s;
    }

    public function model(array $row): Model|array|null
    {
        $nsrdiNumber = $this->cleanStr($row['nsrdi_number'] ?? $row['nsrdi'] ?? $row['defect'] ?? $row['doc_no'] ?? null);
        $acReg = $this->cleanStr($row['aircraft_registration'] ?? $row['ac_reg'] ?? $row['ac'] ?? $row['reg'] ?? null);

        if (! $nsrdiNumber && ! $acReg) {
            return null;
        }

        $codeReason = $this->cleanStr($row['hold_reason_category'] ?? $row['code_reason'] ?? $row['code_open'] ?? null);
        $remarks = $this->cleanStr($row['hold_remarks'] ?? $row['reason_open'] ?? $row['remarks'] ?? null);
        $description = $this->cleanStr($row['finding_description'] ?? $row['description'] ?? $row['defect_description'] ?? null);
        $workGroup = $this->cleanStr($row['work_group'] ?? $row['wg'] ?? null);
        $rawCategory = $this->cleanStr($row['category'] ?? $row['cat'] ?? $row['kategori'] ?? $row['trade'] ?? $row['divisi'] ?? $this->defaultCategory);
        $category = NsrdiLog::determineCategory($rawCategory, $description, $workGroup);

        $planDate = $this->parseDate($row['plan_date'] ?? $row['deploy_date'] ?? $row['date'] ?? null);
        $refreshDate = $this->parseDate($row['refresh_date'] ?? null);
        $reportDate = $this->parseDate($row['report_date'] ?? null) ?: $refreshDate;

        $actSta = $this->cleanStr($row['act_station'] ?? $row['act_sta'] ?? null);
        $planSta = $this->cleanStr($row['plan_station'] ?? $row['plan_sta'] ?? null);
        $station = $actSta ?: ($planSta ?: 'Unknown');

        $statusRaw = strtoupper((string) ($row['status'] ?? 'OPEN'));
        $status = str_contains($statusRaw, 'CLOSE') ? 'Closed' : 'Open';
        $closeDate = $this->parseDate($row['close_date'] ?? null) ?: ($status === 'Closed' ? ($planDate ?: $refreshDate) : null);

        $djaId = null;
        if ($planDate && $acReg) {
            $dja = DailyJobAssignment::firstOrCreate(
                [
                    'aircraft_registration' => $acReg,
                    'date' => $planDate,
                    'task_id' => $nsrdiNumber ?: 'IMPORTED',
                ],
                [
                    'job_type' => 'AOC/NSRDI',
                    'station' => $station,
                    'description' => $description,
                ]
            );
            $djaId = $dja->id;
        }

        $data = [
            'dja_id' => $djaId,
            'refresh_date' => $refreshDate,
            'work_group' => $workGroup,
            'aircraft_registration' => $acReg,
            'nsrdi_number' => $nsrdiNumber,
            'description' => $description,
            'category' => $category,
            'report_date' => $reportDate,
            'due_date' => $this->parseDate($row['due_date'] ?? null),
            'part_number' => $this->cleanStr($row['part_number'] ?? $row['pn'] ?? null),
            'part_description' => $this->cleanStr($row['part_description'] ?? null),
            'defer' => $this->cleanStr($row['defer'] ?? null),
            'aoc' => $this->cleanStr($row['aoc'] ?? null),
            'type' => $this->cleanStr($row['type'] ?? null),
            'plan_station' => $planSta,
            'plan_date' => $planDate,
            'remarks' => $remarks,
            'status' => $status,
            'close_date' => $closeDate,
            'act_station' => $actSta,
            'hold_reason_category' => $codeReason,
            'hold_remarks' => $remarks,
            'code_open' => $codeReason,
            'reason_open' => $remarks,
            'is_submitted' => true,
            'import_source' => $planDate ? 'dja' : 'unplanned',
        ];

        if ($nsrdiNumber) {
            $existing = NsrdiLog::where('nsrdi_number', $nsrdiNumber)
                ->when($planDate, fn ($q) => $q->where('plan_date', $planDate), fn ($q) => $q->whereNull('plan_date'))
                ->first();

            if ($existing) {
                $existing->update($data);

                return $existing;
            }

            return NsrdiLog::create($data);
        }

        return new NsrdiLog($data);
    }
}
