<?php

namespace App\Imports;

use App\Models\CmlLog;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CmlImport implements ToModel, WithHeadingRow
{
    public function model(array $row): \Illuminate\Database\Eloquent\Model|array|null
    {
        return new CmlLog([
            'aircraft_registration' => $row['aircraft_registration'] ?? $row['ac_reg'] ?? null,
            'station' => $row['station'] ?? null,
            'description' => $row['description'] ?? null,
            'status' => $row['status'] ?? 'Open',
            'hold_reason_category' => $row['hold_reason_category'] ?? null,
            'hold_remarks' => $row['hold_remarks'] ?? null,
        ]);
    }
}
