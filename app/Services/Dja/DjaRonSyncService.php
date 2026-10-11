<?php

namespace App\Services\Dja;

use App\Models\CapacityReport;
use App\Models\CapacityStation;
use Illuminate\Support\Facades\Log;

class DjaRonSyncService
{
    /**
     * Parses Sheet 1 ('List AC') rows and synchronizes RON counts into CapacityStation and CapacityReport.
     *
     * @param  array<int, array<int, mixed>>  $rows
     * @return array{total_stations: int, total_aircraft: int, tab_title: string, stations: array<string, array{JT: int, IW: int, ID: int, IU: int, SL: int, OD: int, total: int, aircraft: array<string, array<int, string>>}>}
     */
    public function syncFromArray(array $rows, ?string $tabTitle = 'List AC', ?string $targetDate = null): array
    {
        $parsed = $this->parseRows($rows);
        $res = $this->persist($parsed, $targetDate);

        Log::info("Synced RON from {$tabTitle}: {$res['stations']} stations, {$res['aircraft']} aircraft.");

        return [
            'total_stations' => $res['stations'],
            'total_aircraft' => $res['aircraft'],
            'tab_title' => $tabTitle ?: 'List AC',
            'stations' => $parsed,
        ];
    }

    /**
     * Extracts station columns and lists of aircraft from 2D sheet rows.
     *
     * @param  array<int, array<int, mixed>>  $rows
     * @return array<string, array{JT: int, IW: int, ID: int, IU: int, SL: int, OD: int, total: int, aircraft: array<string, array<int, string>>}>
     */
    public function parseRows(array $rows): array
    {
        if (empty($rows)) {
            return [];
        }

        // 1. Locate station header row (scan first 15 rows)
        $bestHeaderRow = null;
        $bestStationsByCol = [];

        foreach (array_slice($rows, 0, 15, true) as $rIdx => $row) {
            $stationsByCol = [];
            foreach ($row as $colIdx => $cell) {
                $val = strtoupper(trim((string) $cell));
                if ($val === '') {
                    continue;
                }

                // Match 3-letter station codes or prefixes like BM-BTH, TR-PLM, STA-CGK
                if (preg_match('/^(?:BM-|TR-|STA-)?([A-Z]{3})$/', $val, $m)) {
                    $stationsByCol[$colIdx] = $m[1];
                }
            }

            if (count($stationsByCol) >= 1 && count($stationsByCol) > count($bestStationsByCol)) {
                $bestHeaderRow = $rIdx;
                $bestStationsByCol = $stationsByCol;
            }
        }

        if ($bestHeaderRow === null || empty($bestStationsByCol)) {
            return [];
        }

        // 2. Scan aircraft registrations below the header row
        $stationAcs = [];
        $dataRows = array_slice($rows, $bestHeaderRow + 1);

        foreach ($dataRows as $row) {
            foreach ($bestStationsByCol as $colIdx => $sta) {
                if (! isset($row[$colIdx])) {
                    continue;
                }

                $cell = strtoupper(trim((string) $row[$colIdx]));
                if ($cell === '') {
                    continue;
                }

                // Matches aircraft registration format: PK-XXX, HS-XXX, 9M-XXX
                if (preg_match('/^(?:PK|HS|9M)-[A-Z0-9]{3}/i', $cell, $m)) {
                    $reg = strtoupper($m[0]);
                    $operator = $this->classifyOperator($reg);
                    $stationAcs[$sta][$operator][] = $reg;
                }
            }
        }

        // 3. Format result per station with counts
        $result = [];
        $allStations = array_values(array_unique(array_values($bestStationsByCol)));

        foreach ($allStations as $sta) {
            $ops = $stationAcs[$sta] ?? [];
            $jt = count($ops['JT'] ?? []);
            $iw = count($ops['IW'] ?? []);
            $id = count($ops['ID'] ?? []);
            $iu = count($ops['IU'] ?? []);
            $sl = count($ops['SL'] ?? []);
            $od = count($ops['OD'] ?? []);
            $total = $jt + $iw + $id + $iu + $sl + $od;

            $result[$sta] = [
                'JT' => $jt,
                'IW' => $iw,
                'ID' => $id,
                'IU' => $iu,
                'SL' => $sl,
                'OD' => $od,
                'total' => $total,
                'aircraft' => $ops,
            ];
        }

        return $result;
    }

    /**
     * Classifies airline operator based on Lion Air Group aircraft registration prefix.
     */
    public function classifyOperator(string $reg): string
    {
        $reg = strtoupper(trim($reg));

        if (str_starts_with($reg, 'PK-L')) {
            return 'JT'; // Lion Air
        }
        if (str_starts_with($reg, 'PK-W')) {
            return 'IW'; // Wings Air
        }
        if (str_starts_with($reg, 'PK-B')) {
            return 'ID'; // Batik Air
        }
        if (str_starts_with($reg, 'PK-S')) {
            return 'IU'; // Super Air Jet
        }
        if (str_starts_with($reg, 'HS-')) {
            return 'SL'; // Thai Lion Air
        }
        if (str_starts_with($reg, '9M-')) {
            return 'OD'; // Batik Air Malaysia
        }

        return 'JT';
    }

    /**
     * Persists parsed RON counts into CapacityStation and CapacityReport.
     *
     * @param  array<string, array{JT: int, IW: int, ID: int, IU: int, SL: int, OD: int, total: int, aircraft: array<string, array<int, string>>}>  $parsedStations
     * @return array{stations: int, aircraft: int}
     */
    public function persist(array $parsedStations, ?string $targetDate = null): array
    {
        if (empty($parsedStations)) {
            return ['stations' => 0, 'aircraft' => 0];
        }

        $date = $targetDate ?: DjaPersister::activeDate();
        $totalAcs = 0;
        $updatedStations = 0;

        foreach ($parsedStations as $sta => $data) {
            $totalAcs += $data['total'];

            $station = CapacityStation::firstOrNew(['station_code' => $sta]);
            if (! $station->exists) {
                $station->kh_region = 'KH-4';
                $station->order_no = (CapacityStation::max('order_no') ?? 0) + 1;
            }

            $station->ron_jt = $data['JT'];
            $station->ron_iw = $data['IW'];
            $station->ron_id = $data['ID'];
            $station->ron_iu = $data['IU'];
            $station->ron_sl = $data['SL'];
            $station->ron_od = $data['OD'];
            $station->save();
            $updatedStations++;

            // Snapshot to CapacityReport for this active date
            CapacityReport::updateOrCreate(
                ['report_date' => $date, 'station_code' => $sta],
                [
                    'order_no' => $station->order_no,
                    'kh_region' => $station->kh_region,
                    'group_type' => $station->group_type,
                    'code_store' => $station->code_store,
                    'working_hours' => $station->working_hours,
                    'tech_day' => $station->tech_day,
                    'tech_night' => $station->tech_night,
                    'ron_jt' => $data['JT'],
                    'ron_iw' => $data['IW'],
                    'ron_id' => $data['ID'],
                    'ron_iu' => $data['IU'],
                    'ron_sl' => $data['SL'],
                    'ron_od' => $data['OD'],
                ]
            );
        }

        // Reset stations in CapacityStation that are NOT present in the sheet to 0
        CapacityStation::whereNotIn('station_code', array_keys($parsedStations))->update([
            'ron_jt' => 0,
            'ron_iw' => 0,
            'ron_id' => 0,
            'ron_iu' => 0,
            'ron_sl' => 0,
            'ron_od' => 0,
        ]);

        return [
            'stations' => $updatedStations,
            'aircraft' => $totalAcs,
        ];
    }
}
