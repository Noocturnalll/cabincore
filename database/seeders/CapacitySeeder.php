<?php

namespace Database\Seeders;

use App\Models\CapacityStation;
use App\Models\CapacityTarget;
use Illuminate\Database\Seeder;

class CapacitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $targets = [
            ['aoc' => 'JT', 'target_nsrdi' => 15],
            ['aoc' => 'IU', 'target_nsrdi' => 18],
            ['aoc' => 'ID', 'target_nsrdi' => 15],
        ];

        foreach ($targets as $t) {
            CapacityTarget::updateOrCreate(['aoc' => $t['aoc']], $t);
        }

        $capacityData = [
            'KH-1' => [
                ['no' => 1, 'group' => 'A', 'sta' => 'CGK', 'code_store' => 'K1, K22', 'time' => '19.00-07.00', 'day' => null, 'night' => 27, 'ron' => ['JT' => 6, 'IW' => 0, 'ID' => 24, 'IU' => 14, 'SL' => 1, 'OD' => 1]],
                ['no' => 2, 'group' => 'B', 'sta' => 'HLP', 'code_store' => 'H3', 'time' => '20.00-08.00', 'day' => null, 'night' => 3, 'ron' => ['JT' => 0, 'IW' => 0, 'ID' => 2, 'IU' => 0, 'SL' => 0, 'OD' => 0]],
                ['no' => 3, 'group' => 'C', 'sta' => 'CBN', 'code_store' => 'C1', 'time' => '', 'day' => null, 'night' => null, 'ron' => ['JT' => 0, 'IW' => 0, 'ID' => 0, 'IU' => 0, 'SL' => 0, 'OD' => 0]],
            ],
            'KH-2' => [
                ['no' => 4, 'group' => 'A', 'sta' => 'KNO', 'code_store' => 'K7', 'time' => '20.00-08.00', 'day' => null, 'night' => 6, 'ron' => ['JT' => 4, 'IW' => 2, 'ID' => 2, 'IU' => 1, 'SL' => 0, 'OD' => 0]],
                ['no' => 5, 'group' => 'B', 'sta' => 'BTH', 'code_store' => 'K3', 'time' => '11.00-23.00', 'day' => 1, 'night' => null, 'ron' => ['JT' => 1, 'IW' => 0, 'ID' => 1, 'IU' => 1, 'SL' => 0, 'OD' => 0]],
                ['no' => 6, 'group' => 'B', 'sta' => 'PKU', 'code_store' => 'P7', 'time' => '19.00-07.00', 'day' => null, 'night' => 1, 'ron' => ['JT' => 1, 'IW' => 0, 'ID' => 1, 'IU' => 1, 'SL' => 0, 'OD' => 0]],
                ['no' => 7, 'group' => 'C', 'sta' => 'PDG', 'code_store' => 'P1', 'time' => '19.00-07.00', 'day' => null, 'night' => 1, 'ron' => ['JT' => 2, 'IW' => 0, 'ID' => 1, 'IU' => 1, 'SL' => 0, 'OD' => 0]],
                ['no' => 8, 'group' => 'B', 'sta' => 'PLM', 'code_store' => 'P5', 'time' => '11.00-23.00', 'day' => 2, 'night' => null, 'ron' => ['JT' => 1, 'IW' => 0, 'ID' => 1, 'IU' => 2, 'SL' => 0, 'OD' => 0]],
            ],
            'KH-3' => [
                ['no' => 9, 'group' => 'A', 'sta' => 'SUB', 'code_store' => 'S1, K86', 'time' => '19.00-07.00', 'day' => null, 'night' => 5, 'ron' => ['JT' => 4, 'IW' => 1, 'ID' => 4, 'IU' => 4, 'SL' => 0, 'OD' => 0]],
                ['no' => 10, 'group' => 'C', 'sta' => 'SOC', 'code_store' => 'S3', 'time' => '09.00-21.00', 'day' => 2, 'night' => null, 'ron' => ['JT' => 0, 'IW' => 0, 'ID' => 1, 'IU' => 1, 'SL' => 0, 'OD' => 0]],
                ['no' => 11, 'group' => 'C', 'sta' => 'SRG', 'code_store' => 'S5', 'time' => '11.00-23.00', 'day' => 2, 'night' => null, 'ron' => ['JT' => 0, 'IW' => 0, 'ID' => 1, 'IU' => 2, 'SL' => 0, 'OD' => 0]],
                ['no' => 12, 'group' => 'C', 'sta' => 'YIA', 'code_store' => 'C4', 'time' => 'TBA', 'day' => null, 'night' => null, 'ron' => ['JT' => 1, 'IW' => 0, 'ID' => 0, 'IU' => 2, 'SL' => 0, 'OD' => 0]],
                ['no' => 13, 'group' => 'C', 'sta' => 'PNK', 'code_store' => 'P9', 'time' => 'TBA', 'day' => null, 'night' => null, 'ron' => ['JT' => 1, 'IW' => 1, 'ID' => 0, 'IU' => 1, 'SL' => 0, 'OD' => 0]],
            ],
            'KH-4' => [
                ['no' => 14, 'group' => 'B', 'sta' => 'DPS', 'code_store' => 'K5', 'time' => '19.00-07.00', 'day' => null, 'night' => 1, 'ron' => ['JT' => 1, 'IW' => 1, 'ID' => 1, 'IU' => 1, 'SL' => 0, 'OD' => 0]],
                ['no' => 15, 'group' => 'C', 'sta' => 'LOP', 'code_store' => 'L2', 'time' => '14.00-23.00 & 20.00-06.00', 'day' => 1, 'night' => 1, 'ron' => ['JT' => 0, 'IW' => 3, 'ID' => 0, 'IU' => 3, 'SL' => 0, 'OD' => 0]],
                ['no' => 16, 'group' => 'B', 'sta' => 'KOE', 'code_store' => 'K126', 'time' => '13.00-22.00', 'day' => 2, 'night' => null, 'ron' => ['JT' => 1, 'IW' => 3, 'ID' => 1, 'IU' => 0, 'SL' => 0, 'OD' => 0]],
                ['no' => 17, 'group' => 'B', 'sta' => 'BDJ', 'code_store' => 'B1', 'time' => 'TBA', 'day' => null, 'night' => null, 'ron' => ['JT' => 2, 'IW' => 2, 'ID' => 0, 'IU' => 2, 'SL' => 0, 'OD' => 0]],
            ],
            'KH-5' => [
                ['no' => 18, 'group' => 'A', 'sta' => 'UPG', 'code_store' => 'K4', 'time' => '20.00-08.00', 'day' => null, 'night' => 6, 'ron' => ['JT' => 10, 'IW' => 5, 'ID' => 8, 'IU' => 1, 'SL' => 0, 'OD' => 0]],
                ['no' => 19, 'group' => 'B', 'sta' => 'BPN', 'code_store' => 'B9', 'time' => '14.00-23.00', 'day' => 1, 'night' => null, 'ron' => ['JT' => 4, 'IW' => 2, 'ID' => 0, 'IU' => 4, 'SL' => 0, 'OD' => 0]],
                ['no' => 20, 'group' => 'B', 'sta' => 'MDC', 'code_store' => 'M3', 'time' => '19.30-07.30', 'day' => null, 'night' => 2, 'ron' => ['JT' => 2, 'IW' => 3, 'ID' => 0, 'IU' => 0, 'SL' => 0, 'OD' => 0]],
                ['no' => 21, 'group' => 'B', 'sta' => 'AMQ', 'code_store' => 'A2', 'time' => '07.00-16.00', 'day' => 2, 'night' => null, 'ron' => ['JT' => 0, 'IW' => 2, 'ID' => 0, 'IU' => 0, 'SL' => 0, 'OD' => 0]],
            ],
            'KH-6' => [
                ['no' => 22, 'group' => 'A', 'sta' => 'DMK', 'code_store' => 'D5', 'time' => '', 'day' => null, 'night' => null, 'ron' => ['JT' => 0, 'IW' => 0, 'ID' => 0, 'IU' => 0, 'SL' => 0, 'OD' => 0]],
            ],
            'KH-7' => [
                ['no' => 23, 'group' => 'A', 'sta' => 'KUL', 'code_store' => 'K102', 'time' => '', 'day' => null, 'night' => null, 'ron' => ['JT' => 0, 'IW' => 0, 'ID' => 0, 'IU' => 0, 'SL' => 0, 'OD' => 0]],
                ['no' => 24, 'group' => 'B', 'sta' => 'SZB', 'code_store' => 'K104', 'time' => '', 'day' => null, 'night' => null, 'ron' => ['JT' => 0, 'IW' => 0, 'ID' => 0, 'IU' => 0, 'SL' => 0, 'OD' => 0]],
            ],
        ];

        CapacityStation::truncate();

        foreach ($capacityData as $kh => $rows) {
            foreach ($rows as $row) {
                CapacityStation::create([
                    'order_no' => $row['no'],
                    'kh_region' => $kh,
                    'group_type' => $row['group'],
                    'station_code' => $row['sta'],
                    'code_store' => $row['code_store'],
                    'working_hours' => $row['time'],
                    'tech_day' => $row['day'],
                    'tech_night' => $row['night'],
                    'ron_jt' => $row['ron']['JT'],
                    'ron_iw' => $row['ron']['IW'],
                    'ron_id' => $row['ron']['ID'],
                    'ron_iu' => $row['ron']['IU'],
                    'ron_sl' => $row['ron']['SL'] ?? 0,
                    'ron_od' => $row['ron']['OD'] ?? 0,
                ]);
            }
        }
    }
}
