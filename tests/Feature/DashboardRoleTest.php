<?php

namespace Tests\Feature;

use App\Helpers\RoleHelper;
use App\Livewire\Reports\KpiDashboard;
use App\Models\Ims\Category;
use App\Models\Ims\Item;
use App\Models\Ims\Location;
use App\Models\Ims\Unit;
use App\Models\LgtRecord;
use App\Models\User;
use App\Services\Kpi\AttentionList;
use App\Services\Kpi\ModuleTiles;
use App\Services\Kpi\ReportPeriod;
use App\Support\DashboardScope;
use Carbon\Carbon;
use Database\Seeders\RegistryPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/** What each role's dashboard shows: tiles of its own menus, things to chase, and the daily / weekly / monthly views. */
class DashboardRoleTest extends TestCase
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

    private function titles(User $u): array
    {
        $tiles = app(ModuleTiles::class)->build($u, ReportPeriod::week(now()));

        return collect($tiles)->flatten(1)->pluck('title')->all();
    }

    public function test_every_working_role_gets_daily_weekly_and_monthly_and_the_kpi_block(): void
    {
        foreach ([...RoleHelper::ALL_PIC, ...RoleHelper::COD_DESK, RoleHelper::MANAGER, RoleHelper::ADMIN_CGK] as $role) {
            $scope = DashboardScope::for($this->user($role));
            $this->assertSame(['daily', 'weekly', 'monthly'], $scope->periods, $role);
            $this->assertContains('kpi', $scope->modules, $role);
        }
    }

    public function test_division_leads_see_all_stations(): void
    {
        foreach ([...RoleHelper::ALL_PIC, ...RoleHelper::COD_DESK] as $role) {
            $this->assertNull(DashboardScope::for($this->user($role))->stations, $role);
        }
    }

    public function test_tiles_follow_the_menus_of_the_role(): void
    {
        $painting = $this->titles($this->user(RoleHelper::PIC_PAINTING));
        $this->assertContains('NSRDI DJA', $painting);
        $this->assertNotContains('WO DJA', $painting);
        $this->assertNotContains('Aircraft Cleaning', $painting);

        $aiec = $this->titles($this->user(RoleHelper::PIC_AIEC));
        $this->assertContains('Aircraft Cleaning', $aiec);
        $this->assertNotContains('WO DJA', $aiec);
        $this->assertNotContains('NSRDI DJA', $aiec);

        $cbm = $this->titles($this->user(RoleHelper::PIC_CBM));
        $this->assertContains('WO DJA', $cbm);
        $this->assertContains('CML', $cbm);
        $this->assertNotContains('Aircraft Cleaning', $cbm);

        $finishing = $this->titles($this->user(RoleHelper::PIC_FINISHING));
        $this->assertContains('Long Ground Time', $finishing);
        $this->assertContains('Inventory (IMS)', $finishing);
        $this->assertNotContains('WO DJA', $finishing);

        $manager = $this->titles($this->user(RoleHelper::MANAGER));
        $this->assertContains('WO DJA', $manager);
        $this->assertContains('Aircraft Cleaning', $manager);
    }

    public function test_production_tiles_compare_with_the_previous_period(): void
    {
        $dja = fn ($date) => DB::table('daily_job_assignments')->insertGetId(['date' => $date, 'aircraft_registration' => 'PK-A', 'task_id' => 'T'.$date, 'station' => 'CGK', 'created_at' => now(), 'updated_at' => now()]);
        $log = fn ($djaId, $status) => DB::table('wo_logs')->insert(['aircraft_registration' => 'PK-A', 'date' => '2026-10-08', 'status' => $status, 'act_station' => 'CGK', 'dja_id' => $djaId, 'created_at' => now(), 'updated_at' => now()]);
        $thisWeek = $dja('2026-10-08');
        $lastWeek = $dja('2026-10-01');
        $log($thisWeek, 'Closed');
        $log($thisWeek, 'Closed');
        $log($lastWeek, 'Closed');
        $log($lastWeek, 'Open');

        $tiles = app(ModuleTiles::class)->build($this->user(RoleHelper::MANAGER), ReportPeriod::week(Carbon::parse('2026-10-08')));
        $wo = collect($tiles['production'])->firstWhere('key', 'wo');

        $this->assertEquals(50.0, $wo['delta'], '100% this week against 50% last week is +50 points');
    }

    public function test_attention_list_is_built_from_the_work_the_role_can_act_on(): void
    {
        LgtRecord::create(['work_date' => '2026-10-10', 'station' => 'CGK', 'aircraft_registration' => 'PK-LQA', 'sta_time' => '06:00', 'std_time' => '14:00']);
        DB::table('ims_repair_waiting')->insert([
            'repair_code' => 'RPR-1', 'item_id' => $this->makeItem(), 'qty' => 1, 'fault_description' => 'cracked', 'priority' => 'normal',
            'location_id' => $this->makeLocation(), 'received_at' => now(), 'source' => 'aircraft', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('ims_transactions')->insert([
            'code' => 'OUT-1', 'type' => 'out', 'status' => 'pending_approval', 'usage_type' => 'consume', 'purpose_description' => 'Replace arm assy',
            'requested_by' => $this->user(RoleHelper::PIC_CBM)->id, 'requested_at' => now(), 'submitted_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $labels = fn (string $role) => collect(app(AttentionList::class)->build($this->user($role)))->pluck('label')->all();

        $this->assertContains('Draft LGT menunggu diisi pekerjaannya', $labels(RoleHelper::PIC_FINISHING));
        $this->assertNotContains('Draft LGT menunggu diisi pekerjaannya', $labels(RoleHelper::ADMIN_COD), 'the drafter does not fill it in');
        $this->assertContains('Barang rusak menunggu ACC tim repair', $labels(RoleHelper::PIC_SUPPORTING));
        $this->assertNotContains('Barang rusak menunggu ACC tim repair', $labels(RoleHelper::COD));
        $this->assertContains('Permintaan barang menunggu persetujuan Anda', $labels(RoleHelper::MANAGER));
        $this->assertNotContains('Permintaan barang menunggu persetujuan Anda', $labels(RoleHelper::PIC_PAINTING));
    }

    public function test_kpi_page_opens_for_every_role_and_division_leads_can_pick_a_station(): void
    {
        foreach ([...RoleHelper::ALL_PIC, ...RoleHelper::COD_DESK, RoleHelper::MANAGER] as $role) {
            $this->actingAs($this->user($role))->get('/kpi')->assertOk();
        }

        $pic = $this->user(RoleHelper::PIC_PAINTING);
        Livewire::actingAs($pic)->test(KpiDashboard::class)->set('station', 'SUB')->assertOk()->assertViewHas('canPickStation', true);

        $plain = User::factory()->create(['is_default_password' => false, 'status' => 'active', 'station' => 'CGK']);
        $plain->givePermissionTo('kpi.view');
        Livewire::actingAs($plain)->test(KpiDashboard::class)->assertViewHas('canPickStation', false);
    }

    private function makeItem(): int
    {
        $cat = Category::create(['code' => 'C1', 'name' => 'Cat']);
        $unit = Unit::create(['code' => 'PCS', 'name' => 'Pieces']);

        return Item::create(['part_number' => 'PN-1', 'name' => 'Arm', 'description' => 'd', 'category_id' => $cat->id, 'unit_id' => $unit->id, 'tracking_type' => 'quantity'])->id;
    }

    private function makeLocation(): int
    {
        return Location::create(['code' => 'G1', 'name' => 'Rak A', 'type' => 'warehouse'])->id;
    }
}
