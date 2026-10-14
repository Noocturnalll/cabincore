<?php

namespace Tests\Feature;

use App\Helpers\RoleHelper;
use App\Livewire\Modules\Capacity\Index as CapacityPage;
use App\Models\Aircraft;
use App\Models\Aoc;
use App\Models\User;
use App\Services\Kpi\OperationalPivot;
use App\Services\Kpi\ReportPeriod;
use App\Support\TaskStation;
use Carbon\Carbon;
use Database\Seeders\RegistryPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/** Capacity and the dashboard read the same logs the Daily Report archives, and must place a task the same way. */
class CapacityDataFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-13 10:00:00');
        $this->seed(RegistryPermissionSeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function fleet(): void
    {
        $lion = Aoc::create(['code' => 'JT', 'name' => 'Lion Air', 'is_active' => true, 'sort_order' => 1]);
        $batik = Aoc::create(['code' => 'ID', 'name' => 'Batik Air', 'is_active' => true, 'sort_order' => 2]);
        // PK-L.. is shared: Lion and Batik both fly registrations that start with PK-L
        Aircraft::create(['registration' => 'PK-LKX', 'tipe' => 'B737-800', 'maskapai' => 'Lion Air', 'aoc_id' => $lion->id, 'status' => 'Aktif']);
        Aircraft::create(['registration' => 'PK-LBK', 'tipe' => 'A320', 'maskapai' => 'Batik Air', 'aoc_id' => $batik->id, 'status' => 'Aktif']);
    }

    private function nsrdi(string $reg, array $extra = []): void
    {
        DB::table('nsrdi_logs')->insert($extra + [
            'aircraft_registration' => $reg, 'status' => 'Closed', 'close_date' => '2026-10-12', 'plan_date' => '2026-10-12',
            'act_station' => 'CGK', 'is_submitted' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function manager(): User
    {
        $u = User::factory()->create(['is_default_password' => false, 'status' => 'active', 'station' => 'CGK']);
        $u->assignRole(RoleHelper::MANAGER);

        return $u;
    }

    public function test_capacity_counts_a_batik_aircraft_under_batik_not_lion(): void
    {
        $this->fleet();
        $this->nsrdi('PK-LKX');                       // Lion
        $this->nsrdi('PK-LBK', ['aoc' => 'ID']);      // Batik, with the AOC code in the log's own column
        $this->nsrdi('PK-LBK');                       // Batik, nothing in the log: the aircraft master decides

        $totals = Livewire::actingAs($this->manager())->test(CapacityPage::class)
            ->set('activeDate', '2026-10-12')->viewData('nsrdiTotals');

        $this->assertSame(1, $totals['JT'], 'only the Lion aircraft is Lion');
        $this->assertSame(2, $totals['ID'], 'both Batik records are Batik');
        $this->assertSame(3, $totals['TOTAL']);
    }

    public function test_the_aircraft_master_wins_over_a_wrong_aoc_text_in_the_log(): void
    {
        $this->fleet();
        $this->nsrdi('PK-LBK', ['aoc' => 'JT']);   // the sheet said JT, the master says Batik

        $totals = Livewire::actingAs($this->manager())->test(CapacityPage::class)
            ->set('activeDate', '2026-10-12')->viewData('nsrdiTotals');

        $this->assertSame(1, $totals['ID']);
        $this->assertSame(0, $totals['JT']);
    }

    public function test_a_planned_task_belongs_to_its_plan_station_and_an_unplanned_one_to_where_it_was_done(): void
    {
        $this->fleet();
        $dja = DB::table('daily_job_assignments')->insertGetId(['date' => '2026-10-12', 'aircraft_registration' => 'PK-LKX', 'task_id' => 'T1', 'station' => 'CGK', 'created_at' => now(), 'updated_at' => now()]);
        // planned at CGK, worked at SUB
        DB::table('wo_logs')->insert(['aircraft_registration' => 'PK-LKX', 'date' => '2026-10-12', 'status' => 'Closed', 'plan_station' => 'CGK', 'act_station' => 'SUB', 'dja_id' => $dja, 'created_at' => now(), 'updated_at' => now()]);
        // not planned: worked at SUB, no plan station
        DB::table('wo_logs')->insert(['aircraft_registration' => 'PK-LKX', 'date' => '2026-10-12', 'status' => 'Closed', 'act_station' => 'SUB', 'created_at' => now(), 'updated_at' => now()]);

        $pivot = app(OperationalPivot::class)->build($this->manager(), ReportPeriod::day(Carbon::parse('2026-10-12')));

        $this->assertSame(1, $pivot['rows']['CGK']['wo']['total'], 'the planned task stays at its plan station');
        $this->assertArrayNotHasKey('wo', $pivot['rows']['SUB'] ?? [], 'it is not also counted at SUB');
        $this->assertSame(1, $pivot['rows']['SUB']['unplanned']['total'], 'the unplanned task is counted where it was done');
    }

    public function test_station_rule_falls_back_in_the_documented_order(): void
    {
        $planned = (object) ['dja_id' => 5, 'plan_station' => '', 'act_station' => 'sub'];
        $this->assertSame('SUB', TaskStation::of($planned), 'planned without plan station and without a DJA falls back to the actual station');

        $planned2 = (object) ['dja_id' => 5, 'plan_station' => 'cgk', 'act_station' => 'SUB'];
        $this->assertSame('CGK', TaskStation::of($planned2));

        $unplanned = (object) ['dja_id' => null, 'plan_station' => 'CGK', 'act_station' => 'SUB'];
        $this->assertSame('SUB', TaskStation::of($unplanned));

        $unplanned2 = (object) ['dja_id' => null, 'plan_station' => 'CGK', 'act_station' => null];
        $this->assertSame('CGK', TaskStation::of($unplanned2));
    }
}
