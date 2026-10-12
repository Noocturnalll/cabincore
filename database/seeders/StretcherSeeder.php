<?php

namespace Database\Seeders;

use App\Models\Stretcher;
use App\Models\StretcherTransaction;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class StretcherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. STR-01: Stretcher Boeing 737 (Flown from CGK to KNO)
        $s1 = Stretcher::updateOrCreate(['code' => 'STR-01'], [
            'name' => 'Stretcher Boeing 737 Unit 1',
            'aircraft_type' => 'B737',
            'part_number' => 'ST-B737-01',
            'serial_number' => 'SN-88219',
            'current_station' => 'KNO',
            'status' => 'AVAILABLE',
            'condition' => 'GOOD',
            'notes' => 'Tandu medis standar kabin B737-800/900ER.',
        ]);

        StretcherTransaction::updateOrCreate([
            'stretcher_id' => $s1->id,
            'flight_number' => 'JT-380',
            'date' => Carbon::now()->subDays(2)->format('Y-m-d'),
        ], [
            'aircraft_registration' => 'PK-LGF',
            'origin_station' => 'CGK',
            'destination_station' => 'KNO',
            'route' => 'CGK - KNO',
            'passenger_name' => 'Budi Santoso',
            'medical_notes' => 'Medevac Stroke - Didampingi dr. Agus & perawat.',
            'status' => 'COMPLETED',
            'pic_name' => 'Tim CBM CGK',
            'notes' => 'Pemasangan di row 28-30. Tiba di KNO dalam kondisi baik dan aman.',
        ]);

        // 2. STR-02: Stretcher Airbus A320 (Station CGK)
        Stretcher::updateOrCreate(['code' => 'STR-02'], [
            'name' => 'Stretcher Airbus A320 Unit 1',
            'aircraft_type' => 'A320',
            'part_number' => 'ST-A320-02',
            'serial_number' => 'SN-90142',
            'current_station' => 'CGK',
            'status' => 'AVAILABLE',
            'condition' => 'GOOD',
            'notes' => 'Kompatibel Batik Air & Super Air Jet A320.',
        ]);

        // 3. STR-03: Stretcher Boeing 737 Unit 2 (Station SUB)
        $s3 = Stretcher::updateOrCreate(['code' => 'STR-03'], [
            'name' => 'Stretcher Boeing 737 Unit 2',
            'aircraft_type' => 'B737',
            'part_number' => 'ST-B737-03',
            'serial_number' => 'SN-77631',
            'current_station' => 'SUB',
            'status' => 'AVAILABLE',
            'condition' => 'GOOD',
            'notes' => 'Posisi standby di stasiun Juanda Surabaya.',
        ]);

        StretcherTransaction::updateOrCreate([
            'stretcher_id' => $s3->id,
            'flight_number' => 'JT-690',
            'date' => Carbon::now()->subDays(4)->format('Y-m-d'),
        ], [
            'aircraft_registration' => 'PK-LGP',
            'origin_station' => 'CGK',
            'destination_station' => 'SUB',
            'route' => 'CGK - SUB',
            'passenger_name' => 'Siti Aminah',
            'medical_notes' => 'Pasien pasca operasi ortopedi.',
            'status' => 'COMPLETED',
            'pic_name' => 'Tim CBM CGK',
            'notes' => 'Stretcher dilepas di SUB dan disimpan di workshop CBM SUB.',
        ]);

        // 4. STR-04: Stretcher Airbus A330 (Station DPS)
        Stretcher::updateOrCreate(['code' => 'STR-04'], [
            'name' => 'Stretcher Airbus A330 Widebody',
            'aircraft_type' => 'A330',
            'part_number' => 'ST-A330-01',
            'serial_number' => 'SN-55120',
            'current_station' => 'DPS',
            'status' => 'AVAILABLE',
            'condition' => 'GOOD',
            'notes' => 'Stretcher widebody Lion Group A330-300/900neo.',
        ]);

        // 5. STR-05: Stretcher ATR 72 (Station BTH)
        Stretcher::updateOrCreate(['code' => 'STR-05'], [
            'name' => 'Stretcher ATR 72 Unit 1',
            'aircraft_type' => 'ATR72',
            'part_number' => 'ST-ATR-01',
            'serial_number' => 'SN-44210',
            'current_station' => 'BTH',
            'status' => 'AVAILABLE',
            'condition' => 'GOOD',
            'notes' => 'Khusus armada Wings Air ATR 72.',
        ]);
    }
}
