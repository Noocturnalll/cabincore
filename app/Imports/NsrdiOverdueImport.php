<?php

namespace App\Imports;

use App\Models\NsrdiLog;
use App\Models\NsrdiOverdue;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class NsrdiOverdueImport implements ToModel, WithHeadingRow
{
    /**
     * @return Model|null
     */
    public function model(array $row)
    {
        $nsrdiNumber = $row['defect'] ?? $row['nsrdi_number'] ?? $row['nsrdi'] ?? $row['no_nsrdi'] ?? $row['doc_no'] ?? null;

        if (! $nsrdiNumber) {
            return null;
        }

        // Cek apakah sudah closed di NsrdiLog (dari DJA atau Daily Report)
        $isClosed = NsrdiLog::where('nsrdi_number', $nsrdiNumber)
            ->where(function ($query) {
                $query->where('status', 'Closed')
                    ->orWhere('status', 'CLOSED');
            })
            ->exists();

        $reportDate = null;
        if (isset($row['report_date'])) {
            try {
                if (is_numeric($row['report_date'])) {
                    $reportDate = Date::excelToDateTimeObject($row['report_date'])->format('Y-m-d');
                } else {
                    $reportDate = Carbon::parse($row['report_date'])->format('Y-m-d');
                }
            } catch (\Exception $e) {
                $reportDate = null;
            }
        }

        $closedAt = null;
        if (isset($row['closed_at'])) {
            try {
                if (is_numeric($row['closed_at'])) {
                    $closedAt = Date::excelToDateTimeObject($row['closed_at'])->format('Y-m-d H:i:s');
                } else {
                    $closedAt = Carbon::parse($row['closed_at'])->format('Y-m-d H:i:s');
                }
            } catch (\Exception $e) {
                $closedAt = null;
            }
        }

        $status = $isClosed ? 'Closed' : ($row['status'] ?? 'Open');

        return NsrdiOverdue::updateOrCreate(
            ['nsrdi_number' => $nsrdiNumber],
            [
                'operator' => $row['operator'] ?? null,
                'aircraft_registration' => $row['ac'] ?? $row['aircraft_registration'] ?? $row['ac_reg'] ?? $row['reg'] ?? null,
                'ac_status' => $row['status_ac'] ?? $row['ac_status'] ?? null,
                'mddr' => $row['mddr'] ?? null,
                'status' => $status,
                'area' => $row['area'] ?? null,
                'report_date' => $reportDate,
                'month_due' => $row['month_due'] ?? null,
                'description' => $row['defect_description'] ?? $row['description'] ?? $row['desc'] ?? null,
                'status_final' => $row['status_final'] ?? null,
                'remarks' => $row['remarks'] ?? $row['remark'] ?? null,
                'closed_at' => $closedAt,
            ]
        );
    }
}
