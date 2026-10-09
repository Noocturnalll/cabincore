<?php

namespace Tests\Feature;

use App\Helpers\RoleHelper;
use App\Livewire\Modules\DailyReport\Index as DailyReportPage;
use App\Livewire\Modules\DmiLog\Index as DmiPage;
use App\Livewire\Modules\WoLog\Index as WoPage;
use App\Models\CmlLog;
use App\Models\DailyJobAssignment;
use App\Models\DmiLog;
use App\Models\NsrdiLog;
use App\Models\User;
use App\Models\WoLog;
use App\Services\Dja\DailyReportArchiver;
use App\Services\GoogleSheetsReader;
use App\Services\GoogleSheetsSyncService;
use Google\Service\Sheets;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Operational day = 18:00 to 18:00, labelled with the date it starts on.
 * At 18:00 on the 8th the logs of the 7th are past their cutoff and go to the Daily Report.
 */
class DailyReportArchiveTest extends TestCase
{
    use RefreshDatabase;

    private function wo(string $date, array $attrs = []): WoLog
    {
        return WoLog::create(array_merge(['date' => $date, 'aircraft_registration' => 'PK-GQA', 'wo_number' => 'WO-'.uniqid(), 'status' => 'Open'], $attrs));
    }

    private function admin(): User
    {
        Role::findOrCreate(RoleHelper::SUPER_ADMIN, 'web');
        $user = User::factory()->create(['is_default_password' => false, 'status' => 'active']);
        $user->assignRole(RoleHelper::SUPER_ADMIN);

        return $user;
    }

    public function test_nothing_is_archived_before_the_1800_cutoff_and_yesterdays_logs_go_after_it(): void
    {
        $closed = $this->wo('2026-10-07', ['status' => 'Closed']);

        Carbon::setTestNow('2026-10-08 17:55:00'); // still the operational day of the 7th
        $this->assertSame(0, array_sum(app(DailyReportArchiver::class)->run()));
        $this->assertFalse((bool) $closed->fresh()->is_submitted);

        Carbon::setTestNow('2026-10-08 18:00:00'); // the 7th is past its cutoff
        $this->assertSame(1, app(DailyReportArchiver::class)->run()['wo']);
        $this->assertTrue((bool) $closed->fresh()->is_submitted);
    }

    public function test_todays_own_logs_are_not_archived(): void
    {
        $today = $this->wo('2026-10-08', ['status' => 'Closed']);

        Carbon::setTestNow('2026-10-08 19:00:00');
        app(DailyReportArchiver::class)->run();

        $this->assertFalse((bool) $today->fresh()->is_submitted);
    }

    public function test_open_logs_need_both_code_and_remarks_otherwise_they_are_held(): void
    {
        $complete = $this->wo('2026-10-07', ['hold_reason_category' => 'LT', 'hold_remarks' => 'waiting part']);
        $noRemarks = $this->wo('2026-10-07', ['hold_reason_category' => 'LT']);
        $noCode = $this->wo('2026-10-07', ['hold_remarks' => 'waiting part']);
        $untouched = $this->wo('2026-10-07');
        $fromSheet = $this->wo('2026-10-07', ['code_open' => 'MP', 'reason_open' => 'manpower']); // reason already in the planner sheet

        Carbon::setTestNow('2026-10-08 18:05:00');
        $archiver = app(DailyReportArchiver::class);
        $moved = $archiver->run();

        $this->assertSame(2, $moved['wo']);
        $this->assertTrue((bool) $complete->fresh()->is_submitted);
        $this->assertTrue((bool) $fromSheet->fresh()->is_submitted);
        foreach ([$noRemarks, $noCode, $untouched] as $held) {
            $this->assertFalse((bool) $held->fresh()->is_submitted);
        }
        $this->assertSame(3, $archiver->held()['wo']);
    }

    public function test_running_twice_is_harmless(): void
    {
        $this->wo('2026-10-07', ['status' => 'Closed']);
        Carbon::setTestNow('2026-10-08 18:30:00');

        $this->assertSame(1, app(DailyReportArchiver::class)->run()['wo']);
        $this->assertSame(0, app(DailyReportArchiver::class)->run()['wo']);
    }

    public function test_dmi_reason_entered_in_cbm_counts_and_nsrdi_is_judged_by_its_plan_date(): void
    {
        $dmi = DmiLog::create(['date' => '2026-10-07', 'aircraft_registration' => 'PK-GQA', 'dmi_number' => 'D1', 'status' => 'Open', 'hold_reason_category' => 'NS', 'hold_remarks' => 'no spare']);
        // reported weeks ago but planned for today: must NOT be archived early
        $planned = NsrdiLog::create(['plan_date' => '2026-10-08', 'report_date' => '2026-09-10', 'aircraft_registration' => 'PK-GQA', 'nsrdi_number' => 'N1', 'status' => 'Closed']);
        $old = NsrdiLog::create(['plan_date' => '2026-10-07', 'report_date' => '2026-10-07', 'aircraft_registration' => 'PK-GQA', 'nsrdi_number' => 'N2', 'status' => 'Closed']);

        Carbon::setTestNow('2026-10-08 18:10:00');
        $moved = app(DailyReportArchiver::class)->run();

        $this->assertSame(1, $moved['dmi']);
        $this->assertSame(1, $moved['nsrdi']);
        $this->assertTrue((bool) $dmi->fresh()->is_submitted);
        $this->assertFalse((bool) $planned->fresh()->is_submitted);
        $this->assertTrue((bool) $old->fresh()->is_submitted);
    }

