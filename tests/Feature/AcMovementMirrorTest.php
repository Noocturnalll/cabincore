<?php

namespace Tests\Feature;

use App\Helpers\RoleHelper;
use App\Livewire\Modules\AcMovement\Index;
use App\Models\AcRon;
use App\Models\SyncSetting;
use App\Models\TerminalMovement;
use App\Models\User;
use App\Services\GoogleSheetsReader;
use Google\Service\Sheets;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** The web app must always mirror the AC Movement sheet: edits, additions and removals included. */
class AcMovementMirrorTest extends TestCase
{
    use RefreshDatabase;

    private const SHEET = '1MirrorSheetAbCdEfGhIjKlMnOpQrStUvWx';

    private const T1_HEADER = ['DATE', 'NO', 'REGISTRASI', 'FLIGHT NO (IN)', 'STA', 'ETA', 'PLAN PS', 'FLIGHT NO (OUT)', 'STD', 'ATD'];

    /** @param array<string, array<int, array<int, mixed>>|\Throwable> $tabs */
    private function fakeSheet(array $tabs): void
    {
        $reader = Mockery::mock(GoogleSheetsReader::class)->makePartial();
        $reader->shouldReceive('isReady')->andReturn(true);
        $reader->shouldReceive('service')->andReturn(Mockery::mock(Sheets::class));
        $reader->shouldReceive('tabTitles')->andReturn(array_keys($tabs));
        $reader->shouldReceive('values')->andReturnUsing(function (string $id, string $tab) use ($tabs) {
            if ($tabs[$tab] instanceof \Throwable) {
                throw $tabs[$tab];
            }

            return $tabs[$tab];
        });
        $this->app->instance(GoogleSheetsReader::class, $reader);
    }

    private function sync(): void
    {
        SyncSetting::saveSpreadsheetId(SyncSetting::AcMovement, self::SHEET);
        $this->artisan('sync:ac-movement');
    }

    public function test_every_sheet_edit_addition_and_removal_is_mirrored(): void
    {
        $this->fakeSheet(['TERMINAL 1' => [
            self::T1_HEADER,
            ['08/10/2026', '1', 'PK-AAA', 'QG1', '07:00', '', 'P1', 'QG2', '08:00', ''],
            ['', '2', 'PK-BBB', 'QG3', '09:00', '', 'P2', 'QG4', '10:00', ''],
        ]]);
        $this->sync();
        $this->assertEqualsCanonicalizing(['PK-AAA', 'PK-BBB'], TerminalMovement::pluck('registration')->all());

        // planner edits PK-AAA's ATD, deletes PK-BBB and adds PK-CCC
        $this->fakeSheet(['TERMINAL 1' => [
            self::T1_HEADER,
            ['08/10/2026', '1', 'PK-AAA', 'QG1', '07:00', '07:05', 'P1', 'QG2', '08:00', '08:12'],
            ['', '2', 'PK-CCC', 'QG5', '11:00', '', 'P3', 'QG6', '12:00', ''],
        ]]);
        $this->sync();

        $this->assertEqualsCanonicalizing(['PK-AAA', 'PK-CCC'], TerminalMovement::pluck('registration')->all());
        $this->assertSame('08:12', TerminalMovement::firstWhere('registration', 'PK-AAA')->atd);
        $this->assertSame('2026-10-08', substr((string) TerminalMovement::firstWhere('registration', 'PK-CCC')->flight_date, 0, 10));
    }

    public function test_a_tab_that_is_cleared_but_keeps_its_header_is_emptied(): void
    {
        $this->fakeSheet(['AC RON' => [['TGL', 'NO', 'REG FLT'], ['08/10/2026', '1', 'PK-AAA']]]);
        $this->sync();
        $this->assertSame(1, AcRon::count());

        $this->fakeSheet(['AC RON' => [['TGL', 'NO', 'REG FLT']]]);
        $this->sync();

        $this->assertSame(0, AcRon::count(), 'The planner removed every aircraft: the web app follows.');
    }

    public function test_a_completely_empty_response_does_not_wipe_live_data(): void
    {
        $this->fakeSheet(['AC RON' => [['TGL', 'NO', 'REG FLT'], ['08/10/2026', '1', 'PK-AAA']]]);
        $this->sync();

        $this->fakeSheet(['AC RON' => []]); // API hiccup: no header, no rows
        $this->sync();

        $this->assertSame(1, AcRon::count());
        $this->assertStringContainsString('data lama dipertahankan', SyncSetting::for(SyncSetting::AcMovement)->last_message);
    }

    public function test_one_broken_tab_is_reported_as_failed_but_the_other_tabs_still_update(): void
    {
        $admin = $this->admin();
        $this->fakeSheet([
            'AC RON' => [['TGL', 'NO', 'REG FLT'], ['08/10/2026', '1', 'PK-NEW']],
            'AC STBY' => new \RuntimeException('Quota exceeded'),
        ]);

        $this->sync();

        $this->assertSame(['PK-NEW'], AcRon::pluck('reg_flt')->all());
        $setting = SyncSetting::for(SyncSetting::AcMovement);
        $this->assertSame('failed', $setting->last_status);
        $this->assertStringContainsString('AC STBY', $setting->last_message);
        $this->assertSame(1, $admin->fresh()->notifications()->count());

        $this->sync(); // still failing: no second alert
        $this->assertSame(1, $admin->fresh()->notifications()->count());
    }

    public function test_the_page_warns_when_the_mirror_is_stale_or_failed(): void
    {
        $user = $this->admin();
        SyncSetting::for(SyncSetting::AcMovement)->update(['spreadsheet_id' => self::SHEET, 'last_synced_at' => now()->subMinutes(30), 'last_status' => 'success']);
        $this->assertStringContainsString('belum diperbarui 30 menit', Livewire::actingAs($user)->test(Index::class)->html());

        SyncSetting::for(SyncSetting::AcMovement)->update(['last_synced_at' => now()->subMinutes(2)]);
        $this->assertStringNotContainsString('belum diperbarui', Livewire::actingAs($user)->test(Index::class)->html());

        SyncSetting::for(SyncSetting::AcMovement)->update(['last_status' => 'failed', 'last_message' => 'Quota exceeded']);
        $html = Livewire::actingAs($user)->test(Index::class)->html();
        $this->assertStringContainsString('Sync terakhir gagal', $html);
        $this->assertStringContainsString('Quota exceeded', $html);
    }

    private function admin(): User
    {
        Role::findOrCreate(RoleHelper::SUPER_ADMIN, 'web');
        $user = User::factory()->create(['is_default_password' => false, 'status' => 'active']);
        $user->assignRole(RoleHelper::SUPER_ADMIN);

        return $user;
    }
}
