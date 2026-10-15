<?php

namespace Tests\Feature;

use App\Models\DailyJobAssignment;
use App\Models\DmiLog;
use App\Models\NsrdiLog;
use App\Models\SheetSyncLog;
use App\Models\WoLog;
use App\Services\Dja\UnplannedSheetPuller;
use App\Services\GoogleSheetsReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class UnplannedPullTest extends TestCase
{
    use RefreshDatabase;

    private function reader(array $tabs)
    {
        $r = Mockery::mock(GoogleSheetsReader::class)->makePartial();
        $r->shouldReceive('isReady')->andReturn(true);
        $r->shouldReceive('tabTitles')->andReturn(array_keys($tabs));
        $r->shouldReceive('values')->andReturnUsing(fn ($id, $tab) => $tabs[$tab] ?? []);

        return $r;
    }

    public function test_unplanned_rows_from_the_sheet_become_closed_logs_without_duplicates(): void
    {
        $r = $this->reader([
            'Unplanned WO' => [
                ['REFRESH DATE', 'WG', 'AC REG', 'NO DOC', 'WO DESCRIPTION', 'ACT STA', 'STATUS WO'],
                ['13-Oct-26', 'CBM', 'PK-ABC', 'WO-U1', 'Fix seat', 'cgk', 'CLOSED'],
                ['13-Oct-26', 'CBM', 'PK-ABC', 'WO-U1', 'Fix seat again', 'cgk', 'CLOSED'],
                ['', '', '', '', '', '', ''],
            ],
            'Unplaned DMI' => [
                ['DATE', 'REG', 'DMI NO', 'DMI DESCRIPTION', 'PN REQUIRED', 'ACT STA', 'STATUS DMI', 'STATUS WO'],
                ['13-Oct-26', 'PK-XYZ', 'DMI-U1', 'Torn carpet', 'PN-1', 'sub', '', ''],
            ],
            'Unplaned NSRD' => [
                ['', 'AC REG', 'NSRDI', 'DESCRIPTION', 'STATUS'],
                ['13-Oct-26', 'PK-XYZ', 'NS-U1', 'Panel crack', 'CLOSED'],
            ],
        ]);

        $puller = new UnplannedPullTestPuller($r);
        $first = $puller->pull('sheet');
        $second = $puller->pull('sheet');

        $this->assertSame(3, $first['created']);
        $this->assertSame(0, $second['created']);
        $this->assertSame(1, WoLog::where('wo_number', 'WO-U1')->count());

        $wo = WoLog::where('wo_number', 'WO-U1')->first();
        $this->assertNull($wo->dja_id);
        $this->assertSame('Closed', $wo->status);
        $this->assertSame('CGK', $wo->act_station);

        $dmi = DmiLog::where('dmi_number', 'DMI-U1')->first();
        $this->assertSame('PN-1', $dmi->pn_required);
        $this->assertSame('SUB', $dmi->act_station);
        $this->assertNull($dmi->status_wo);

        $this->assertSame('Closed', NsrdiLog::where('nsrdi_number', 'NS-U1')->value('status'));
    }

    public function test_status_difference_with_planned_document_is_logged_not_duplicated(): void
    {
        $dja = DailyJobAssignment::create(['task_id' => 'WO-P1', 'date' => '2026-10-13', 'job_type' => 'R01/WO', 'aircraft_registration' => 'PK-ABC', 'description' => 'x', 'station' => 'CGK']);
        WoLog::create(['dja_id' => $dja->id, 'wo_number' => 'WO-P1', 'status' => 'Open', 'aircraft_registration' => 'PK-ABC', 'date' => '2026-10-13']);

        $r = $this->reader(['Unplanned WO' => [
            ['REFRESH DATE', 'AC REG', 'NO DOC', 'WO DESCRIPTION', 'STATUS WO'],
            ['13-Oct-26', 'PK-ABC', 'WO-P1', 'dup', 'CLOSED'],
        ]]);
        $stats = (new UnplannedSheetPuller($r))->pull('sheet');

        $this->assertSame(1, WoLog::where('wo_number', 'WO-P1')->count());
        $this->assertSame(1, $stats['conflicts']);
        $this->assertSame(1, SheetSyncLog::where('event', 'conflict')->count());
    }
}

class UnplannedPullTestPuller extends UnplannedSheetPuller {}
