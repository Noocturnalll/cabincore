<?php

namespace Tests\Feature;

use App\Exports\DailyReportDayExport;
use App\Exports\PaintingDailyReportDayExport;
use App\Models\CmlLog;
use App\Models\DailyJobAssignment;
use App\Models\WoLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Concerns\WithTitle;
use Tests\TestCase;

class DailyReportDayExportTest extends TestCase
{
    use RefreshDatabase;

    private function titles(array $sheets): array
    {
        return array_map(fn ($s) => $s instanceof WithTitle ? $s->title() : '?', $sheets);
    }

    public function test_one_workbook_holds_every_tab_and_the_advanced_pivots_with_unique_names(): void
    {
        $dja = DailyJobAssignment::create(['task_id' => 'W1', 'date' => '2026-10-12', 'job_type' => 'R01/WO', 'aircraft_registration' => 'PK-A', 'description' => 'x', 'station' => 'CGK']);
        WoLog::create(['dja_id' => $dja->id, 'wo_number' => 'W1', 'aircraft_registration' => 'PK-A', 'plan_station' => 'CGK', 'status' => 'Closed', 'date' => '2026-10-12', 'is_submitted' => true]);
        CmlLog::create(['date' => '2026-10-12', 'aircraft_registration' => 'PK-A', 'station' => 'CGK', 'no_doc' => 'C1', 'description' => 'seat', 'status' => 'Closed', 'start_perform' => '17,4', 'finish_perform' => '18,4', 'mp1' => 'A']);
        CmlLog::create(['date' => '2026-10-12', 'aircraft_registration' => 'PK-B', 'station' => 'CGK', 'no_doc' => 'C2', 'description' => 'check', 'status' => 'Closed', 'task_type' => 'DBI']);

        $titles = $this->titles((new DailyReportDayExport('2026-10-12'))->sheets());

        $this->assertSame($titles, array_values(array_unique($titles)), 'sheet names must be unique');
        foreach ($titles as $t) {
            $this->assertLessThanOrEqual(31, mb_strlen($t));
        }
        foreach (['DJA WO - Detail', 'Unplanned WO - Pivot', 'DJA DMI - Detail', 'DJA NSRDI - Pivot', 'CML - Detail', 'DBI - Detail', 'Umur Open', 'Man-hour CML & DBI'] as $expected) {
            $this->assertContains($expected, $titles);
        }
    }

    public function test_painting_day_export_has_both_tabs(): void
    {
        $titles = $this->titles((new PaintingDailyReportDayExport('2026-10-12'))->sheets());

        $this->assertSame($titles, array_values(array_unique($titles)));
        $this->assertContains('DJA Painting - Detail', $titles);
        $this->assertContains('Unplanned Painting - Pivot', $titles);
    }
}
