<?php

namespace App\Imports;

use App\Models\NsrdiLog;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class NsrdiImport implements ToModel, WithHeadingRow
{
    public function model(array $row): Model|array|null
    {
        return new NsrdiLog([
            'aircraft_registration' => $row['aircraft_registration'] ?? $row['ac_reg'] ?? null,
            'nsrdi_number' => $row['nsrdi_number'] ?? null,
            'description' => $row['description'] ?? null,
            'status' => $row['status'] ?? 'Open',
            'reason_open' => $row['reason_open'] ?? null,
            'plan_station' => $row['plan_station'] ?? null,
        ]);
    }
}
