<?php

namespace App\Imports;

use App\Models\DmiLog;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DmiImport implements ToModel, WithHeadingRow
{
    public function model(array $row): Model|array|null
    {
        return new DmiLog([
            'aircraft_registration' => $row['aircraft_registration'] ?? $row['ac_reg'] ?? null,
            'dmi_number' => $row['dmi_number'] ?? null,
            'description' => $row['description'] ?? null,
            'status' => $row['status'] ?? 'Open',
            'remarks' => $row['remarks'] ?? null,
            'plan_station' => $row['plan_station'] ?? null,
        ]);
    }
}
