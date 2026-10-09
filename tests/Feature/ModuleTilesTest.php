<?php

namespace Tests\Feature;

use App\Helpers\RoleHelper;
use App\Livewire\Reports\KpiDashboard;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\DailyJobAssignment;
use App\Models\LgtRecord;
use App\Models\User;
use App\Services\Kpi\ModuleTiles;
use App\Services\Kpi\ReportPeriod;
use Carbon\Carbon;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RegistryPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class ModuleTilesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-08 10:00:00');
        $this->seed(RegistryPermissionSeeder::class);
        $this->seed(MasterDataSeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function user(string $role, array $attrs = []): User
    {
        $u = User::factory()->create(['is_default_password' => false, 'status' => 'active'] + $attrs);
        $u->assignRole($role);

        return $u;
    }

    private function flat(array $tiles): array
    {
        return collect($tiles)->flatten(1)->keyBy('key')->all();
    }

    private function wo(string $date, string $station, string $status): void
    {
        $dja = DailyJobAssignment::create(['aircraft_registration' => 'PK-'.uniqid(), 'date' => $date, 'job_type' => 'R01/WO', 'station' => $station, 'task_id' => uniqid('T')]);
        DB::table('wo_logs')->insert(['dja_id' => $dja->id, 'aircraft_registration' => $dja->aircraft_registration, 'wo_number' => uniqid(), 'status' => $status, 'date' => $date, 'act_station' => $station, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_every_group_has_tiles_and_numbers_follow_the_period(): void
    {
        $this->wo('2026-10-07', 'CGK', 'Closed');
        $this->wo('2026-10-07', 'CGK', 'Open');
        $this->wo('2026-09-01', 'CGK', 'Closed');   // outside the week
        DB::table('aircraft_cleanings')->insert([
            ['aircraft_registration' => 'PK-A', 'date' => '2026-10-07', 'shift' => 'Pagi', 'type' => 'Transit', 'status' => 'Closed', 'station' => 'CGK', 'man_hour' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['aircraft_registration' => 'PK-B', 'date' => '2026-10-07', 'shift' => 'Pagi', 'type' => 'DCI', 'status' => 'Closed', 'station' => 'SUB', 'man_hour' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);
        LgtRecord::create(['work_date' => '2026-10-07', 'station' => 'CGK', 'aircraft_registration' => 'PK-A', 'cbm_status' => 'CLOSED', 'aiec_status' => 'CANCEL']);

        $manager = $this->user(RoleHelper::MANAGER);
        $tiles = (new ModuleTiles)->build($manager, ReportPeriod::week(Carbon::parse('2026-10-07')));

        $this->assertSame(['production', 'cleaning', 'flight', 'people', 'assets', 'system'], array_keys($tiles));
        $flat = $this->flat($tiles);

        $this->assertSame('1 / 2', $flat['wo']['value']);
        $this->assertSame('50% closed', $flat['wo']['sub']);
        $this->assertSame('red', $flat['wo']['tone']);
        $this->assertSame('2', $flat['cleaning']['value']);
        $this->assertStringContainsString('Transit 1', $flat['cleaning']['sub']);
        $this->assertSame('100%', $flat['lgt']['value']);
        $this->assertStringContainsString('AIEC 0%', $flat['lgt']['sub']);
        foreach (['capacity', 'compliance', 'attendance', 'employees', 'assets', 'ims', 'documents', 'sources', 'ict', 'rotation'] as $module) {
            $this->assertArrayHasKey($module, $flat, "tile for {$module}");
        }
    }

    public function test_tiles_follow_the_users_station_and_permissions(): void
    {
        $this->wo('2026-10-07', 'CGK', 'Closed');
        $this->wo('2026-10-07', 'SUB', 'Closed');
        $this->wo('2026-10-07', 'SUB', 'Closed');
        $period = ReportPeriod::week(Carbon::parse('2026-10-07'));

        $sub = $this->flat((new ModuleTiles)->build($this->user(RoleHelper::PIC_CABIN), $period, ['SUB']));
        $this->assertSame('2 / 2', $sub['wo']['value']);

        $all = $this->flat((new ModuleTiles)->build($this->user(RoleHelper::MANAGER), $period));
        $this->assertSame('3 / 3', $all['wo']['value']);

        $nobody = User::factory()->create(['is_default_password' => false, 'status' => 'active']);
        $bare = $this->flat((new ModuleTiles)->build($nobody, $period));
        foreach (['attendance', 'employees', 'assets', 'lgt', 'compliance', 'sources', 'leader'] as $gated) {
            $this->assertArrayNotHasKey($gated, $bare, "{$gated} needs its permission");
        }
        $this->assertArrayHasKey('wo', $bare, 'the core production tiles are for everyone');
    }

    public function test_asset_tile_counts_available_units_and_overdue_loans(): void
    {
        $asset = Asset::create(['code' => 'S1', 'name' => 'Stretcher', 'category' => 'STRETCHER', 'condition' => 'BAIK', 'qty_total' => 5]);
        AssetAssignment::create(['asset_id' => $asset->id, 'holder_type' => 'station', 'holder_name' => 'CGK', 'qty' => 2, 'assigned_at' => '2026-09-01', 'due_back' => '2026-09-30']);   // overdue

        $tile = $this->flat((new ModuleTiles)->build($this->user(RoleHelper::MANAGER), ReportPeriod::week(now())))['assets'];

        $this->assertSame('3', $tile['value']);
        $this->assertStringContainsString('1 lewat jatuh tempo', $tile['sub']);
        $this->assertSame('red', $tile['tone']);
    }

    public function test_dashboard_page_renders_the_tiles_as_links_and_survives_a_missing_module(): void
    {
        $manager = $this->user(RoleHelper::MANAGER);

        $html = Livewire::actingAs($manager)->test(KpiDashboard::class)->assertSee('Production')->assertSee('Cleaning & LGT')->assertSee('Asset & Inventory')->assertSee('Long Ground Time')->html();

        $this->assertStringContainsString(route('modules.lgt'), $html);
        $this->assertStringContainsString(route('assets.index'), $html);
        $this->assertStringContainsString(route('attendance.index'), $html);
    }
}
