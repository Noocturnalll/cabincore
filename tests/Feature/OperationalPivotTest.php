<?php

namespace Tests\Feature;

use App\Helpers\RoleHelper;
use App\Livewire\Reports\KpiDashboard;
use App\Models\User;
use App\Services\Kpi\OperationalPivot;
use App\Services\Kpi\ReportPeriod;
use Carbon\Carbon;
use Database\Seeders\RegistryPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/** Open / closed / rate per station and per day for WO, DMI, NSRDI, unplanned, CML and every cleaning type. */
class OperationalPivotTest extends TestCase
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

    private function user(string $role): User
    {
        $u = User::factory()->create(['is_default_password' => false, 'status' => 'active', 'station' => 'CGK']);
        $u->assignRole($role);

        return $u;
    }

    private function dja(string $date): int
    {
        return DB::table('daily_job_assignments')->insertGetId(['date' => $date, 'aircraft_registration' => 'PK-A', 'task_id' => 'T'.uniqid(), 'station' => 'CGK', 'created_at' => now(), 'updated_at' => now()]);
    }

    private function wo(string $table, ?int $dja, string $date, string $station, string $status): void
    {
        $dateCol = $table === 'nsrdi_logs' ? 'refresh_date' : 'date';
        DB::table($table)->insert(['aircraft_registration' => 'PK-A', $dateCol => $date, 'status' => $status, 'act_station' => $station, 'dja_id' => $dja, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function cleaning(string $type, string $station, string $date, string $status, float $hours = 1): void
    {
        DB::table('aircraft_cleanings')->insert(['aircraft_registration' => 'PK-'.uniqid(), 'date' => $date, 'shift' => 'Pagi', 'type' => $type, 'status' => $status, 'station' => $station, 'man_hour' => $hours, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function seedWorld(): void
    {
        $mon = $this->dja('2026-10-07');
        $tue = $this->dja('2026-10-08');
        // CGK: WO 3 planned, 2 closed ; SUB: WO 1 planned, 1 closed
        $this->wo('wo_logs', $mon, '2026-10-07', 'CGK', 'Closed');
        $this->wo('wo_logs', $mon, '2026-10-07', 'CGK', 'Closed');
        $this->wo('wo_logs', $tue, '2026-10-08', 'CGK', 'Open');
        $this->wo('wo_logs', $tue, '2026-10-08', 'SUB', 'Closed');
        $this->wo('dmi_logs', $mon, '2026-10-07', 'CGK', 'Open');
        $this->wo('nsrdi_logs', $tue, '2026-10-08', 'CGK', 'Closed');
        // unplanned: no DJA
        $this->wo('wo_logs', null, '2026-10-08', 'CGK', 'Closed');
        $this->wo('dmi_logs', null, '2026-10-08', 'SUB', 'Open');
        DB::table('cml_logs')->insert(['aircraft_registration' => 'PK-A', 'date' => '2026-10-07', 'status' => 'Closed', 'station' => 'CGK', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('cml_logs')->insert(['aircraft_registration' => 'PK-B', 'date' => '2026-10-07', 'status' => 'Open', 'station' => 'CGK', 'created_at' => now(), 'updated_at' => now()]);

        foreach ([['Transit', 'CGK', '2026-10-07', 'Closed'], ['Transit', 'CGK', '2026-10-07', 'Closed'], ['Transit', 'CGK', '2026-10-08', 'Open'],
            ['GCE', 'CGK', '2026-10-08', 'Closed'], ['GCI', 'SUB', '2026-10-08', 'Closed'], ['DCI', 'SUB', '2026-10-07', 'Open'], ['DBI', 'CGK', '2026-10-07', 'Closed'], ['DCE', 'CGK', '2026-10-07', 'Closed'], ['General', 'CGK', '2026-10-07', 'Closed']] as [$t, $s, $d, $st]) {
            $this->cleaning($t, $s, $d, $st);
        }
    }

    private function week(): ReportPeriod
    {
        return ReportPeriod::week(Carbon::parse('2026-10-08'));   // Thu 2026-10-08 starts... covers the 7th and 8th
    }

    public function test_planned_work_is_counted_per_station_with_open_closed_and_rate(): void
    {
        $this->seedWorld();

        $p = app(OperationalPivot::class)->build($this->user(RoleHelper::MANAGER), ReportPeriod::of('week', Carbon::parse('2026-10-07'), 3));

        $this->assertSame(['total' => 3, 'closed' => 2, 'open' => 1, 'rate' => 66.7], $p['rows']['CGK']['wo']);
        $this->assertSame(['total' => 1, 'closed' => 1, 'open' => 0, 'rate' => 100.0], $p['rows']['SUB']['wo']);
        $this->assertSame(['total' => 4, 'closed' => 3, 'open' => 1, 'rate' => 75.0], $p['total']['wo']);
        $this->assertSame(1, $p['rows']['CGK']['dmi']['total']);
        $this->assertSame(0, $p['rows']['CGK']['dmi']['closed']);
        $this->assertSame(100.0, $p['rows']['CGK']['nsrdi']['rate']);
    }

    public function test_unplanned_and_cml_have_their_own_columns(): void
    {
        $this->seedWorld();

        $p = app(OperationalPivot::class)->build($this->user(RoleHelper::MANAGER), ReportPeriod::of('week', Carbon::parse('2026-10-07'), 3));

        $this->assertSame(1, $p['rows']['CGK']['unplanned']['closed']);
        $this->assertSame(1, $p['rows']['SUB']['unplanned']['open']);
        $this->assertSame(['total' => 2, 'closed' => 1, 'open' => 1, 'rate' => 50.0], $p['rows']['CGK']['cml']);
    }

    public function test_every_cleaning_type_is_split_out_with_its_own_closed_rate(): void
    {
        $this->seedWorld();

        $p = app(OperationalPivot::class)->build($this->user(RoleHelper::MANAGER), ReportPeriod::of('week', Carbon::parse('2026-10-07'), 3));

        $this->assertSame(['Transit', 'General', 'DCI', 'DCE', 'DBI', 'GCI', 'GCE'], $p['cleaning_types']);
        $this->assertSame(['total' => 3, 'closed' => 2, 'open' => 1, 'rate' => 66.7], $p['rows']['CGK']['cleaning:Transit']);
        $this->assertSame(1, $p['rows']['CGK']['cleaning:GCE']['closed']);
        $this->assertSame(1, $p['rows']['SUB']['cleaning:GCI']['closed']);
        $this->assertSame(0.0, (float) $p['rows']['SUB']['cleaning:DCI']['rate']);
        $this->assertSame(9, $p['total']['cleaning']['total']);
    }

    public function test_days_give_the_same_figures_one_day_at_a_time(): void
    {
        $this->seedWorld();

        $p = app(OperationalPivot::class)->build($this->user(RoleHelper::MANAGER), ReportPeriod::of('week', Carbon::parse('2026-10-07'), 3));

        $this->assertSame(2, $p['days']['2026-10-07']['wo']['closed']);
        $this->assertSame(['total' => 2, 'closed' => 1, 'open' => 1, 'rate' => 50.0], $p['days']['2026-10-08']['wo']);
        $this->assertSame(['2026-10-07', '2026-10-08'], array_keys($p['days']));
    }

    public function test_a_single_day_and_a_station_filter_narrow_the_figures(): void
    {
        $this->seedWorld();
        $manager = $this->user(RoleHelper::MANAGER);

        $day = app(OperationalPivot::class)->build($manager, ReportPeriod::day(Carbon::parse('2026-10-07')));
        $this->assertSame(2, $day['rows']['CGK']['wo']['total']);
        $this->assertArrayNotHasKey('SUB', array_filter($day['rows'], fn ($c) => isset($c['wo'])));

        $sub = app(OperationalPivot::class)->build($manager, ReportPeriod::of('week', Carbon::parse('2026-10-07'), 3), ['SUB']);
        $this->assertSame(['SUB'], array_keys($sub['rows']));
    }

    public function test_each_role_only_gets_the_blocks_of_its_menus(): void
    {
        $this->seedWorld();
        $period = ReportPeriod::of('week', Carbon::parse('2026-10-07'), 3);
        $pivot = app(OperationalPivot::class);

        $painting = $pivot->build($this->user(RoleHelper::PIC_PAINTING), $period);
        $this->assertSame(['nsrdi', 'unplanned'], array_keys($painting['blocks']));

        $aiec = $pivot->build($this->user(RoleHelper::PIC_AIEC), $period);
        $this->assertSame(['cleaning'], array_keys($aiec['blocks']));

        $cbm = $pivot->build($this->user(RoleHelper::PIC_CBM), $period);
        $this->assertSame(['wo', 'dmi', 'nsrdi', 'unplanned', 'cml'], array_keys($cbm['blocks']));

        $finishing = $pivot->build($this->user(RoleHelper::PIC_FINISHING), $period);
        $this->assertSame([], $finishing['blocks']);
    }

    public function test_the_dashboard_shows_the_pivot_sections_and_the_section_nav(): void
    {
        $this->seedWorld();

        Livewire::actingAs($this->user(RoleHelper::MANAGER))->test(KpiDashboard::class)
            ->set('kind', 'month')->set('date', '2026-10-08')
            ->assertSee('Open · Closed · Rate per station')->assertSee('Aircraft Cleaning per station')
            ->assertSee('Transit')->assertSee('GCE')->assertSee('DBI')
            ->assertSee('Per hari')->assertSee('sec-kpi', false)->assertSee('kd-snav', false);

        // a single day has no day-by-day block
        Livewire::actingAs($this->user(RoleHelper::MANAGER))->test(KpiDashboard::class)
            ->set('kind', 'day')->set('date', '2026-10-07')->assertDontSee('sec-days', false);
    }
}
