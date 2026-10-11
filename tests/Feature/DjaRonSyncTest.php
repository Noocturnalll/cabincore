<?php

namespace Tests\Feature;

use App\Models\CapacityReport;
use App\Models\CapacityStation;
use App\Services\Dja\DjaPersister;
use App\Services\Dja\DjaRonSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DjaRonSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_parses_list_ac_sheet_and_updates_capacity_station_and_report(): void
    {
        // Setup initial stations
        CapacityStation::create([
            'order_no' => 1,
            'kh_region' => 'KH-1',
            'group_type' => 'A',
            'station_code' => 'CGK',
            'ron_jt' => 0,
            'ron_iw' => 0,
            'ron_id' => 0,
            'ron_iu' => 0,
            'ron_sl' => 0,
            'ron_od' => 0,
        ]);

        CapacityStation::create([
            'order_no' => 2,
            'kh_region' => 'KH-5',
            'group_type' => 'A',
            'station_code' => 'UPG',
            'ron_jt' => 0,
            'ron_iw' => 0,
            'ron_id' => 0,
            'ron_iu' => 0,
            'ron_sl' => 0,
            'ron_od' => 0,
        ]);

        // Mock 2D array representation of Sheet 1 (List AC)
        // Row 0: TRUE, TRUE
        // Row 1: CGK, UPG
        // Row 2: 3, 2 (totals)
        // Row 3: PK-BDF (Batik ID), PK-WJJ (Wings IW)
        // Row 4: PK-LAL (Lion JT),  PK-LFK (Lion JT)
        // Row 5: PK-SJR (Super IU), ''
        $sheetData = [
            ['TRUE', 'TRUE'],
            ['CGK', 'UPG'],
            ['3', '2'],
            ['PK-BDF', 'PK-WJJ'],
            ['PK-LAL', 'PK-LFK'],
            ['PK-SJR', ''],
        ];

        $service = app(DjaRonSyncService::class);
        $result = $service->syncFromArray($sheetData, 'List AC');

        $this->assertSame(2, $result['total_stations']);
        $this->assertSame(5, $result['total_aircraft']);

        // Verify CGK: 1 ID, 1 JT, 1 IU
        $cgk = CapacityStation::where('station_code', 'CGK')->firstOrFail();
        $this->assertSame(1, $cgk->ron_id);
        $this->assertSame(1, $cgk->ron_jt);
        $this->assertSame(1, $cgk->ron_iu);
        $this->assertSame(0, $cgk->ron_iw);

        // Verify UPG: 1 IW, 1 JT
        $upg = CapacityStation::where('station_code', 'UPG')->firstOrFail();
        $this->assertSame(1, $upg->ron_iw);
        $this->assertSame(1, $upg->ron_jt);
        $this->assertSame(0, $upg->ron_id);

        // Verify CapacityReport snapshot was generated for active date
        $activeDate = DjaPersister::activeDate();
        $reportCgk = CapacityReport::whereDate('report_date', $activeDate)->where('station_code', 'CGK')->firstOrFail();
        $this->assertSame(1, $reportCgk->ron_id);
        $this->assertSame(1, $reportCgk->ron_jt);
    }
}
