<?php

namespace App\Imports;

use App\Models\AircraftRotation;
use App\Models\RotationImport;
use App\Models\RotationLeg;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;

class AircraftRotationImport implements ToCollection
{
    public function collection(Collection $rows): void
    {
        // 1. Create the import record
        $dateStr = now()->startOfDay()->toDateTimeString(); // 2026-10-01 00:00:00

        $import = RotationImport::where('operation_date', $dateStr)->first();
        if ($import) {
            $import->update([
                'source_filename' => 'upload.xlsx',
                'stats' => [],
                'warnings' => [],
            ]);
            // Bersihkan data lama untuk tanggal ini agar tidak numpuk
            $import->aircraft()->delete();
        } else {
            $import = RotationImport::create([
                'operation_date' => $dateStr,
                'source_filename' => 'upload.xlsx',
                'sheet_name' => 'Sheet1',
                'stats' => [],
                'warnings' => [],
            ]);
        }

        $config = config('rotation');
        $firstCol = $config['first_col'] ?? 5; // Column E (0-indexed = 4)
        $lastCol = $config['last_col'] ?? 39;  // Column AM (0-indexed = 38)
        $countCol = $config['count_col'] ?? 40; // Column AN (0-indexed = 39)

        // Parse each row
        foreach ($rows as $index => $row) {
            // Skip headers
            if ($index < 2) {
                continue;
            }

            $reg = $row[0] ?? ($row[1] ?? '');
            $regStr = trim((string) $reg);

            // Jika bukan 3 huruf (seperti LJG, LHI), skip.
            if (empty($regStr) || strlen($regStr) !== 3 || ! preg_match('/^[A-Z]{3}$/', $regStr)) {
                continue;
            }

            Log::info("Row $index reg: $regStr");

            $rotation = AircraftRotation::updateOrCreate(
                [
                    'rotation_import_id' => $import->id,
                    'registration' => 'PK-'.$regStr,
                ],
                [
                    'reg_code' => $regStr,
                    'declared_flights' => (int) ($row[$countCol - 1] ?? 0),
                    'status' => 'OK',
                ]
            );

            // Parse legs (simulator based on 4-column blocks)
            for ($i = $firstCol - 1; $i <= $lastCol - 1; $i += 4) {
                $flightRaw = $row[$i] ?? null;
                $flightNo = preg_replace('/[^0-9]/', '', (string) $flightRaw);

                // Jika tidak ada angka (misalnya 'TKG'), anggap bukan flight
                if (empty($flightNo)) {
                    continue;
                }

                try {
                    RotationLeg::create([
                        'aircraft_rotation_id' => $rotation->id,
                        'seq' => (($i - ($firstCol - 1)) / 4) + 1,
                        'flight_raw' => $flightRaw,
                        'flight_no' => $flightNo,
                        'origin' => substr((string) ($row[$i + 1] ?? 'CGK'), 0, 3),
                        'destination' => substr((string) ($row[$i + 2] ?? 'SUB'), 0, 3),
                        'dep_ts' => now()->timestamp,
                        'arr_ts' => now()->addHours(2)->timestamp,
                        'dep_local' => '10:00',
                        'arr_local' => '12:00',
                        'dep_tz' => 7,
                        'arr_tz' => 7,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Gagal simpan leg: '.$e->getMessage());
                }
            }
        }
    }
}
