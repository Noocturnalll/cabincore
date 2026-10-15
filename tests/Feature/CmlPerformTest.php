<?php

namespace Tests\Feature;

use App\Models\CmlLog;
use App\Support\CmlKind;
use App\Support\ManHours;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmlPerformTest extends TestCase
{
    use RefreshDatabase;

    public function test_perform_times_follow_the_sheet_notation(): void
    {
        $this->assertSame('17:40', ManHours::clockText('17,4'));
        $this->assertSame('18:50', ManHours::clockText('18,5'));
        $this->assertSame('20:00', ManHours::clockText('20'));
        $this->assertSame('19:05', ManHours::clockText('19,05'));
        $this->assertSame('17:40', ManHours::clockText('17:40'));
        $this->assertSame(1.0, ManHours::duration('17,4', '18,4'));
        $this->assertSame(0.5, ManHours::duration('18,5', '19,2'));
        $this->assertSame(1.0, ManHours::duration('23,3', '0,3'));   // crosses midnight
        $this->assertNull(ManHours::duration('', '18,4'));
    }

    public function test_man_hours_come_from_perform_times_and_named_technicians(): void
    {
        $log = CmlLog::create([
            'date' => '2026-10-12', 'aircraft_registration' => 'PK-WHS', 'station' => 'KOE', 'no_doc' => 'C036366',
            'description' => 'Rectification cover side seat', 'status' => 'Closed', 'task_type' => 'CML',
            'start_perform' => '17,4', 'finish_perform' => '18,4', 'mp1' => 'TERA KANAF', 'mp2' => 'HAMSAH RIADI',
        ]);

        $this->assertSame(2, (int) $log->man_power);
        $this->assertEquals(1.0, (float) $log->individual_mhrs);
        $this->assertEquals(2.0, (float) $log->man_hour);
        $this->assertSame('CML', $log->kind);
    }

    public function test_task_type_dbi_files_the_line_under_dbi(): void
    {
        $log = CmlLog::create([
            'date' => '2026-10-12', 'aircraft_registration' => 'PK-WHS', 'no_doc' => 'C036367',
            'description' => 'Rectification cover side seat 1C', 'status' => 'Closed', 'task_type' => 'DBI',
        ]);
        $this->assertSame('DBI', $log->kind);

        CmlKind::sync();   // the cleanup must not push it back to CML
        $this->assertSame('DBI', $log->fresh()->kind);
    }
}
