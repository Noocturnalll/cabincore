<?php

namespace Tests\Feature;

use App\Helpers\RoleHelper;
use App\Livewire\Reports\KpiDashboard;
use App\Models\ComplianceEntry;
use App\Models\Division;
use App\Models\DocumentAccuracy;
use App\Models\LgtRecord;
use App\Models\RosterEntry;
use App\Models\User;
use App\Services\Kpi\ReportPeriod;
use App\Services\Kpi\StationKpiService;
use Carbon\Carbon;
use Database\Seeders\RegistryPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class KpiDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-09 10:00:00');
        $this->seed(RegistryPermissionSeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function roster(string $station, string $team, string $shift, int $people, string $date = '2026-10-07'): void
    {
        for ($i = 0; $i < $people; $i++) {
            RosterEntry::insert(['work_date' => $date, 'station' => $station, 'employee_id' => "$station$team$shift$i", 'team' => $team, 'shift' => $shift, 'shift_code' => 'X', 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    private function seedWorld(): void
    {
        // CGK: CBM 2 morning (2x8h) + 1 night (10h) = 26h ; AIEC 1 morning = 8h
        $this->roster('CGK', 'CBM', 'PAGI', 2);
        $this->roster('CGK', 'CBM', 'MALAM', 1);
        $this->roster('CGK', 'AIEC', 'PAGI', 1);
        // SUB: CBM 1 morning = 8h
        $this->roster('SUB', 'CBM', 'PAGI', 1);

        // man hours used
        DB::table('wo_logs')->insert(['aircraft_registration' => 'PK-A', 'date' => '2026-10-07', 'status' => 'Closed', 'man_hour' => 13, 'act_station' => 'CGK', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('dmi_logs')->insert(['aircraft_registration' => 'PK-A', 'date' => '2026-10-07', 'status' => 'Closed', 'man_hour' => 2, 'plan_station' => 'CGK', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('wo_logs')->insert(['aircraft_registration' => 'PK-B', 'date' => '2026-10-07', 'status' => 'Closed', 'man_hour' => 4, 'act_station' => 'SUB', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('aircraft_cleanings')->insert(['aircraft_registration' => 'PK-A', 'date' => '2026-10-07', 'shift' => 'Pagi', 'type' => 'Transit', 'status' => 'Closed', 'station' => 'CGK', 'man_hour' => 6, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('nsrdi_logs')->insert(['aircraft_registration' => 'PK-A', 'refresh_date' => '2026-10-07', 'category' => 'PAINTING', 'status' => 'Closed', 'man_hour' => 3, 'act_station' => 'CGK', 'created_at' => now(), 'updated_at' => now()]);

        DocumentAccuracy::create(['work_date' => '2026-10-07', 'station' => 'CGK', 'cml_reported' => 90, 'nsrdi_reported' => 10, 'cml_missed' => 5, 'nsrdi_missed' => 0]);
        foreach ([['CLOSED', 'CLOSED'], ['CLOSED', 'CANCEL'], ['CANCEL', 'CANCEL'], ['CLOSED', 'CLOSED']] as [$c, $a]) {
            LgtRecord::create(['work_date' => '2026-10-07', 'station' => 'CGK', 'aircraft_registration' => 'PK-A', 'cbm_status' => $c, 'aiec_status' => $a]);
        }
        ComplianceEntry::create(['entry_id' => 'CGK_1_Pagi', 'station' => 'CGK', 'work_date' => '2026-10-07', 'shift' => 'Pagi', 'url_5r' => 'u', 'url_att' => 'u', 'url_brf' => 'u']);
    }

    private function day(): ReportPeriod
    {
        return ReportPeriod::day(Carbon::parse('2026-10-07'));
    }

    public function test_pivot_per_station_for_all_teams(): void
    {
        $this->seedWorld();

        $data = (new StationKpiService)->build($this->day(), null, 'ALL');
        $rows = collect($data['rows'])->keyBy('station');

        $cgk = $rows['CGK'];
        $this->assertEquals(26 + 8, $cgk['capacity']);                 // CBM 26h + AIEC 8h
        $this->assertEquals(4.0, $cgk['mp_avg']);
        $this->assertEquals(13 + 2 + 6 + 3, $cgk['used']);             // WO + DMI + cleaning + painting NSRDI
        $this->assertEquals(round(24 / 34 * 100, 1), $cgk['utilisation']);
        $this->assertEquals(95.0, $cgk['accuracy']);
        $this->assertEquals(75.0, $cgk['lgt_cbm']);
        $this->assertEquals(50.0, $cgk['lgt_aiec']);
        $this->assertEquals(50.0, $cgk['compliance'], 'CGK reports Pagi and Malam; only Pagi arrived');

        $sub = $rows['SUB'];
        $this->assertEquals(8, $sub['capacity']);
        $this->assertEquals(4, $sub['used']);
        $this->assertEquals(50.0, $sub['utilisation']);
        $this->assertNull($sub['accuracy']);

        $this->assertEquals(34 + 8, $data['total']['capacity']);
        $this->assertEquals(28, $data['total']['used']);
        $this->assertSame(2, $data['coverage']['WO']['timed']);
    }

    public function test_team_filter_limits_both_capacity_and_the_hours_that_count(): void
    {
        $this->seedWorld();
        $svc = new StationKpiService;

        $cbm = collect($svc->build($this->day(), null, 'CBM')['rows'])->keyBy('station')['CGK'];
        $this->assertEquals(26, $cbm['capacity']);
        $this->assertEquals(13 + 2, $cbm['used'], 'CBM counts WO and DMI, not cleaning or painting NSRDI');

        $aiec = collect($svc->build($this->day(), null, 'AIEC')['rows'])->keyBy('station')['CGK'];
        $this->assertEquals(8, $aiec['capacity']);
        $this->assertEquals(6, $aiec['used']);

        $painting = collect($svc->build($this->day(), ['CGK'], 'PAINTING')['rows'])->keyBy('station')['CGK'];
        $this->assertEquals(3, $painting['used']);
        $this->assertEquals(0, $painting['capacity']);
        $this->assertNull($painting['utilisation'], 'no capacity, no percentage');
    }

    public function test_station_filter(): void
    {
        $this->seedWorld();
        $rows = (new StationKpiService)->build($this->day(), ['SUB'], 'ALL')['rows'];

        $this->assertCount(1, $rows);
        $this->assertSame('SUB', $rows[0]['station']);
    }

    public function test_manager_sees_every_station_and_picks_a_team_but_a_pic_is_held_to_their_scope(): void
    {
        $this->seedWorld();

        $manager = User::factory()->create(['is_default_password' => false, 'status' => 'active']);
        $manager->assignRole(RoleHelper::MANAGER);
        Livewire::actingAs($manager)->test(KpiDashboard::class)
            ->set('kind', 'day')->set('date', '2026-10-07')
            ->assertSee('CGK')->assertSee('SUB')
            ->set('team', 'AIEC')->assertSee('CGK')->assertDontSee('LGT CBM');

        $pic = User::factory()->create([
            'is_default_password' => false, 'status' => 'active', 'station' => 'SUB',
            'division_id' => Division::where('name', 'Cabin')->value('id'),
        ]);
        $pic->assignRole(RoleHelper::PIC_CABIN);
        Livewire::actingAs($pic)->test(KpiDashboard::class)
            ->set('kind', 'day')->set('date', '2026-10-07')
            ->set('station', 'CGK')->set('team', 'AIEC')   // trying to widen the scope does nothing
            ->assertSee('SUB')->assertDontSeeHtml('wire:key="kd-CGK"')->assertSee('Station SUB');
    }

    public function test_access_and_navigation_state(): void
    {
        $nobody = User::factory()->create(['is_default_password' => false, 'status' => 'active']);
        $this->actingAs($nobody)->get('/kpi')->assertForbidden();

        $manager = User::factory()->create(['is_default_password' => false, 'status' => 'active']);
        $manager->assignRole(RoleHelper::MANAGER);
        $this->actingAs($manager)->get('/kpi')->assertOk()->assertSee('Dashboard KPI');

        $c = Livewire::actingAs($manager)->test(KpiDashboard::class)->set('kind', 'week')->set('date', '2026-10-09');
        $c->call('move', -1)->assertSet('date', '2026-10-02');
        $c->call('setKind', 'month')->call('move', 1)->assertSet('date', '2026-11-02');
        $c->call('setKind', 'nonsense')->assertSet('kind', 'week');
        $c->call('sortBy', 'used')->assertSet('sort', 'used')->assertSet('dir', 'desc')->call('sortBy', 'used')->assertSet('dir', 'asc');
    }
}
