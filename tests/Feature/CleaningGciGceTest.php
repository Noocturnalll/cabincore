<?php

namespace Tests\Feature;

use App\Models\AircraftCleaning;
use App\Services\Aiec\StationCapabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CleaningGciGceTest extends TestCase
{
    use RefreshDatabase;

    private function job(string $type, string $reg): void
    {
        AircraftCleaning::create(['date' => '2026-10-12', 'station' => 'CGK', 'type' => $type, 'shift' => 'PAGI', 'aircraft_registration' => $reg, 'ref_no' => $reg]);
    }

    public function test_gci_and_gce_have_their_own_columns_and_count_in_the_totals(): void
    {
        $this->job('GCI', 'PK-A');
        $this->job('GCE', 'PK-B');
        $this->job('GCE', 'PK-C');
        $this->job('General', 'PK-D');

        $pivot = StationCapabilityService::getStationTypePivot('2026-10');

        $this->assertContains('GCI', $pivot['types']);
        $this->assertContains('GCE', $pivot['types']);
        $this->assertSame(1, $pivot['matrix']['CGK']['GCI']);
        $this->assertSame(2, $pivot['matrix']['CGK']['GCE']);
        $this->assertSame(4, $pivot['grand_total'], 'GCI and GCE used to be dropped from the totals');
    }
}
