<?php

namespace Tests\Feature;

use App\Livewire\Modules\AcMovement\Index;
use App\Models\AcRon;
use App\Models\AcStandby;
use App\Models\SyncSetting;
use App\Models\TerminalMovement;
use App\Models\User;
use App\Services\GoogleSheetsReader;
use Google\Service\Sheets;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class AcMovementSyncTest extends TestCase
{
    use RefreshDatabase;

    private const SheetId = '1AbCdEfGhIjKlMnOpQrStUvWxYz0123456789';

    /**
     * @param  array<string, array<int, array<int, mixed>>>  $tabs
     */
    private function fakeSheet(array $tabs): void
    {
        $reader = Mockery::mock(GoogleSheetsReader::class)->makePartial();
        $reader->shouldReceive('isReady')->andReturn(true);
        $reader->shouldReceive('service')->andReturn(Mockery::mock(Sheets::class));
        $reader->shouldReceive('tabTitles')->andReturn(array_keys($tabs));
        $reader->shouldReceive('values')->andReturnUsing(fn (string $id, string $tab) => $tabs[$tab] ?? []);

        $this->app->instance(GoogleSheetsReader::class, $reader);
    }

    public function test_command_syncs_all_tabs_from_saved_sheet_with_title_rows_and_merged_dates(): void
    {
        SyncSetting::saveSpreadsheetId(SyncSetting::AcMovement, self::SheetId);

        $this->fakeSheet([
            'Terminal 1' => [
                ['AIRCRAFT MOVEMENT TERMINAL 1'],
                [],
                ['DATE', 'NO', 'REGISTRASI', 'FLIGHT NO (IN)', 'STA', 'ETA', 'PLAN PS', 'FLIGHT NO (OUT)', 'STD', 'ATD'],
                ['03/10/2026', '1', 'PK-GQA', 'QG100', '07:00', '07:05', 'P1', 'QG101', '08:00', '08:10'],
                ['', '2', 'PK-GQB', 'QG200', '09:00', '', 'P2', 'QG201', '10:00', ''],
                ['', '', '', '', '', '', '', '', '', ''],
            ],
            'TERMINAL 2' => [
                ['DATE', 'NO', 'REGISTRASI', 'FLIGHT NO (IN)', 'STA'],
                ['03/10/2026', '1', 'PK-GFA', 'GA150', '11:00'],
            ],
            'AC RON' => [
                ['TGL', 'NO', 'REG FLT', 'EX FLT', 'STA/ATA', 'STAND', 'FLT NO', 'ROUTE', 'STD', 'REMARKS', 'NOTE'],
                ['03 Oct 2026', '1', 'PK-GQC', 'QG300', '21:00', 'S5', 'QG301', 'BTH-CGK', '06:00', 'RON', ''],
            ],
            'AC STBY' => [
                ['NO', 'REG FLT', 'PARKING', 'PLAN RTS', 'REMARKS'],
                ['CITILINK'],
                ['1', 'PK-GQD', 'P7', '12:00', 'AOG'],
            ],
        ]);

        $this->artisan('sync:ac-movement')->assertSuccessful();

        $this->assertSame(2, TerminalMovement::where('terminal_name', 'TERMINAL 1')->count());
        $this->assertSame(1, TerminalMovement::where('terminal_name', 'TERMINAL 2')->count());

        $second = TerminalMovement::where('registration', 'PK-GQB')->first();
        $this->assertSame('2026-10-03', substr((string) $second->flight_date, 0, 10));
        $this->assertSame(2, (int) $second->no_seq);

        $ron = AcRon::first();
        $this->assertSame('PK-GQC', $ron->reg_flt);
        $this->assertSame('2026-10-03', substr((string) $ron->ron_date, 0, 10));

        $standby = AcStandby::first();
        $this->assertSame('CITILINK', $standby->airline_category);
        $this->assertSame('P7', $standby->parking);

        $setting = SyncSetting::for(SyncSetting::AcMovement);
        $this->assertSame('success', $setting->last_status);
        $this->assertNotNull($setting->last_synced_at);
    }

    public function test_failed_spreadsheet_access_keeps_existing_data(): void
    {
        SyncSetting::saveSpreadsheetId(SyncSetting::AcMovement, self::SheetId);
        AcRon::create(['reg_flt' => 'PK-OLD']);

        $reader = Mockery::mock(GoogleSheetsReader::class)->makePartial();
        $reader->shouldReceive('isReady')->andReturn(true);
        $reader->shouldReceive('tabTitles')->andThrow(new \RuntimeException('The caller does not have permission'));
        $this->app->instance(GoogleSheetsReader::class, $reader);

        $this->artisan('sync:ac-movement')->assertFailed();

        $this->assertSame(1, AcRon::count());
        $this->assertSame('failed', SyncSetting::for(SyncSetting::AcMovement)->last_status);
    }

    public function test_command_without_saved_sheet_does_nothing(): void
    {
        $this->artisan('sync:ac-movement')->assertSuccessful();

        $this->assertSame(0, TerminalMovement::count());
    }

    public function test_livewire_saves_new_sheet_id_and_prefills_it_next_time(): void
    {
        $this->fakeSheet(['AC RON' => [['TGL', 'NO', 'REG FLT'], ['03/10/2026', '1', 'PK-GQE']]]);
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('sheetUrl', 'https://docs.google.com/spreadsheets/d/'.self::SheetId.'/edit#gid=0')
            ->call('syncNow');

        $this->assertSame(self::SheetId, SyncSetting::spreadsheetIdFor(SyncSetting::AcMovement));
        $this->assertSame(1, AcRon::count());

        Livewire::actingAs($user)
            ->test(Index::class)
            ->assertSet('sheetUrl', 'https://docs.google.com/spreadsheets/d/'.self::SheetId.'/edit');

        $newId = '1ZyXwVuTsRqPoNmLkJiHgFeDcBa9876543210';
        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('sheetUrl', $newId)
            ->call('syncNow');

        $this->assertSame($newId, SyncSetting::spreadsheetIdFor(SyncSetting::AcMovement));
    }
}
