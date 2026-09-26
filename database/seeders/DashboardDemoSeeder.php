<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DashboardDemoSeeder extends Seeder
{
    public function run(): void
    {
        $stations = ['CGK', 'HLP', 'SUB', 'KNO', 'UPG', 'MDC', 'BPN', 'AMQ', 'SRG', 'PLM', 'DPS'];
        $operators = ['Lion Air', 'Batik Air', 'Super Air Jet', 'Wings Air'];
        $registrations = ['PK-LQA', 'PK-LQB', 'PK-LQC', 'PK-LZA', 'PK-LZB', 'PK-BAX', 'PK-BAY', 'PK-SAA', 'PK-SAB', 'PK-WGA'];
        $statuses = ['Open', 'Closed'];
        $today = Carbon::today()->format('Y-m-d');
        $now = now();

        // ─── Insert DJA (daily_job_assignments) ───
        $djaIds = [];
        for ($i = 0; $i < 30; $i++) {
            $daysAgo = rand(0, 6);
            $date = Carbon::today()->subDays($daysAgo)->format('Y-m-d');
            $jobType = collect(['R01/WO', 'DMI', 'AOC/NSRDI'])->random();
            $id = DB::table('daily_job_assignments')->insertGetId([
                'aircraft_registration' => $registrations[array_rand($registrations)],
                'date' => $date,
                'task_id' => 'TASK-'.str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'job_type' => $jobType,
                'description' => 'Task deskripsi '.($i + 1),
                'station' => $stations[array_rand($stations)],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $djaIds[] = ['id' => $id, 'date' => $date, 'job_type' => $jobType, 'station' => $stations[array_rand($stations)]];
        }

        // ─── WO Logs ───
        // DJA WO (planned)
        $woDjaEntries = array_filter($djaIds, fn ($d) => $d['job_type'] === 'R01/WO');
        foreach ($woDjaEntries as $dja) {
            DB::table('wo_logs')->insert([
                'dja_id' => $dja['id'],
                'aircraft_registration' => $registrations[array_rand($registrations)],
                'date' => $dja['date'],
                'wo_number' => 'WO-'.rand(1000, 9999),
                'wo_category' => collect(['A-Check', 'C-Check', 'Routine'])->random(),
                'description' => 'Work order planned dari DJA',
                'status' => $statuses[array_rand($statuses)],
                'plan_station' => $dja['station'],
                'act_station' => $dja['station'],
                'operator' => $operators[array_rand($operators)],
                'work_group' => collect(['Cabin', 'AIC', 'Painting'])->random(),
                'man_hour' => rand(1, 8),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Unplanned WO
        for ($i = 0; $i < 20; $i++) {
            $daysAgo = rand(0, 6);
            DB::table('wo_logs')->insert([
                'dja_id' => null,
                'aircraft_registration' => $registrations[array_rand($registrations)],
                'date' => Carbon::today()->subDays($daysAgo)->format('Y-m-d'),
                'wo_number' => 'WO-U'.rand(1000, 9999),
                'wo_category' => collect(['A-Check', 'Routine', 'Defect'])->random(),
                'description' => 'Unplanned work order',
                'status' => $statuses[array_rand($statuses)],
                'plan_station' => $stations[array_rand($stations)],
                'act_station' => $stations[array_rand($stations)],
                'operator' => $operators[array_rand($operators)],
                'work_group' => collect(['Cabin', 'AIC', 'Painting'])->random(),
                'man_hour' => rand(1, 6),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // ─── DMI Logs ───
        $dmiDjaEntries = array_filter($djaIds, fn ($d) => $d['job_type'] === 'DMI');
        foreach ($dmiDjaEntries as $dja) {
            DB::table('dmi_logs')->insert([
                'dja_id' => $dja['id'],
                'aircraft_registration' => $registrations[array_rand($registrations)],
                'date' => $dja['date'],
                'dmi_number' => 'DMI-'.rand(1000, 9999),
                'dmi_category' => collect(['Structural', 'System', 'Avionics'])->random(),
                'description' => 'DMI log planned dari DJA',
                'status' => $statuses[array_rand($statuses)],
                'plan_station' => $dja['station'],
                'act_station' => $dja['station'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        for ($i = 0; $i < 15; $i++) {
            $daysAgo = rand(0, 6);
            DB::table('dmi_logs')->insert([
                'dja_id' => null,
                'aircraft_registration' => $registrations[array_rand($registrations)],
                'date' => Carbon::today()->subDays($daysAgo)->format('Y-m-d'),
                'dmi_number' => 'DMI-U'.rand(1000, 9999),
                'dmi_category' => collect(['Structural', 'System', 'Avionics'])->random(),
                'description' => 'Unplanned DMI log',
                'status' => $statuses[array_rand($statuses)],
                'plan_station' => $stations[array_rand($stations)],
                'act_station' => $stations[array_rand($stations)],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // ─── NSRDI Logs ───
        $nsrdiDjaEntries = array_filter($djaIds, fn ($d) => $d['job_type'] === 'AOC/NSRDI');
        foreach ($nsrdiDjaEntries as $dja) {
            $dueDate = Carbon::parse($dja['date'])->addDays(rand(-5, 30))->format('Y-m-d');
            DB::table('nsrdi_logs')->insert([
                'dja_id' => $dja['id'],
                'aircraft_registration' => $registrations[array_rand($registrations)],
                'plan_date' => $dja['date'],
                'report_date' => $dja['date'],
                'nsrdi_number' => 'NSRDI-'.rand(1000, 9999),
                'category' => collect(['SB', 'AD', 'EO'])->random(),
                'description' => 'NSRDI planned dari DJA',
                'status' => $statuses[array_rand($statuses)],
                'plan_station' => $dja['station'],
                'act_station' => $dja['station'],
                'due_date' => $dueDate,
                'aoc' => $operators[array_rand($operators)],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        for ($i = 0; $i < 15; $i++) {
            $daysAgo = rand(0, 6);
            $reportDate = Carbon::today()->subDays($daysAgo)->format('Y-m-d');
            $dueDate = Carbon::parse($reportDate)->addDays(rand(-10, 20))->format('Y-m-d');
            DB::table('nsrdi_logs')->insert([
                'dja_id' => null,
                'aircraft_registration' => $registrations[array_rand($registrations)],
                'plan_date' => null,
                'report_date' => $reportDate,
                'nsrdi_number' => 'NSRDI-U'.rand(1000, 9999),
                'category' => collect(['SB', 'AD', 'EO'])->random(),
                'description' => 'Unplanned NSRDI log',
                'status' => $statuses[array_rand($statuses)],
                'plan_station' => $stations[array_rand($stations)],
                'act_station' => $stations[array_rand($stations)],
                'due_date' => $dueDate,
                'aoc' => $operators[array_rand($operators)],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // ─── CML Logs ─── (already has 200, but add some for today)
        for ($i = 0; $i < 25; $i++) {
            $daysAgo = $i < 10 ? 0 : rand(1, 6);
            DB::table('cml_logs')->insert([
                'date' => Carbon::today()->subDays($daysAgo)->format('Y-m-d'),
                'operator' => $operators[array_rand($operators)],
                'aircraft_registration' => $registrations[array_rand($registrations)],
                'ac_status' => collect(['Serviceable', 'AOG'])->random(),
                'station' => $stations[array_rand($stations)],
                'doc_type' => 'CML',
                'no_doc' => 'DOC-'.rand(1000, 9999),
                'description' => 'CML document '.($i + 1),
                'status' => $statuses[array_rand($statuses)],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // ─── ICT Findings ───
        for ($i = 0; $i < 35; $i++) {
            $daysAgo = $i < 15 ? 0 : rand(1, 6);
            DB::table('ict_findings')->insert([
                'date' => Carbon::today()->subDays($daysAgo)->format('Y-m-d'),
                'no_finding' => 'ICT-'.str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'operator' => $operators[array_rand($operators)],
                'aircraft_registration' => $registrations[array_rand($registrations)],
                'defect_description' => 'Temuan ICT pada area kabin penumpang item '.($i + 1),
                'remarks' => 'Perlu tindak lanjut segera',
                'status' => $i < 20 ? 'Open' : 'Closed',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // ─── Aircraft Cleanings ───
        $acTypes = ['General', 'DCI', 'DCE', 'Transit'];
        $shifts = ['Pagi', 'Siang', 'Malam'];

        for ($i = 0; $i < 60; $i++) {
            $daysAgo = $i < 30 ? 0 : rand(1, 6);
            $type = $acTypes[array_rand($acTypes)];
            DB::table('aircraft_cleanings')->insert([
                'aircraft_registration' => $registrations[array_rand($registrations)],
                'date' => Carbon::today()->subDays($daysAgo)->format('Y-m-d'),
                'shift' => $shifts[array_rand($shifts)],
                'type' => $type,
                'status' => $statuses[array_rand($statuses)],
                'remarks' => 'Aircraft cleaning '.$type.' selesai',
                'operator' => $operators[array_rand($operators)],
                'station' => $stations[array_rand($stations)],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->command->info('Dashboard demo data seeded successfully!');
    }
}
