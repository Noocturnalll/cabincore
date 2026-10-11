<?php

namespace Tests\Feature;

use App\Models\DailyJobAssignment;
use App\Models\NsrdiLog;
use App\Models\WoLog;
use App\Services\Dja\CodReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CodReportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_cod_report_with_exact_format_and_dynamic_stations(): void
    {
        $date = '2026-10-10';

        // 1. DJA R01/WO assignments and logs
        $djaCgk = DailyJobAssignment::create([
            'date' => $date,
            'station' => 'CGK',
            'task_id' => 'WO-CGK-1',
            'job_type' => 'R01/WO',
            'aircraft_registration' => 'PK-GAA',
        ]);
        WoLog::create([
            'dja_id' => $djaCgk->id,
            'date' => $date,
            'plan_station' => 'CGK',
            'status' => 'Closed',
            'wo_number' => 'WO-CGK-1',
            'aircraft_registration' => 'PK-GAA',
        ]);

        // Dynamic station: DJJ
        $djaDjj = DailyJobAssignment::create([
            'date' => $date,
            'station' => 'DJJ',
            'task_id' => 'WO-DJJ-1',
            'job_type' => 'R01/WO',
            'aircraft_registration' => 'PK-GAB',
        ]);
        WoLog::create([
            'dja_id' => $djaDjj->id,
            'date' => $date,
            'plan_station' => 'DJJ',
            'status' => 'Closed',
            'wo_number' => 'WO-DJJ-1',
            'aircraft_registration' => 'PK-GAB',
        ]);

        // 2. Open NSRDI log with code_open
        $djaNsrdi = DailyJobAssignment::create([
            'date' => $date,
            'station' => 'SUB',
            'task_id' => 'NSRDI-SUB-1',
            'job_type' => 'AOC/NSRDI',
            'aircraft_registration' => 'PK-GAC',
        ]);
        NsrdiLog::create([
            'dja_id' => $djaNsrdi->id,
            'plan_date' => $date,
            'plan_station' => 'SUB',
            'status' => 'Open',
            'code_open' => 'IRR',
            'nsrdi_number' => 'NSRDI-SUB-1',
            'aircraft_registration' => 'PK-GAC',
        ]);

        // 3. Unplanned NSRDI log
        NsrdiLog::create([
            'dja_id' => null,
            'close_date' => $date,
            'plan_station' => 'CGK',
            'act_station' => 'CGK',
            'status' => 'Closed',
            'nsrdi_number' => 'NSRDI-UNPLANNED-1',
            'aircraft_registration' => 'PK-GAD',
        ]);

        $service = app(CodReportService::class);
        $text = $service->generateReportText($date);

        // Verify sections
        $this->assertStringContainsString('> CABIN ON-DUTY PRODUCTION 10 OCT 2026', $text);
        $this->assertStringContainsString('> DJA R01/WO', $text);
        $this->assertStringContainsString('- `CGK:` 1 (C:1 R:-)', $text);
        $this->assertStringContainsString('- `DJJ:` 1 (C:1 R:-)', $text); // dynamic station
        $this->assertStringContainsString('> DMI CBM ', $text);
        $this->assertStringContainsString('> NSRDI CBM DJA R01 ', $text);
        $this->assertStringContainsString('> REASON OPEN', $text);
        $this->assertStringContainsString('`IRR:`1', $text);
        $this->assertStringContainsString('`GSE:`', $text);
        $this->assertStringContainsString('> UNPLANED NSRDIL', $text);
        $this->assertStringContainsString('- `CGK:`1', $text);
        $this->assertStringContainsString('JAM KERJA: 19:00 - 07:00 WIB', $text);
    }
}
