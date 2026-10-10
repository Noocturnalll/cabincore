<?php

namespace App\Imports;

use App\Models\AircraftRotation;
use App\Models\RotationImport;
use App\Models\RotationLeg;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;

class AircraftRotationImport implements ToCollection
{
    /**
     * Map IATA station codes to UTC offsets based on config.
     */
    protected function getTzMap(): array
    {
        $map = [];
        $tzConfig = config('rotation.timezones', []);
        foreach ($tzConfig as $tz => $airports) {
            foreach ($airports as $code) {
                $map[strtoupper((string) $code)] = (int) $tz;
            }
        }

        return $map;
    }

    /**
     * Normalize a possible time value to HH:MM format.
     */
    protected function normalizeTime(mixed $val): ?string
    {
        if ($val === null || $val === '') {
            return null;
        }

        // If numeric decimal from Excel time (fraction of a day, e.g. 0.41666 = 10:00)
        if (is_numeric($val) && (float) $val < 1 && (float) $val >= 0) {
            return gmdate('H:i', (int) round((float) $val * 86400));
        }

        $str = trim((string) $val);

        // Matches 08:30 or 8:30
        if (preg_match('/^(\d{1,2})[:.](\d{2})$/', $str, $matches)) {
            return sprintf('%02d:%02d', (int) $matches[1], (int) $matches[2]);
        }

        // Matches 0830
        if (preg_match('/^(\d{2})(\d{2})$/', $str, $matches)) {
            $h = (int) $matches[1];
            $m = (int) $matches[2];
            if ($h < 24 && $m < 60) {
                return sprintf('%02d:%02d', $h, $m);
            }
        }

        return null;
    }

    public function collection(Collection $rows): void
    {
        // 1. Create or retrieve the import record
        $dateStr = now()->startOfDay()->toDateTimeString();

        $import = RotationImport::where('operation_date', $dateStr)->first();
        if ($import) {
            $import->update([
                'source_filename' => 'upload.xlsx',
                'stats' => [],
                'warnings' => [],
            ]);
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
        $tzMap = $this->getTzMap();

        $opBaseDate = Carbon::parse($import->operation_date ?? now()->toDateString())->startOfDay();

        // Parse each row
        foreach ($rows as $index => $row) {
            if ($index < 2) {
                continue;
            }

            $reg = $row[0] ?? ($row[1] ?? '');
            $regStr = trim((string) $reg);

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

            // Parse legs (4-column blocks)
            $legSeq = 1;
            for ($i = $firstCol - 1; $i <= $lastCol - 1; $i += 4) {
                $flightRaw = $row[$i] ?? null;
                $flightNo = preg_replace('/[^0-9]/', '', (string) $flightRaw);

                if (empty($flightNo)) {
                    continue;
                }

                $c1 = $row[$i + 1] ?? null;
                $c2 = $row[$i + 2] ?? null;
                $c3 = $row[$i + 3] ?? null;

                // Detect stations vs times
                $origin = 'CGK';
                $dest = 'SUB';
                $depLocal = null;
                $arrLocal = null;

                $cells = [$c1, $c2, $c3];
                $stationsFound = [];
                $timesFound = [];

                foreach ($cells as $cell) {
                    $cStr = strtoupper(trim((string) $cell));
                    if (preg_match('/^[A-Z]{3}$/', $cStr)) {
                        $stationsFound[] = $cStr;
                    } elseif ($t = $this->normalizeTime($cell)) {
                        $timesFound[] = $t;
                    }
                }

                if (count($stationsFound) >= 2) {
                    $origin = $stationsFound[0];
                    $dest = $stationsFound[1];
                } elseif (count($stationsFound) === 1) {
                    $origin = $stationsFound[0];
                }

                if (count($timesFound) >= 2) {
                    $depLocal = $timesFound[0];
                    $arrLocal = $timesFound[1];
                } elseif (count($timesFound) === 1) {
                    $depLocal = $timesFound[0];
                }

                // If no times detected, compute sequential schedule based on leg sequence
                if (! $depLocal) {
                    $startHour = 6 + (($legSeq - 1) * 3);
                    $depLocal = sprintf('%02d:00', min(22, $startHour));
                }
                if (! $arrLocal) {
                    $depCarbon = Carbon::createFromFormat('H:i', $depLocal);
                    $arrLocal = $depCarbon->addHours(2)->format('H:i');
                }

                $depTz = $tzMap[$origin] ?? 7;
                $arrTz = $tzMap[$dest] ?? 7;

                $depDateTime = $opBaseDate->copy()->setTimeFromTimeString($depLocal.':00');
                $arrDateTime = $opBaseDate->copy()->setTimeFromTimeString($arrLocal.':00');
                if ($arrDateTime->lt($depDateTime)) {
                    $arrDateTime->addDay();
                }

                $depTs = $depDateTime->timestamp - ($depTz * 3600);
                $arrTs = $arrDateTime->timestamp - ($arrTz * 3600);

                try {
                    RotationLeg::create([
                        'aircraft_rotation_id' => $rotation->id,
                        'seq' => $legSeq++,
                        'flight_raw' => $flightRaw,
                        'flight_no' => $flightNo,
                        'origin' => substr($origin, 0, 3),
                        'destination' => substr($dest, 0, 3),
                        'dep_ts' => $depTs,
                        'arr_ts' => $arrTs,
                        'dep_local' => $depLocal,
                        'arr_local' => $arrLocal,
                        'dep_tz' => $depTz,
                        'arr_tz' => $arrTz,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Gagal simpan leg: '.$e->getMessage());
                }
            }
        }
    }
}
