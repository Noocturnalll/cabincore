<?php

namespace App\Imports;

use App\Models\AircraftCleaning;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class AircraftCleaningImport extends CalculatedValueBinder implements Import, ToCollection, WithChunkReading, WithCustomValueBinder, WithHeadingRow
{
    public function chunkSize(): int
    {
        return 500;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            // Check if essential columns are present
            if (! isset($row['aircraft_registration']) || ! isset($row['date'])) {
                continue;
            }

            // Convert Excel date to PHP date
            $date = $row['date'];
            if (is_numeric($date)) {
                $date = Date::excelToDateTimeObject($date)->format('Y-m-d');
            }

            AircraftCleaning::updateOrCreate(
                [
                    'aircraft_registration' => $row['aircraft_registration'],
                    'date' => $date,
                    'type' => $row['type'] ?? 'General',
                ],
                [
                    'shift' => $row['shift'] ?? null,
                    'status' => $row['status'] ?? 'Open',
                    'remarks' => $row['remarks'] ?? null,
                    'operator' => $row['operator'] ?? null,
                    'station' => $row['station'] ?? null,
                ]
            );
        }
    }
}
