<?php

namespace App\Imports;

use App\Models\NsrdiLog;
use App\Models\NsrdiOverdue;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class NsrdiOverdueImport implements ToModel, WithChunkReading, WithHeadingRow
{
    protected ?array $closedSet = null;

    public function __construct()
    {
        ini_set('memory_limit', '512M');
    }

    public function chunkSize(): int
    {
        return 500;
    }

    protected function getClosedSet(): array
    {
        if ($this->closedSet === null) {
            $this->closedSet = NsrdiLog::whereRaw('LOWER(status) = ?', ['closed'])
                ->pluck('nsrdi_number')
                ->filter()
                ->map(fn ($v) => strtoupper(trim((string) $v)))
                ->flip()
                ->toArray();
        }

        return $this->closedSet;
    }

    protected function cleanStr(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $s = trim((string) $value);

        return $s === '' ? null : $s;
    }

    protected function parseDate(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return Date::excelToDateTimeObject((float) $value)->format('Y-m-d');
            }

            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function parseDateTime(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return Date::excelToDateTimeObject((float) $value)->format('Y-m-d H:i:s');
            }

            return Carbon::parse((string) $value)->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function model(array $row): Model|array|null
    {
        $nsrdiNumber = $this->cleanStr($row['defect'] ?? $row['nsrdi_number'] ?? $row['nsrdi'] ?? $row['no_nsrdi'] ?? $row['doc_no'] ?? null);

        if (! $nsrdiNumber) {
            return null;
        }

        $mddr = $this->cleanStr($row['mddr'] ?? null);
        $numUpper = strtoupper($nsrdiNumber);
        $mddrUpper = $mddr ? strtoupper($mddr) : null;
        $closedSet = $this->getClosedSet();

        // Cek apakah defect number atau mddr ada di NsrdiLog dan sudah berstatus Closed
        $isClosedInLogs = isset($closedSet[$numUpper]) || ($mddrUpper && isset($closedSet[$mddrUpper]));

        $reportDate = $this->parseDate($row['reported_date'] ?? $row['report_date'] ?? $row['date'] ?? null);
        $dueDate = $this->parseDate($row['due_date'] ?? $row['due-date'] ?? null);
        $closedAt = $this->parseDateTime($row['closed_at'] ?? null);

        $statusRaw = $this->cleanStr($row['status'] ?? null);
        if ($isClosedInLogs) {
            $status = 'Closed';
            $closedAt = $closedAt ?: now();
        } elseif ($statusRaw) {
            $statusUpper = strtoupper($statusRaw);
            if (str_contains($statusUpper, 'CLOSE')) {
                $status = 'Closed';
                $closedAt = $closedAt ?: now();
            } elseif ($statusUpper === 'OVERDUE') {
                $status = 'Overdue';
            } else {
                $status = 'Open';
            }
        } else {
            $status = 'Open';
        }

        $operator = $this->cleanStr($row['provider'] ?? $row['operator'] ?? $row['aoc'] ?? null);
        $ac = $this->cleanStr($row['ac'] ?? $row['aircraft_registration'] ?? $row['ac_reg'] ?? $row['reg'] ?? null);
        $acStatus = $this->cleanStr($row['status_ac'] ?? $row['ac_status'] ?? null);
        $area = $this->cleanStr($row['area'] ?? null);
        $monthDue = $this->cleanStr($row['month_due'] ?? $row['month-due'] ?? null);
        $desc = $this->cleanStr($row['defect_description'] ?? $row['finding_description'] ?? $row['description'] ?? $row['desc'] ?? null);
        $partNumber = $this->cleanStr($row['part_number'] ?? $row['pn'] ?? null);
        $statusFinal = $this->cleanStr($row['status_final'] ?? $row['status_per_pn'] ?? null);
        $remarks = $this->cleanStr($row['remarks'] ?? $row['remark'] ?? $row['part_description'] ?? null);

        $existing = NsrdiOverdue::where('nsrdi_number', $nsrdiNumber)->first();

        if ($existing) {
            // Update / merge non-empty data agar Sheet 1 (detail part) tidak menghapus kolom dari Sheet 0
            $update = [];
            if ($operator && ! $existing->operator) {
                $update['operator'] = $operator;
            }
            if ($ac && ! $existing->aircraft_registration) {
                $update['aircraft_registration'] = $ac;
            }
            if ($acStatus && ! $existing->ac_status) {
                $update['ac_status'] = $acStatus;
            }
            if ($mddr && ! $existing->mddr) {
                $update['mddr'] = $mddr;
            }
            if ($area && ! $existing->area) {
                $update['area'] = $area;
            }
            if ($reportDate && ! $existing->report_date) {
                $update['report_date'] = $reportDate;
            }
            if ($dueDate && ! $existing->due_date) {
                $update['due_date'] = $dueDate;
            }
            if ($monthDue && ! $existing->month_due) {
                $update['month_due'] = $monthDue;
            }
            if ($desc && ! $existing->description) {
                $update['description'] = $desc;
            }
            if ($partNumber && ! $existing->part_number) {
                $update['part_number'] = $partNumber;
            }
            if ($statusFinal && ! $existing->status_final) {
                $update['status_final'] = $statusFinal;
            }
            if ($remarks && ! $existing->remarks) {
                $update['remarks'] = $remarks;
            }
            if ($status === 'Closed' && $existing->status !== 'Closed') {
                $update['status'] = 'Closed';
                $update['closed_at'] = $closedAt ?: now();
            }

            if (! empty($update)) {
                $existing->update($update);
            }

            return null;
        }

        return new NsrdiOverdue([
            'operator' => $operator,
            'aircraft_registration' => $ac,
            'ac_status' => $acStatus,
            'nsrdi_number' => $nsrdiNumber,
            'mddr' => $mddr,
            'status' => $status,
            'area' => $area,
            'report_date' => $reportDate,
            'due_date' => $dueDate,
            'month_due' => $monthDue,
            'description' => $desc,
            'part_number' => $partNumber,
            'status_final' => $statusFinal,
            'remarks' => $remarks,
            'closed_at' => $status === 'Closed' ? ($closedAt ?: now()) : null,
        ]);
    }
}
