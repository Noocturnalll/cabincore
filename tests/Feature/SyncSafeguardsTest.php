<?php

namespace Tests\Feature;

use App\Models\DailyJobAssignment;
use App\Models\SheetSyncLog;
use App\Services\Dja\UnplannedSheetPusher;
use App\Services\GoogleSheetsReader;
use App\Services\GoogleSheetsSyncService;
use App\Services\Leader\UnplannedSheetWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SyncSafeguardsTest extends TestCase
{
    use RefreshDatabase;

    private function tasks(int $n, string $sheet = 'sheet-1'): void
    {
        for ($i = 1; $i <= $n; $i++) {
            DailyJobAssignment::create([
                'task_id' => "T{$i}", 'date' => '2026-10-13', 'job_type' => 'R01/WO', 'aircraft_registration' => 'PK-A',
                'description' => 'x', 'station' => 'CGK', 'source_spreadsheet_id' => $sheet,
            ]);
        }
    }

    public function test_a_read_that_lost_most_tasks_does_not_delete_anything(): void
    {
        $this->tasks(20);
        $service = app(GoogleSheetsSyncService::class);

        // empty read: every task would look deleted
        $this->assertFalse($service->ghostRemovalLooksSafe('sheet-1', []));
        $this->assertSame(1, SheetSyncLog::where('event', 'failed')->count());

        // a normal day: two of twenty tasks were really removed by the planner
        $pulled = array_map(fn ($i) => "T{$i}", range(1, 18));
        $this->assertTrue($service->ghostRemovalLooksSafe('sheet-1', $pulled));
    }

    public function test_a_small_sheet_is_not_judged(): void
    {
        $this->tasks(5);

        $this->assertTrue(app(GoogleSheetsSyncService::class)->ghostRemovalLooksSafe('sheet-1', []));
    }

    public function test_a_renamed_header_is_reported_in_the_sync_log(): void
    {
        $service = new class(app(GoogleSheetsReader::class)) extends GoogleSheetsSyncService
        {
            public function check(string $tab, array $map): void
            {
                $this->warnAboutMissingColumns($tab, $map);
            }
        };

        $service->check('DJA', ['WG' => 0, 'DESCRIPTION' => 1, 'REG PESAWAT' => 2, 'NOMOR' => 3, 'KETERANGAN' => 13]);   // AC REG, WO and STATUS columns renamed

        $entry = SheetSyncLog::where('event', 'failed')->first();
        $this->assertNotNull($entry);
        $this->assertStringContainsString('AC REG', $entry->message);
    }

    public function test_a_write_that_keeps_failing_for_a_day_is_abandoned_not_looped(): void
    {
        SheetSyncLog::create(['direction' => 'push', 'event' => 'failed', 'kind' => 'wo', 'doc_no' => 'W1', 'payload' => ['doc_no' => 'W1'], 'attempts' => 288]);

        $writer = Mockery::mock(UnplannedSheetWriter::class);
        $writer->shouldReceive('upsert')->never();
        (new UnplannedSheetPusher($writer))->retryFailed();

        $this->assertSame('abandoned', SheetSyncLog::first()->event);
    }
}
