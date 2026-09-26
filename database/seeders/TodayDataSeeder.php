<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds additional data specifically for today's date to ensure
 * the dashboard has visible data when demo-ing.
 */
class TodayDataSeeder extends Seeder
{
    public function run(): void
    {
        $today = Carbon::today()->format('Y-m-d');
        $now = now();

        $regs = ['PK-LQA', 'PK-LQB', 'PK-LQC', 'PK-LZA', 'PK-LZB', 'PK-BAX', 'PK-SAA'];
        $stations = ['CGK', 'HLP', 'SUB', 'KNO', 'UPG', 'DPS', 'MDC'];
        $ops = ['Lion Air', 'Batik Air', 'Super Air Jet', 'Wings Air'];
        $statuses = ['Open', 'Closed'];

        // ─── DJA + WO (today) ───
        for ($i = 0; $i < 10; $i++) {
            $station = $stations[array_rand($stations)];
            $djaId = DB::table('daily_job_assignments')->insertGetId([
                'aircraft_registration' => $regs[array_rand($regs)],
                'date' => $today,
                'task_id' => 'TODAY-WO-'.str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'job_type' => 'R01/WO',
                'description' => 'WO planned task hari ini #'.($i + 1),
                'station' => $station,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('wo_logs')->insert([
                'dja_id' => $djaId,
                'aircraft_registration' => $regs[array_rand($regs)],
                'date' => $today,
                'wo_number' => 'WO-T'.rand(1000, 9999),
                'wo_category' => collect(['A-Check', 'C-Check', 'Routine'])->random(),
                'description' => 'Work order planned hari ini',
                'status' => $statuses[array_rand($statuses)],
                'plan_station' => $station,
                'act_station' => $station,
                'operator' => $ops[array_rand($ops)],
                'work_group' => collect(['Cabin', 'AIC', 'Painting'])->random(),
                'man_hour' => rand(1, 8),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // ─── DJA + DMI (today) ───
        for ($i = 0; $i < 7; $i++) {
            $station = $stations[array_rand($stations)];
            $djaId = DB::table('daily_job_assignments')->insertGetId([
                'aircraft_registration' => $regs[array_rand($regs)],
                'date' => $today,
                'task_id' => 'TODAY-DMI-'.str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'job_type' => 'DMI',
                'description' => 'DMI planned task hari ini #'.($i + 1),
                'station' => $station,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('dmi_logs')->insert([
                'dja_id' => $djaId,
                'aircraft_registration' => $regs[array_rand($regs)],
                'date' => $today,
                'dmi_number' => 'DMI-T'.rand(1000, 9999),
                'dmi_category' => collect(['Structural', 'System', 'Avionics'])->random(),
                'description' => 'DMI planned hari ini',
                'status' => $statuses[array_rand($statuses)],
                'plan_station' => $station,
                'act_station' => $station,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // ─── DJA + NSRDI (today) ───
        for ($i = 0; $i < 5; $i++) {
            $station = $stations[array_rand($stations)];
            $dueDate = Carbon::today()->addDays(rand(1, 30))->format('Y-m-d');
            $djaId = DB::table('daily_job_assignments')->insertGetId([
                'aircraft_registration' => $regs[array_rand($regs)],
                'date' => $today,
                'task_id' => 'TODAY-NSRDI-'.str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'job_type' => 'AOC/NSRDI',
                'description' => 'NSRDI planned task hari ini #'.($i + 1),
                'station' => $station,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('nsrdi_logs')->insert([
                'dja_id' => $djaId,
                'aircraft_registration' => $regs[array_rand($regs)],
                'plan_date' => $today,
                'report_date' => $today,
                'nsrdi_number' => 'NSRDI-T'.rand(1000, 9999),
                'category' => collect(['SB', 'AD', 'EO'])->random(),
                'description' => 'NSRDI planned hari ini',
                'status' => $statuses[array_rand($statuses)],
                'plan_station' => $station,
                'act_station' => $station,
                'due_date' => $dueDate,
                'aoc' => $ops[array_rand($ops)],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // ─── Unplanned WO (today) ───
        for ($i = 0; $i < 6; $i++) {
            DB::table('wo_logs')->insert([
                'dja_id' => null,
                'aircraft_registration' => $regs[array_rand($regs)],
                'date' => $today,
                'wo_number' => 'WO-U-T'.rand(1000, 9999),
                'wo_category' => 'Defect',
                'description' => 'Unplanned WO hari ini #'.($i + 1),
                'status' => $statuses[array_rand($statuses)],
                'plan_station' => $stations[array_rand($stations)],
                'act_station' => $stations[array_rand($stations)],
                'operator' => $ops[array_rand($ops)],
                'work_group' => 'Cabin',
                'man_hour' => rand(1, 4),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // ─── Unplanned DMI (today) ───
        for ($i = 0; $i < 4; $i++) {
            DB::table('dmi_logs')->insert([
                'dja_id' => null,
                'aircraft_registration' => $regs[array_rand($regs)],
                'date' => $today,
                'dmi_number' => 'DMI-U-T'.rand(1000, 9999),
                'dmi_category' => 'System',
                'description' => 'Unplanned DMI hari ini',
                'status' => $statuses[array_rand($statuses)],
                'plan_station' => $stations[array_rand($stations)],
                'act_station' => $stations[array_rand($stations)],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // ─── NSRDI overdue (due_date in past, status Open) ───
        for ($i = 0; $i < 8; $i++) {
            $dueDate = Carbon::today()->subDays(rand(1, 30))->format('Y-m-d');
            $reportDate = Carbon::today()->subDays(rand(5, 60))->format('Y-m-d');
            DB::table('nsrdi_logs')->insert([
                'dja_id' => null,
                'aircraft_registration' => $regs[array_rand($regs)],
                'plan_date' => null,
                'report_date' => $reportDate,
                'nsrdi_number' => 'NSRDI-OD-'.rand(1000, 9999),
                'category' => collect(['SB', 'AD', 'EO'])->random(),
                'description' => 'NSRDI overdue',
                'status' => 'Open',
                'plan_station' => $stations[array_rand($stations)],
                'act_station' => $stations[array_rand($stations)],
                'due_date' => $dueDate,
                'aoc' => $ops[array_rand($ops)],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->command->info("Today's demo data added: {$today}");
    }
}
