<?php

namespace App\Imports;

use App\Models\IctFinding;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class IctFindingImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row): \Illuminate\Database\Eloquent\Model|array|null
    {
        $date = isset($row['date']) && is_numeric($row['date']) 
            ? Carbon::instance(Date::excelToDateTimeObject($row['date']))->format('Y-m-d')
            : (isset($row['date']) ? Carbon::parse($row['date'])->format('Y-m-d') : null);

        $ac_reg = $row['aircraft_registration'] ?? $row['ac_reg'] ?? $row['a_c_reg'] ?? '';

        return IctFinding::updateOrCreate(
            [
                'no_finding' => $row['no_finding'],
                'aircraft_registration' => $ac_reg,
                'date' => $date,
            ],
            [
                'operator' => $row['operator'] ?? null,
                'defect_description' => $row['defect_description'] ?? null,
                // do not overwrite remarks or status if it already exists and user uploaded again?
                // For safety, we update it, but usually imports overwrite. 
                // Let's just update if it's there. The user can edit later.
                'remarks' => $row['remarks'] ?? null,
                'status' => $row['status'] ?? 'Open',
            ]
        );
    }

    public function rules(): array
    {
        return [
            'no_finding' => 'required',
            'date' => 'required',
        ];
    }
}
