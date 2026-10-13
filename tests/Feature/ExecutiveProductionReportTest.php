<?php

namespace Tests\Feature;

use App\Helpers\RoleHelper;
use App\Models\AircraftCleaning;
use App\Models\CmlLog;
use App\Models\NsrdiLog;
use App\Models\User;
use App\Services\Reports\ProductionExecutiveService;
use Database\Seeders\RegistryPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExecutiveProductionReportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RegistryPermissionSeeder::class);

        $this->admin = User::factory()->create([
            'status' => 'active',
            'is_default_password' => false,
        ]);
        $this->admin->assignRole(RoleHelper::SUPER_ADMIN);
    }

    public function test_executive_production_report_page_renders_successfully(): void
    {
        $this->actingAs($this->admin)
            ->get(route('reports.executive'))
            ->assertOk()
            ->assertSee('Executive Production Meeting Dashboard')
            ->assertSee('3 Pilar Bank Data Terpadu')
            ->assertSee('Pilar 1: Cabin Maintenance')
            ->assertSee('Pilar 2: Aircraft Painting')
            ->assertSee('Pilar 3: Aircraft Cleaning (AIEC)');
    }

    public function test_production_executive_service_calculates_all_pillars(): void
    {
        NsrdiLog::create([
            'aircraft_registration' => 'PK-LNF',
            'nsrdi_number' => 'NSRDI-TEST-001',
            'plan_date' => now()->toDateString(),
            'category' => 'CBM',
            'plan_station' => 'CGK',
            'act_station' => 'CGK',
            'aoc' => 'JT',
            'status' => 'Closed',
            'description' => 'Tray table latch broken',
            'is_submitted' => true,
        ]);

        NsrdiLog::create([
            'aircraft_registration' => 'PK-LPA',
            'nsrdi_number' => 'NSRDI-PAINT-001',
            'plan_date' => now()->toDateString(),
            'category' => 'PAINTING',
            'plan_station' => 'CGK',
            'act_station' => 'CGK',
            'aoc' => 'ID',
            'status' => 'Closed',
            'description' => 'Repainting section 41 due to PPO',
            'is_submitted' => true,
        ]);

        CmlLog::create([
            'aircraft_registration' => 'PK-LNF',
            'station' => 'CGK',
            'date' => now()->toDateString(),
            'operator' => 'LION AIR',
            'status' => 'Closed',
            'description' => 'Recline wire broken at seat 14A',
        ]);

        AircraftCleaning::create([
            'aircraft_registration' => 'PK-LNF',
            'aircraft_type' => 'B737-800NG',
            'date' => now()->toDateString(),
            'station' => 'CGK',
            'shift' => 'Pagi',
            'type' => 'DCI',
            'status' => 'Closed',
            'operator' => 'LION AIR',
            'man_power' => 6,
            'man_hour' => 1.5,
        ]);

        $service = app(ProductionExecutiveService::class);
        $report = $service->getProductionReport(now()->toDateString(), now()->toDateString());

        $this->assertGreaterThanOrEqual(1, $report['kpis']['cbm']['total']);
        $this->assertGreaterThanOrEqual(1, $report['kpis']['painting']['total']);
        $this->assertGreaterThanOrEqual(1, $report['kpis']['cleaning']['total']);
        $this->assertArrayHasKey('cbm_stations', $report['matrices']);
        $this->assertArrayHasKey('painting_stations', $report['matrices']);
        $this->assertArrayHasKey('top_defects', $report['pareto']);
    }

    public function test_cml_index_page_renders_with_pivot_tab(): void
    {
        CmlLog::create([
            'aircraft_registration' => 'PK-BGF',
            'station' => 'SUB',
            'date' => now()->toDateString(),
            'operator' => 'BATIK AIR',
            'status' => 'Open',
            'description' => 'Lavatory sink faucet loose',
        ]);

        $this->actingAs($this->admin)
            ->get(route('modules.cml', ['tab' => 'pivot']))
            ->assertOk()
            ->assertSee('CML &amp; Pivot Defect Kabin', false)
            ->assertSee('Pivot &amp; Rekapitulasi Defect Kabin', false);
    }
}