    public function test_the_scheduled_command_does_the_same_job(): void
    {
        $this->wo('2026-10-07', ['status' => 'Closed']);
        Carbon::setTestNow('2026-10-08 18:15:00');

        $this->artisan('dailyreport:auto-submit')->assertSuccessful();

        $this->assertSame(1, WoLog::where('is_submitted', true)->count());
    }

    public function test_a_sync_after_the_cutoff_banks_the_previous_days_logs_immediately(): void
    {
        $this->wo('2026-10-07', ['status' => 'Closed']);
        Carbon::setTestNow('2026-10-08 18:03:00');

        $sheet = '1ArchiveSheetAbCdEfGhIjKlMnOpQrStUv';
        $reader = Mockery::mock(GoogleSheetsReader::class)->makePartial();
        $reader->shouldReceive('isReady')->andReturn(true);
        $reader->shouldReceive('service')->andReturn(Mockery::mock(Sheets::class));
        $reader->shouldReceive('tabTitles')->andReturn(['DJA']);
        $reader->shouldReceive('values')->andReturn([['TASK ID', 'AC REG', 'DESCRIPTION', 'ATA'], ['WO-NEW', 'PK-GQB', 'Replace seat cover', '25-21']]);
        $this->app->instance(GoogleSheetsReader::class, $reader);

        $result = app(GoogleSheetsSyncService::class)->syncDja($sheet);

        $this->assertSame(1, $result['archived']);
        $this->assertStringContainsString('1 log masuk Daily Report', $result['message']);
        $this->assertSame(1, WoLog::where('is_submitted', true)->count());
        $this->assertSame(1, DailyJobAssignment::where('task_id', 'WO-NEW')->count());
    }

    public function test_cml_needs_no_flag_and_shows_in_the_daily_report_once_its_day_has_passed(): void
    {
        CmlLog::create(['date' => '2026-10-07', 'aircraft_registration' => 'PK-GQA', 'status' => 'Open']);
        CmlLog::create(['date' => '2026-10-08', 'aircraft_registration' => 'PK-GQB', 'status' => 'Open']);

        Carbon::setTestNow('2026-10-08 18:30:00');
        $page = Livewire::actingAs($this->admin())->test(DailyReportPage::class)->call('setTab', 'cml');

        $this->assertSame(['PK-GQA'], $page->viewData('logs')->pluck('aircraft_registration')->all());
    }

    public function test_daily_report_lists_only_banked_logs_and_warns_about_held_ones(): void
    {
        $this->wo('2026-10-07', ['status' => 'Closed', 'aircraft_registration' => 'PK-BANKED']);
        $this->wo('2026-10-07', ['aircraft_registration' => 'PK-HELD']);
        $dja = DailyJobAssignment::create(['task_id' => 'X', 'date' => '2026-10-07', 'aircraft_registration' => 'PK-BANKED', 'job_type' => 'R01/WO', 'station' => 'CGK']);
        WoLog::where('aircraft_registration', 'PK-BANKED')->update(['dja_id' => $dja->id]);

        Carbon::setTestNow('2026-10-08 18:20:00');
        app(DailyReportArchiver::class)->run();

        $page = Livewire::actingAs($this->admin())->test(DailyReportPage::class)->call('setTab', 'dja-wo');
        $this->assertSame(['PK-BANKED'], $page->viewData('logs')->pluck('aircraft_registration')->all());
        $this->assertSame(1, $page->viewData('held')['wo']);
        $this->assertStringContainsString('1 log belum masuk arsip', $page->html());
    }

    public function test_unfinished_logs_of_earlier_days_stay_visible_on_the_module_page(): void
    {
        $held = $this->wo('2026-10-07', ['aircraft_registration' => 'PK-HELD']);
        $done = $this->wo('2026-10-07', ['aircraft_registration' => 'PK-DONE', 'status' => 'Closed']);
        $today = $this->wo('2026-10-08', ['aircraft_registration' => 'PK-TODAY']);

        Carbon::setTestNow('2026-10-08 18:20:00'); // active day is the 8th
        $page = Livewire::actingAs($this->admin())->test(WoPage::class)->set('activeTab', 'unplanned');
        $regs = collect($page->viewData('logs')->items())->pluck('aircraft_registration')->all();

        $this->assertEqualsCanonicalizing(['PK-TODAY', 'PK-HELD'], $regs, 'Held log carried over, finished one is left to the archive.');
        $this->assertSame(1, $page->viewData('carryOver'));

        // once it gets a complete reason it leaves the page for the Daily Report
        $held->update(['hold_reason_category' => 'LT', 'hold_remarks' => 'waiting part']);
        app(DailyReportArchiver::class)->run();
        $regs = collect(Livewire::actingAs($this->admin())->test(WoPage::class)->set('activeTab', 'unplanned')->viewData('logs')->items())->pluck('aircraft_registration')->all();
        $this->assertSame(['PK-TODAY'], $regs);

        // a chosen date shows exactly that day, no carry-over mixed in
        $this->assertSame(0, Livewire::actingAs($this->admin())->test(DmiPage::class)->set('dateFilter', '2026-10-07')->viewData('carryOver'));
    }
}
