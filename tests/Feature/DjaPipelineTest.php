<?php

namespace Tests\Feature;

use App\Helpers\RoleHelper;
use App\Imports\DjaSheetImport;
use App\Livewire\Modules\DailyJobAssigment\Index as DjaPage;
use App\Livewire\Modules\NsrdiLog\Index as NsrdiPage;
use App\Livewire\Modules\WoLog\Index as WoPage;
use App\Models\DailyJobAssignment;
use App\Models\DjaSyncReview;
use App\Models\NsrdiLog;
use App\Models\SyncSetting;
use App\Models\User;
use App\Models\WoLog;
use App\Services\Dja\DjaPersister;
use App\Services\GoogleSheetsReader;
use App\Services\GoogleSheetsSyncService;
use Google\Service\Sheets;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DjaPipelineTest extends TestCase
{
    use RefreshDatabase;

    private const SHEET = '1PipelineSheetAbCdEfGhIjKlMnOpQrStUv';

    /** Real-looking WO header: NO, WG, AC REG, TASK ID, CATEGORY, DESCRIPTION, ATA, ... PLAN STA at 10, STATUS at 13 */
    private const WO_HEADER = ['NO', 'WG', 'AC REG', 'TASK ID', 'CATEGORY', 'DESCRIPTION', 'ATA', 'MAN HOUR', 'OPERATOR', 'TYPE', 'PLAN STA', 'REMARKS PPC TO LM', 'ACT STA', 'STATUS', 'CODE REASON', 'REASON OPEN'];

    private const NSRDI_HEADER = ['NO', 'WG', 'AC REG', 'TASK ID', 'DESCRIPTION', 'CATEGORY', 'REPORT DATE', 'DUE DATE', 'PART NUMBER', 'PART DESCRIPTION', 'DEFER', 'AOC', 'TYPE', 'PLAN STA', 'REMARKS', 'STATUS', 'CLOSE DATE'];

    private function woRow(string $task, string $desc, string $ata = '', string $station = 'KNO', string $reg = 'PK-GQA'): array
    {
        return ['1', 'CABIN', $reg, $task, 'CBM', $desc, $ata, '2', 'Budi', 'ROUTINE', $station, 'note', '', '', '', ''];
    }

    private function fakeSheets(array $tabs): void
    {
        $reader = Mockery::mock(GoogleSheetsReader::class)->makePartial();
        $reader->shouldReceive('isReady')->andReturn(true);
        $reader->shouldReceive('service')->andReturn(Mockery::mock(Sheets::class));
        $reader->shouldReceive('tabTitles')->andReturn(array_keys($tabs));
        $reader->shouldReceive('values')->andReturnUsing(fn (string $id, string $tab) => $tabs[$tab] ?? []);
        $this->app->instance(GoogleSheetsReader::class, $reader);
    }

    private function sync(): array
    {
        return app(GoogleSheetsSyncService::class)->syncDja(self::SHEET);
    }

    private function admin(): User
    {
        Role::findOrCreate(RoleHelper::SUPER_ADMIN, 'web');
        $user = User::factory()->create(['is_default_password' => false, 'status' => 'active']);
        $user->assignRole(RoleHelper::SUPER_ADMIN);

        return $user;
    }

    // ── Classification & the review queue ────────────────────────────────

    public function test_rows_are_accepted_parked_or_rejected_and_nothing_disappears(): void
    {
        $this->fakeSheets(['DJA' => [
            self::WO_HEADER,
            $this->woRow('WO-1', 'Replace seat cover', '25-21'),         // ATA accept
            $this->woRow('WO-2', 'Cabin carpet worn out'),                // weak keyword -> review
            $this->woRow('WO-3', 'Replace fuel boost pump', '28-10'),     // no signal -> reject
            $this->woRow('WO-4', 'Seat track near engine', ''),           // conflict -> review
            $this->woRow('WO-5', 'Seat belt check', '72-00'),             // ATA reject
        ]]);

        $result = $this->sync();

        $this->assertTrue($result['success']);
        $this->assertSame(['WO-1'], DailyJobAssignment::pluck('task_id')->all());
        $this->assertSame(1, $result['stats']['created']);
        $this->assertSame(2, $result['stats']['review']);
        $this->assertSame(2, $result['stats']['rejected']);
        $this->assertStringContainsString('2 baris perlu review', $result['message']);

        $this->assertSame(4, DjaSyncReview::count(), 'Every non-accepted row stays visible.');
        $this->assertSame('keyword.conflict', DjaSyncReview::where('task_id', 'WO-4')->value('rule'));
        $this->assertSame('ata.reject', DjaSyncReview::where('task_id', 'WO-5')->value('rule'));
    }

    public function test_a_person_can_accept_a_parked_row_and_it_is_remembered(): void
    {
        $this->fakeSheets(['DJA' => [self::WO_HEADER, $this->woRow('WO-2', 'Cabin carpet worn out')]]);
        $this->sync();
        $review = DjaSyncReview::firstWhere('task_id', 'WO-2');
        $this->assertSame('review', $review->bucket);

        Livewire::actingAs($this->admin())->test(DjaPage::class)
            ->call('setTab', 'review')
            ->call('acceptReview', $review->id);

        $this->assertSame('WO-2', DailyJobAssignment::firstOrFail()->task_id);
        $this->assertSame(1, WoLog::count());
        $this->assertSame('accepted', $review->fresh()->decision);

        // Next sync keeps it (even though the rules would still only say "review") and does not duplicate it
        $result = $this->sync();
        $this->assertSame(1, DailyJobAssignment::count());
        $this->assertSame(1, $result['stats']['manual_accepted']);
        $this->assertSame(0, $result['removed'], 'A manually accepted task must not be treated as a ghost.');
    }

    public function test_a_rejected_row_is_not_asked_again(): void
    {
        $this->fakeSheets(['DJA' => [self::WO_HEADER, $this->woRow('WO-2', 'Cabin carpet worn out')]]);
        $this->sync();
        $review = DjaSyncReview::firstOrFail();

        Livewire::actingAs($this->admin())->test(DjaPage::class)->call('rejectReview', $review->id);

        $result = $this->sync();
        $this->assertSame(0, DailyJobAssignment::count());
        $this->assertSame(1, $result['stats']['manual_rejected']);
        $this->assertSame(0, $result['stats']['review']);
    }

    public function test_only_planners_can_decide_reviews(): void
    {
        $this->fakeSheets(['DJA' => [self::WO_HEADER, $this->woRow('WO-2', 'Cabin carpet worn out')]]);
        $this->sync();

        Role::findOrCreate(RoleHelper::PIC_CABIN, 'web');
        $pic = User::factory()->create(['is_default_password' => false]);
        $pic->assignRole(RoleHelper::PIC_CABIN);

        Livewire::actingAs($pic)->test(DjaPage::class)
            ->call('acceptReview', DjaSyncReview::firstOrFail()->id)
            ->assertForbidden();
        $this->assertSame(0, DailyJobAssignment::count());
    }

    public function test_rows_that_the_rules_accept_later_leave_the_review_list(): void
    {
        $this->fakeSheets(['DJA' => [self::WO_HEADER, $this->woRow('WO-9', 'Check something', '')]]);
        $this->sync();
        $this->assertSame(1, DjaSyncReview::count());

        // The planner fills in the ATA chapter
        $this->fakeSheets(['DJA' => [self::WO_HEADER, $this->woRow('WO-9', 'Check something', '25-10')]]);
        $this->sync();

        $this->assertSame(0, DjaSyncReview::count());
        $this->assertSame(1, DailyJobAssignment::where('task_id', 'WO-9')->count());
    }

    // ── What gets copied from the sheet ─────────────────────────────────

    public function test_the_log_gets_the_same_columns_as_an_excel_import_would(): void
    {
        $this->fakeSheets(['DJA' => [self::WO_HEADER, $this->woRow('WO-1', 'Replace seat cover', '25-21', 'knO')]]);
        $this->sync();

        $dja = DailyJobAssignment::firstOrFail();
        $this->assertSame('KNO', $dja->station, 'Station comes from the sheet, not a hard-coded value.');

        $log = WoLog::firstOrFail();
        $this->assertSame($dja->id, $log->dja_id);
        $this->assertSame('WO-1', $log->wo_number);
        $this->assertSame('CABIN', $log->work_group);
        $this->assertSame('CBM', $log->wo_category);
        $this->assertSame('KNO', $log->plan_station);
        $this->assertSame('Budi', $log->operator);
        $this->assertEquals(2.0, $log->man_hour);
        $this->assertSame('Open', $log->status);
    }

    public function test_default_station_is_used_when_the_sheet_has_none(): void
    {
        $this->fakeSheets(['DJA' => [self::WO_HEADER, $this->woRow('WO-1', 'Replace seat cover', '25-21', '')]]);
        $this->sync();

        $this->assertSame(config('dja.default_station'), DailyJobAssignment::firstOrFail()->station);
    }

    public function test_reordered_columns_are_read_by_header_name(): void
    {
        $header = ['TASK ID', 'DESCRIPTION', 'AC REG', 'ATA', 'CATEGORY'];
        $this->fakeSheets(['DJA' => [$header, ['WO-7', 'Replace life vest', 'PK-ABC', '', 'CBM']]]);
        $this->sync();

        $dja = DailyJobAssignment::firstOrFail();
        $this->assertSame('WO-7', $dja->task_id);
        $this->assertSame('PK-ABC', $dja->aircraft_registration);
        $this->assertSame('Replace life vest', $dja->description);
    }

    public function test_resync_refreshes_planner_fields_but_never_overwrites_work_done_in_cbm(): void
    {
        $this->fakeSheets(['DJA' => [self::WO_HEADER, $this->woRow('WO-1', 'Replace seat cover', '25-21')]]);
        $this->sync();

        $log = WoLog::firstOrFail();
        $log->update(['status' => 'Closed', 'hold_reason_category' => 'LT', 'hold_remarks' => 'waiting part', 'act_station' => 'CGK']);

        $this->fakeSheets(['DJA' => [self::WO_HEADER, $this->woRow('WO-1', 'Replace seat cover and belt', '25-21')]]);
        $this->sync();

        $log->refresh();
        $this->assertSame('Replace seat cover and belt', $log->description, 'Planner text is refreshed.');
        $this->assertSame('Closed', $log->status);
        $this->assertSame('LT', $log->hold_reason_category);
        $this->assertSame('waiting part', $log->hold_remarks);
        $this->assertSame('CGK', $log->act_station);
        $this->assertSame(1, WoLog::count());
    }

    public function test_nsrdi_only_takes_cbm_and_painting(): void
    {
        $row = fn (string $task, string $category) => ['1', 'CABIN', 'PK-GQA', $task, 'desc', $category, '', '', '', '', '', 'LION', 'AOC', 'CGK', '', '', ''];
        $this->fakeSheets(['DJA NSRDI' => [self::NSRDI_HEADER, $row('N-1', 'CBM'), $row('N-2', 'Painting'), $row('N-3', 'AVIONICS'), $row('N-4', '')]]);

        $result = $this->sync();

        $this->assertEqualsCanonicalizing(['N-1', 'N-2'], DailyJobAssignment::pluck('task_id')->all());
        $this->assertSame(2, NsrdiLog::count());
        $this->assertSame(1, $result['stats']['rejected']);
        $this->assertSame(1, $result['stats']['review']);
        $this->assertSame('CGK', NsrdiLog::firstWhere('nsrdi_number', 'N-1')->plan_station);
    }

    public function test_excel_import_and_google_sync_give_the_same_result(): void
    {
        // Positional row (no header): same layout the Excel template uses
        $positional = ['1', 'CABIN', 'PK-GQA', 'WO-1', 'CBM', 'Replace life vest', '', '2', 'Budi', 'ROUTINE', 'KNO', 'note', '', '', '', ''];
        (new DjaSheetImport('DJA'))->model($positional);
        (new DjaSheetImport('DJA'))->model(['1', 'CABIN', 'PK-GQA', 'WO-2', 'CBM', 'Replace fuel pump']);

        $this->assertSame(['WO-1'], DailyJobAssignment::pluck('task_id')->all());
        $this->assertSame(1, DjaSyncReview::where('task_id', 'WO-2')->where('bucket', 'reject')->count());
        $this->assertSame('KNO', WoLog::firstOrFail()->plan_station);
    }

    // ── Staleness / failure ───────────────────────────────────────────────

    public function test_stale_sync_is_flagged_on_the_page(): void
    {
        SyncSetting::for(SyncSetting::Dja)->update(['spreadsheet_id' => self::SHEET, 'last_synced_at' => now()->subMinutes(45), 'last_status' => 'success']);

        $html = Livewire::actingAs($this->admin())->test(DjaPage::class)->html();
        $this->assertStringContainsString('belum diperbarui 45 menit', $html);

        SyncSetting::for(SyncSetting::Dja)->update(['last_synced_at' => now()->subMinutes(3)]);
        $this->assertStringNotContainsString('belum diperbarui', Livewire::actingAs($this->admin())->test(DjaPage::class)->html());
    }

    public function test_failed_scheduled_sync_notifies_super_admins_once_per_streak(): void
    {
        $admin = $this->admin();
        SyncSetting::for(SyncSetting::Dja)->update(['spreadsheet_id' => self::SHEET]);

        $reader = Mockery::mock(GoogleSheetsReader::class)->makePartial();
        $reader->shouldReceive('isReady')->andReturn(true);
        $reader->shouldReceive('service')->andReturn(Mockery::mock(Sheets::class));
        $reader->shouldReceive('tabTitles')->andThrow(new \RuntimeException('Network error'));
        $this->app->instance(GoogleSheetsReader::class, $reader);

        $this->artisan('sync:daily-dja')->assertFailed();
        $this->assertSame(1, $admin->fresh()->notifications()->count());

        $this->artisan('sync:daily-dja')->assertFailed();
        $this->assertSame(1, $admin->fresh()->notifications()->count(), 'No repeat while it keeps failing.');
    }

    // ── Closed / Open actions ───────────────────────────────────────────

    private function wo(array $attrs = []): WoLog
    {
        $dja = DailyJobAssignment::create(['task_id' => 'WO-1', 'date' => now()->toDateString(), 'aircraft_registration' => 'PK-GQA', 'job_type' => 'R01/WO', 'station' => 'CGK', 'source_spreadsheet_id' => self::SHEET]);

        return WoLog::create(array_merge(['dja_id' => $dja->id, 'date' => now()->toDateString(), 'aircraft_registration' => 'PK-GQA', 'wo_number' => 'WO-1', 'status' => 'Open'], $attrs));
    }

    public function test_open_requires_both_a_reason_code_and_remarks(): void
    {
        $log = $this->wo();
        $page = Livewire::actingAs($this->admin())->test(WoPage::class)->call('openStatusModal', $log->id);

        $page->set('hold_reason_category', '')->set('hold_remarks', '')->call('updateStatus')
            ->assertHasErrors(['hold_reason_category', 'hold_remarks']);

        $page->set('hold_reason_category', 'LT')->set('hold_remarks', '  ')->call('updateStatus')
            ->assertHasErrors('hold_remarks')->assertHasNoErrors('hold_reason_category');

        $page->set('hold_reason_category', 'XX')->set('hold_remarks', 'waiting part')->call('updateStatus')
            ->assertHasErrors('hold_reason_category');

        $this->assertNull($log->fresh()->hold_reason_category, 'Nothing is saved while validation fails.');
    }

    public function test_open_is_saved_and_pushed_to_the_sheet_with_code_and_remarks(): void
    {
        $log = $this->wo(['status' => 'Closed']);

        $sync = Mockery::mock(GoogleSheetsSyncService::class);
        $sync->shouldReceive('pushSync')->once()->with(self::SHEET, 'DJA', 'WO-1', 'Open', 'waiting part from CGK', 'LT')->andReturn(true);
        $this->app->instance(GoogleSheetsSyncService::class, $sync);

        Livewire::actingAs($this->admin())->test(WoPage::class)
            ->call('openStatusModal', $log->id)
            ->set('hold_reason_category', 'LT')->set('hold_remarks', 'waiting part from CGK')
            ->call('updateStatus')->assertHasNoErrors()->assertSet('isModalOpen', false);

        $log->refresh();
        $this->assertSame('Open', $log->status);
        $this->assertSame('LT', $log->hold_reason_category);
        $this->assertSame('waiting part from CGK', $log->hold_remarks);
    }

    public function test_closed_is_one_click_clears_the_reason_and_pushes_to_the_sheet(): void
    {
        $log = $this->wo(['hold_reason_category' => 'LT', 'hold_remarks' => 'waiting part']);

        $sync = Mockery::mock(GoogleSheetsSyncService::class);
        $sync->shouldReceive('pushSync')->once()->with(self::SHEET, 'DJA', 'WO-1', 'Closed', null, null)->andReturn(true);
        $this->app->instance(GoogleSheetsSyncService::class, $sync);

        Livewire::actingAs($this->admin())->test(WoPage::class)->call('markClosed', $log->id);

        $log->refresh();
        $this->assertSame('Closed', $log->status);
        $this->assertNull($log->hold_reason_category);
        $this->assertNull($log->hold_remarks);
    }

    public function test_a_failed_sheet_write_back_is_reported_not_hidden(): void
    {
        $log = $this->wo();

        $sync = Mockery::mock(GoogleSheetsSyncService::class);
        $sync->shouldReceive('pushSync')->andReturn(false);
        $this->app->instance(GoogleSheetsSyncService::class, $sync);

        Livewire::actingAs($this->admin())->test(WoPage::class)
            ->call('markClosed', $log->id)
            ->assertDispatched('notify', fn (string $event, array $params) => ($params['icon'] ?? null) === 'warning' || ($params[0]['icon'] ?? null) === 'warning');

        $this->assertSame('Closed', $log->fresh()->status, 'The CBM status is still saved.');
    }

    public function test_closing_an_nsrdi_stamps_the_close_date_that_capacity_reports_on(): void
    {
        $dja = DailyJobAssignment::create(['task_id' => 'N-1', 'date' => now()->toDateString(), 'aircraft_registration' => 'PK-GQA', 'job_type' => 'AOC/NSRDI', 'station' => 'CGK']);
        $log = NsrdiLog::create(['dja_id' => $dja->id, 'aircraft_registration' => 'PK-GQA', 'nsrdi_number' => 'N-1', 'plan_date' => now()->toDateString(), 'report_date' => now()->toDateString(), 'status' => 'Open']);

        $page = Livewire::actingAs($this->admin())->test(NsrdiPage::class)->call('markClosed', $log->id);
        $this->assertSame(DjaPersister::activeDate(), substr((string) $log->fresh()->close_date, 0, 10));

        $page->call('openStatusModal', $log->id)->set('hold_reason_category', 'NS')->set('hold_remarks', 'no spare available')->call('updateStatus');
        $this->assertNull($log->fresh()->close_date, 'Re-opening clears the close date.');
    }

    // ── Write-back targets the right cells ─────────────────────────────

    public function test_push_writes_status_code_and_reason_into_their_own_columns(): void
    {
        $values = Mockery::mock();
        $captured = null;
        $values->shouldReceive('batchUpdate')->once()->andReturnUsing(function ($id, $request) use (&$captured) {
            $captured = $request;

            return null;
        });
        $sheets = Mockery::mock(Sheets::class);
        $sheets->spreadsheets_values = $values;

        $reader = Mockery::mock(GoogleSheetsReader::class)->makePartial();
        $reader->shouldReceive('isReady')->andReturn(true);
        $reader->shouldReceive('service')->andReturn($sheets);
        $reader->shouldReceive('tabTitles')->andReturn(['DJA']);
        $reader->shouldReceive('values')->andReturn([self::WO_HEADER, $this->woRow('WO-1', 'x'), $this->woRow('WO-2', 'y')]);
        $this->app->instance(GoogleSheetsReader::class, $reader);

        $this->assertTrue(app(GoogleSheetsSyncService::class)->pushSync(self::SHEET, 'DJA', 'WO-2', 'Open', 'waiting part', 'LT'));

        $written = collect($captured->getData())->mapWithKeys(fn ($vr) => [$vr->getRange() => $vr->getValues()[0][0]])->all();
        // header is row 1, WO-1 row 2, WO-2 row 3; STATUS=N, CODE REASON=O, REASON OPEN=P
        $this->assertSame(['N3' => 'Open', 'O3' => 'LT', 'P3' => 'waiting part'], collect($written)->mapWithKeys(fn ($v, $k) => [str_replace("'DJA'!", '', $k) => $v])->all());
    }
}
