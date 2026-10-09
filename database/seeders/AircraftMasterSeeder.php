<?php

namespace Database\Seeders;

use App\Models\Aircraft;
use App\Models\Aoc;
use App\Services\Audit\AircraftTypeNormalizer;
use Illuminate\Database\Seeder;

/**
 * Loads the aircraft list (registration, operator, type) from data/aircraft_master.csv.
 * Safe to run again: it never touches the work group (wg) that is filled in later.
 */
class AircraftMasterSeeder extends Seeder
{
    /** operator text in the list => AOC code */
    private const OPERATORS = [
        'LION AIR' => 'JT', 'LION AIR A330' => 'JT',
        'BATIK AIR' => 'ID',
        'SUPER AIR JET' => 'IU',
        'WINGS AIR' => 'IW',
        'BATIK OD' => 'OD',          // Malindo, NOT Batik Air
        'THAI LION' => 'SL',
        'AAA' => 'AAA',
    ];

    public static function aocDefinitions(): array
    {
        return [
            ['code' => 'JT', 'name' => 'Lion Air', 'aliases' => ['LION AIR', 'LION AIR A330', 'LION', 'LNI'], 'sort_order' => 1],
            ['code' => 'ID', 'name' => 'Batik Air', 'aliases' => ['BATIK AIR', 'BATIK', 'BTK'], 'sort_order' => 2],
            ['code' => 'IU', 'name' => 'Super Air Jet', 'aliases' => ['SUPER AIR JET', 'SUPERAIRJET', 'SAJ'], 'sort_order' => 3],
            ['code' => 'IW', 'name' => 'Wings Air', 'aliases' => ['WINGS AIR', 'WINGS', 'WON'], 'sort_order' => 4],
            ['code' => 'OD', 'name' => 'Malindo Air', 'aliases' => ['BATIK OD', 'BATIK AIR MALAYSIA', 'MALINDO', 'MALINDO AIR', 'MXD'], 'sort_order' => 5],
            ['code' => 'SL', 'name' => 'Thai Lion Air', 'aliases' => ['THAI LION', 'THAI LION AIR', 'TLM'], 'sort_order' => 6],
            ['code' => 'AAA', 'name' => 'AAA (General Aviation)', 'aliases' => ['AAA'], 'sort_order' => 99, 'include_in_report' => false],
        ];
    }

    public function run(): void
    {
        foreach (self::aocDefinitions() as $def) {
            Aoc::updateOrCreate(['code' => $def['code']], $def + ['include_in_report' => true, 'is_active' => true]);
        }
        $aocs = Aoc::pluck('id', 'code');
        $aocNames = Aoc::pluck('name', 'code');

        $normalizer = new AircraftTypeNormalizer;
        $handle = fopen(__DIR__.'/data/aircraft_master.csv', 'r');
        fgetcsv($handle); // header

        $seen = [];
        $duplicates = [];
        $unknownOperators = [];
        $saved = 0;

        while (($row = fgetcsv($handle)) !== false) {
            [$registration, $operator, $type] = array_map('trim', array_pad($row, 3, ''));
            $registration = strtoupper($registration);
            if ($registration === '') {
                continue;
            }

            $code = self::OPERATORS[strtoupper($operator)] ?? null;
            if (! $code) {
                $unknownOperators[$operator] = true;
            }
            $norm = $normalizer->normalize($type);
            $signature = $norm['fleet'].'/'.$norm['variant'];

            // A registration listed twice: keep the first row; flag it when the type differs
            if (isset($seen[$registration])) {
                $duplicates[] = $registration.($seen[$registration] !== $signature ? " (tipe berbeda: {$type})" : '');

                continue;
            }
            $seen[$registration] = $signature;

            $aircraft = Aircraft::firstOrNew(['registration' => $registration]);
            $aircraft->fill([
                'aoc_id' => $code ? ($aocs[$code] ?? null) : null,
                'maskapai' => $code ? ($aocNames[$code] ?? $operator) : $operator,
                'tipe' => $type,
                'type_raw' => $type,
                'fleet' => $norm['fleet'],
                'variant' => $norm['variant'],
            ]);
            if (! $aircraft->exists) {
                $aircraft->status = 'Aktif';
            }
            $aircraft->save();
            $saved++;
        }
        fclose($handle);

        if (isset($this->command)) {
            $this->command->info("Pesawat tersimpan: {$saved}");
            if ($duplicates) {
                $this->command->warn('Registrasi ganda (diambil baris pertama): '.implode(', ', $duplicates));
            }
            if ($unknownOperators) {
                $this->command->warn('Operator tanpa AOC: '.implode(', ', array_keys($unknownOperators)));
            }
        }
    }
}
