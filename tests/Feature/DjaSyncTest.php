<?php

namespace Tests\Feature;

use App\Livewire\Modules\DailyJobAssigment\Index;
use App\Models\DailyJobAssignment;
use App\Models\SyncSetting;
use App\Models\User;
use App\Models\WoLog;
use App\Services\GoogleSheetsReader;
use Google\Service\Sheets;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class DjaSyncTest extends TestCase
{
    use RefreshDatabase;

    private const MondaySheet = '1MondaySheetAbCdEfGhIjKlMnOpQrStUv';

    private const TuesdaySheet = '1TuesdaySheetAbCdEfGhIjKlMnOpQrStU';

    private const Header = ['NO', 'DATE', 'AC REG', 'TASK ID', 'CATEGORY', 'DESCRIPTION', 'ATA'];

    /**
     * @param  array<string, array<string, array<int, array<int, mixed>>>>  $sheets  spreadsheetId => tab => rows
     */
    private function fakeSheets(array $sheets): void
    {
        $reader = Mockery::mock(GoogleSheetsReader::class)->makePartial();
        $reader->shouldReceive('isReady')->andReturn(true);
        $reader->shouldReceive('service')->andReturn(Mockery::mock(Sheets::class));
        $reader->shouldReceive('tabTitles')->andReturnUsing(fn (string $id) => array_keys($sheets[$id] ?? []));
        $reader->shouldReceive('values')->andReturnUsing(fn (string $id, string $tab) => $sheets[$id][$tab] ?? []);

        $this->app->instance(GoogleSheetsReader::class, $reader);
    }

    public function test_livewire_sync_sets_daily_sheet_and_scheduler_follows_the_new_sheet(): void
    {
        $this->fakeSheets([
            self::MondaySheet => ['DJA' => [self::Header, ['1', '', 'PK-GQA', 'WO-001', 'CABIN', 'REPLACE SEAT COVER', '25-21']]],
            self::TuesdaySheet => ['DJA' => [self::Header, ['1', '', 'PK-GQB', 'WO-002', 'CABIN', 'CHECK LIFE VEST', '25-60']]],
        ]);
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('sheetUrl', 'https://docs.google.com/spreadsheets/d/'.self::MondaySheet.'/edit')
            ->call('syncNow');

        $this->assertSame(self::MondaySheet, SyncSetting::spreadsheetIdFor(SyncSetting::Dja));
        $this->assertTrue(DailyJobAssignment::where('task_id', 'WO-001')->exists());
        $this->assertSame(1, WoLog::count());

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('sheetUrl', 'https://docs.google.com/spreadsheets/d/'.self::TuesdaySheet.'/edit')
            ->call('syncNow');

        $this->artisan('sync:daily-dja')->assertSuccessful();

        $this->assertSame(self::TuesdaySheet, SyncSetting::spreadsheetIdFor(SyncSetting::Dja));
        $this->assertTrue(DailyJobAssignment::where('task_id', 'WO-002')->where('source_spreadsheet_id', self::TuesdaySheet)->exists());
        $this->assertTrue(DailyJobAssignment::where('task_id', 'WO-001')->exists(), 'Yesterday tasks must not be removed by a new day sheet.');
        $this->assertSame('success', SyncSetting::for(SyncSetting::Dja)->last_status);
    }

    public function test_command_accepts_new_sheet_option(): void
    {
        $this->fakeSheets([
            self::TuesdaySheet => ['DJA' => [self::Header, ['1', '', 'PK-GQB', 'WO-002', 'CABIN', 'CHECK LIFE VEST', '25-60']]],
        ]);

        $this->artisan('sync:daily-dja', ['--sheet' => 'https://docs.google.com/spreadsheets/d/'.self::TuesdaySheet.'/edit'])
            ->assertSuccessful();

        $this->assertSame(self::TuesdaySheet, SyncSetting::spreadsheetIdFor(SyncSetting::Dja));
        $this->assertSame(1, DailyJobAssignment::count());
    }

    public function test_task_removed_from_sheet_is_soft_deleted_and_log_becomes_unplanned(): void
    {
        $this->fakeSheets([
            self::MondaySheet => ['DJA' => [
                self::Header,
                ['1', '', 'PK-GQA', 'WO-001', 'CABIN', 'REPLACE SEAT COVER', '25-21'],
                ['2', '', 'PK-GQA', 'WO-003', 'CABIN', 'OXYGEN MASK CHECK', '35-10'],
            ]],
        ]);
        $this->artisan('sync:daily-dja', ['--sheet' => self::MondaySheet])->assertSuccessful();
        $this->assertSame(2, DailyJobAssignment::count());

        $this->fakeSheets([
            self::MondaySheet => ['DJA' => [self::Header, ['1', '', 'PK-GQA', 'WO-001', 'CABIN', 'REPLACE SEAT COVER', '25-21']]],
        ]);
        $this->artisan('sync:daily-dja')->assertSuccessful();

        $this->assertSame(1, DailyJobAssignment::count());
        $this->assertSoftDeleted('daily_job_assignments', ['task_id' => 'WO-003']);
        $this->assertSame(1, WoLog::whereNull('dja_id')->count());
    }

    public function test_unreadable_sheet_never_deletes_existing_tasks(): void
    {
        $this->fakeSheets([
            self::MondaySheet => ['DJA' => [self::Header, ['1', '', 'PK-GQA', 'WO-001', 'CABIN', 'REPLACE SEAT COVER', '25-21']]],
        ]);
        $this->artisan('sync:daily-dja', ['--sheet' => self::MondaySheet])->assertSuccessful();

        $reader = Mockery::mock(GoogleSheetsReader::class)->makePartial();
        $reader->shouldReceive('isReady')->andReturn(true);
        $reader->shouldReceive('service')->andReturn(Mockery::mock(Sheets::class));
        $reader->shouldReceive('tabTitles')->andThrow(new \RuntimeException('Network error'));
        $this->app->instance(GoogleSheetsReader::class, $reader);

        $this->artisan('sync:daily-dja')->assertFailed();

        $this->assertSame(1, DailyJobAssignment::count());
        $this->assertSame('failed', SyncSetting::for(SyncSetting::Dja)->last_status);
    }

    public function test_spreadsheet_id_is_extracted_from_url_or_raw_id(): void
    {
        $this->assertSame(self::MondaySheet, SyncSetting::extractSpreadsheetId('https://docs.google.com/spreadsheets/d/'.self::MondaySheet.'/edit#gid=12'));
        $this->assertSame(self::MondaySheet, SyncSetting::extractSpreadsheetId(self::MondaySheet));
        $this->assertNull(SyncSetting::extractSpreadsheetId('not a sheet'));
    }
}
