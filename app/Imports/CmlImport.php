<?php

namespace App\Imports;

use App\Models\CmlLog;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CmlImport implements ToModel, WithHeadingRow
{
    public function model(array $row): Model|array|null
    {
        return new CmlLog([
            'date' => $row['date'] ?? null,
            'operator' => $row['operator'] ?? null,
            'aircraft_registration' => $row['aircraft_registration'] ?? $row['ac_reg'] ?? $row['reg_ac'] ?? null,
            'ac_status' => $row['ac_status'] ?? $row['aircraft_status'] ?? null,
            'station' => $row['station'] ?? $row['sta'] ?? null,
            'doc_type' => $row['doc_type'] ?? null,
            'no_doc' => $row['no_doc'] ?? null,
            'description' => $row['description'] ?? $row['action_taken'] ?? $row['reason'] ?? null,
            'status' => $row['status'] ?? 'Open',
            'hold_reason_category' => $row['hold_reason_category'] ?? null,
            'hold_remarks' => $row['hold_remarks'] ?? null,
        ]);
    }
}
