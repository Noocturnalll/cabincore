<?php

namespace Tests\Feature;

use App\Models\AircraftCleaning;
use App\Models\CmlLog;
use App\Models\NsrdiLog;
use App\Models\WoLog;
use App\Services\Kpi\CapacityOverview;
use App\Services\Kpi\ReportPeriod;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CapacityOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_division_is_compared_with_its_own_capacity(): void
    {
        $day = '2026-10-12';
        WoLog::create(['aircraft_registration' => 'PK-A', 'wo_number' => 'W1', 'date' => $day, 'man_hour' => 30]);
        CmlLog::create(['date' => $day, 'aircraft_registration' => 'PK-A', 'station' => 'CGK', 'no_doc' => 'C1', 'description' => 'seat', 'status' => 'Closed', 'start_perform' => '10', 'finish_perform' => '12', 'mp1' => 'A', 'mp2' => 'B']);
        NsrdiLog::create(['aircraft_registration' => 'PK-A', 'nsrdi_number' => 'N1', 'plan_date' => $day, 'category' => 'PAINTING', 'man_hour' => 20]);
        NsrdiLog::create(['aircraft_registration' => 'PK-A', 'nsrdi_number' => 'N2', 'plan_date' => $day, 'category' => 'CBM', 'man_hour' => 10]);

        $teams = ['CBM' => ['hours' => 100, 'mp_avg' => 10], 'PAINTING' => ['hours' => 40, 'mp_avg' => 4], 'IRREG' => ['hours' => 25, 'mp_avg' => 2]];
        $out = (new CapacityOverview)->build(ReportPeriod::day(Carbon::parse($day)), $teams);

        $by = collect($out['divisions'])->keyBy('team');
        $this->assertEquals(30 + 4 + 10, $by['CBM']['used']);          // WO 30 + CML 2 people x 2 h + non-paint NSRDI 10
        $this->assertEquals(44.0, $by['CBM']['pct']);
        $this->assertSame('low', $by['CBM']['state']);
        $this->assertEquals(20, $by['PAINTING']['used']);
        $this->assertEquals(50.0, $by['PAINTING']['pct']);
        $this->assertNull($by['IRREG']['used']);                      // no job log carries hours for this team
        $this->assertSame('none', $by['IRREG']['state']);
    }

    public function test_over_the_limit_is_flagged_and_aiec_capability_is_listed_per_station(): void
    {
        $day = '2026-10-12';
        foreach (range(1, 6) as $i) {
            AircraftCleaning::create([
                'date' => $day, 'station' => 'CGK', 'type' => 'DCI', 'shift' => 'PAGI', 'aircraft_registration' => "PK-A{$i}",
            ]);
        }

        $out = (new CapacityOverview)->build(ReportPeriod::day(Carbon::parse($day)), []);

        $cgk = collect($out['aiec'])->firstWhere('station', 'CGK');
        $this->assertNotNull($cgk);
        $this->assertSame(4, $cgk['DCI']['cap']);
        $this->assertSame(6, $cgk['DCI']['peak']);
        $this->assertTrue($cgk['DCI']['over']);
        $this->assertSame('over', $cgk['state']);
    }
}
