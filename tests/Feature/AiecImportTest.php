<?php

namespace Tests\Feature;

use App\Models\AircraftCleaning;
use App\Services\Aiec\AiecReportImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class AiecImportTest extends TestCase
{
    use RefreshDatabase;

    private const HEADER = ['DATE', 'OPERATOR', 'A/C TYPE', 'A/C REG', 'A/C Status', 'STA', 'SHIFT', 'TASK TYPE', 'DOC TYPE', 'STATUS', 'NO. DOC', 'ACTION TAKEN / REASON', 'START PERFORM', 'FINISH PERFORM',
        'MP 1', 'MP 2', 'MP 3', 'MP 4', 'MP 5', 'MP 6', 'MP 7', 'MP 8', 'MP 9', 'MP 10', 'MP 11', 'MP 12', 'TOTAL', 'DUR', 'MH'];

    private function workbook(array $jobs, string $sheet = 'SEP'): string
    {
        $book = new Spreadsheet;
        $ws = $book->getActiveSheet();
        $ws->setTitle($sheet);
        $ws->fromArray(['AIEC REPORT'], null, 'A1');
        $ws->fromArray(self::HEADER, null, 'A2');
        foreach ($jobs as $i => $job) {
            $ws->fromArray($job, null, 'A'.($i + 3));
        }
        $other = $book->createSheet();
        $other->setTitle('DASHBOARD DCIE');
        $other->setCellValue('A1', 'ignored');

        $path = tempnam(sys_get_temp_dir(), 'ai').'.xlsx';
        (new Xlsx($book))->save($path);

        return $path;
    }

    /** a job row: date serial 46266 = 2026-09-01 */
    private function job(string $reg, string $action, float $start, float $finish, array $people, string $shift = 'PAGI', string $sta = 'MDC'): array
    {
        $mp = array_pad($people, 12, null);

        return array_merge([46266, null, null, $reg, 'TRANSIT', $sta, $shift, 'UNSCHEDULE', 'N/A', 'CLOSED', 'ID-1', $action, $start, $finish], $mp);
    }

    public function test_man_hours_are_people_times_duration_with_overnight_rollover(): void
    {
        $path = $this->workbook([
            $this->job('PK-LBS', 'TRANSIT', 0.25, 0.2916666667, [250409, 83117667, '83117673'], 'PAGI'),     // 3 people x 1h
            $this->job('PK-WJY', 'GENERAL EXTERIOR', 0.9166666667, 0.0416666667, ['A', 'B'], 'MALAM'),        // 2 people x 3h, past midnight
            $this->job('PK-AAA', 'DCI', 0.5, 0.5416666667, []),                                              // nobody listed
            $this->job('PK-BBB', 'DCE', 12, 0.5, ['X']),                                                     // "12" typed instead of a time
            $this->job('PK-CCC', 'PROJECT OVEN', 0.5, 0.6, ['X']),                                           // not a cleaning job
        ]);

        $stats = (new AiecReportImporter)->import($path);

        $this->assertSame(4, $stats['saved']);
        $this->assertSame(['SEP' => 4], $stats['sheets']);
        $this->assertSame(['PROJECT OVEN' => 1], $stats['skipped']);

        $transit = AircraftCleaning::where('aircraft_registration', 'PK-LBS')->first();
        $this->assertSame('Transit', $transit->type);
        $this->assertSame(3, $transit->man_power);
        $this->assertEquals(3.0, $transit->man_hour);
        $this->assertSame('250409, 83117667, 83117673', $transit->mp_ids);
        $this->assertSame('MDC', $transit->station);
        $this->assertSame('Pagi', $transit->shift);
        $this->assertSame('TRANSIT', $transit->ac_status);

        $night = AircraftCleaning::where('aircraft_registration', 'PK-WJY')->first();
        $this->assertSame('GCE', $night->type, 'GENERAL EXTERIOR is GCE');
        $this->assertEquals(6.0, $night->man_hour);
        $this->assertSame('2026-09-02 01:00:00', (string) $night->end_at);

        $this->assertNull(AircraftCleaning::where('aircraft_registration', 'PK-AAA')->value('man_hour'), 'no crew listed: no man hours invented');
        $bad = AircraftCleaning::where('aircraft_registration', 'PK-BBB')->first();
        $this->assertNull($bad->man_hour, 'a whole-number "time" is a typo, not a 12 hour job');
        $this->assertNull($bad->start_at);
    }

    public function test_reimport_replaces_only_imported_rows_in_the_file_dates(): void
    {
        AircraftCleaning::create(['aircraft_registration' => 'PK-HAND', 'date' => '2026-09-01', 'shift' => 'Pagi', 'type' => 'General', 'status' => 'Closed', 'station' => 'CGK']);   // typed by hand
        $path = $this->workbook([$this->job('PK-LBS', 'TRANSIT', 0.25, 0.3, ['A'])]);

        $importer = new AiecReportImporter;
        $importer->import($path);
        $importer->import($path);

        $this->assertSame(1, AircraftCleaning::where('import_source', 'aiec_report')->count(), 'a re-import does not duplicate');
        $this->assertSame(1, AircraftCleaning::where('aircraft_registration', 'PK-HAND')->count(), 'records typed in by hand are never touched');
    }

    public function test_dry_run_changes_nothing(): void
    {
        $stats = (new AiecReportImporter)->import($this->workbook([$this->job('PK-LBS', 'TRANSIT', 0.25, 0.3, ['A'])]), true);

        $this->assertSame(1, $stats['saved']);
        $this->assertSame(0, AircraftCleaning::count());
    }
}
