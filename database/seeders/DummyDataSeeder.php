<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('id_ID');

        $operators = ['Lion Air', 'Batik Air', 'Super Air Jet', 'Wings Air'];
        $acStatus = ['Serviceable', 'AOG'];
        $stations = ['CGK', 'BTH', 'KNO', 'SUB', 'UPG'];
        $status = ['Open', 'Closed'];

        // CML LOGS
        $cmlLogs = [];
        for ($i = 0; $i < 100; $i++) {
            $cmlLogs[] = [
                'date' => Carbon::now()->subDays(rand(0, 30))->format('Y-m-d'),
                'operator' => $faker->randomElement($operators),
                'aircraft_registration' => 'PK-'.strtoupper($faker->lexify('???')),
                'ac_status' => $faker->randomElement($acStatus),
                'station' => $faker->randomElement($stations),
                'doc_type' => 'CML',
                'no_doc' => 'DOC-'.$faker->numberBetween(1000, 9999),
                'description' => $faker->sentence(10),
                'status' => $faker->randomElement($status),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DB::table('cml_logs')->insert($cmlLogs);
    }
}
