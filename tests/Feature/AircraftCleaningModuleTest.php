<?php

namespace Tests\Feature;

use App\Helpers\RoleHelper;
use App\Models\AircraftCleaning;
use App\Models\User;
use App\Services\Aiec\StationCapabilityService;
use Database\Seeders\RegistryPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AircraftCleaningModuleTest extends TestCase
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

    public function test_daily_report_page_opens_and_renders_all_tabs(): void
    {
        $this->actingAs($this->admin)
            ->get(route('modules.cleaning.daily-report'))
            ->assertOk()
            ->assertSee('AIEC Report &amp; Capacity Hub', false)
            ->assertSee('Log Aktivitas Pembersihan')
            ->assertSee('Kapasitas &amp; Kapabilitas Stasiun (DCIE)', false)
            ->assertSee('Pivot &amp; Rekapitulasi Matriks', false)
            ->assertSee('Monitoring Overdue Batik OD (9M-)', false);
    }

    public function test_station_capability_service_returns_matrix_and_metrics(): void
    {
        $caps = StationCapabilityService::getStationCapabilities();
        $this->assertArrayHasKey('CGK', $caps);
        $this->assertArrayHasKey('SUB', $caps);
        $this->assertArrayHasKey('UPG', $caps);
        $this->assertSame(4, $caps['CGK']['dci_cap']);
        $this->assertSame(2, $caps['CGK']['dce_cap']);
        $this->assertSame(2, $caps['SUB']['dce_cap']);

        $metrics = StationCapabilityService::getStationMetrics();
        $this->assertGreaterThan(5, $metrics->count());
    }

    public function test_pivots_and_monthly_trends_generate_clean_data(): void
    {
        AircraftCleaning::create([
            'aircraft_registration' => 'PK-BGF',
            'aircraft_type' => 'B737-800NG',
            'date' => '2026-10-02',
            'station' => 'CGK',
            'shift' => 'Pagi',
            'type' => 'Transit',
            'status' => 'Closed',
            'operator' => 'BATIK AIR',
            'man_power' => 4,
            'man_hour' => 0.5,
        ]);

        $typePivot = StationCapabilityService::getStationTypePivot();
        $this->assertArrayHasKey('matrix', $typePivot);
        $this->assertArrayHasKey('CGK', $typePivot['matrix']);
        $this->assertSame(1, $typePivot['matrix']['CGK']['Transit']);

        $shiftPivot = StationCapabilityService::getStationShiftPivot();
        $this->assertArrayHasKey('matrix', $shiftPivot);
        $this->assertArrayHasKey('CGK', $shiftPivot['matrix']);
        $this->assertSame(1, $shiftPivot['matrix']['CGK']['Pagi']);

        $trends = StationCapabilityService::getMonthlyTrendPivot();
        $this->assertCount(5, $trends);
    }

    public function test_batik_od_overdue_monitoring_identifies_jobs(): void
    {
        $overdue = StationCapabilityService::getBatikOdOverdueMonitoring();
        $this->assertNotEmpty($overdue);
        $this->assertTrue(collect($overdue)->pluck('reg')->contains('9M-LRP') || collect($overdue)->pluck('reg')->contains('9M-LDJ'));
    }
}
