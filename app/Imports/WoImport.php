<?php

namespace App\Imports;

use App\Models\WoLog;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class WoImport implements ToModel, WithHeadingRow
{
    public function model(array $row): \Illuminate\Database\Eloquent\Model|array|null
    {
        return new WoLog([
            'aircraft_registration' => $row['aircraft_registration'] ?? $row['ac_reg'] ?? null,
            'wo_number' => $row['wo_number'] ?? null,
            'description' => $row['description'] ?? null,
            'status' => $row['status'] ?? 'Open',
            'reason_open' => $row['reason_open'] ?? null,
            'plan_station' => $row['plan_station'] ?? null,
        ]);
    }
}
