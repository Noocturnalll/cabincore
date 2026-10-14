<?php

namespace Tests\Feature;

use App\Helpers\RoleHelper;
use App\Livewire\Modules\AircraftCleaning\DailyReport;
use App\Livewire\Modules\CmlLog\Index as CmlPage;
use App\Livewire\Modules\DailyBeautification\Index;
use App\Livewire\Modules\DailyReport\Index as DailyReportPage;
use App\Models\AircraftCleaning;
use App\Models\CmlLog;
use App\Models\User;
use App\Services\Kpi\ModuleTiles;
use App\Services\Kpi\OperationalPivot;
use App\Services\Kpi\ReportPeriod;
use App\Support\CmlKind;
use Carbon\Carbon;
use Database\Seeders\RegistryPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/** DBI (Daily Beautification Inspection) is Cabin Maintenance: a CML that says so goes to the DBI tab, not into the CML figures. */
class CmlDbiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-14 10:00:00');
        $this->seed(RegistryPermissionSeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function manager(): User
    {
        $u = User::factory()->create(['is_default_password' => false, 'status' => 'active', 'station' => 'CGK']);
        $u->assignRole(RoleHelper::MANAGER);

        return $u;
    }

    private function cml(string $description, array $extra = []): CmlLog
    {
        return CmlLog::create($extra + [
            'aircraft_registration' => 'PK-LAT', 'station' => 'CGK', 'date' => '2026-10-13', 'status' => 'Closed', 'doc_type' => 'CML',
            'description' => $description,
        ]);
    }

    public function test_the_description_decides(): void
    {
        $this->assertSame('DBI', CmlKind::detect('BEAUTIFICATION INSPECTION DAILY HAS BEEN PERFORMED'));
        $this->assertSame('DBI', CmlKind::detect('IAW AMM 25-00-00 PERFORMED DAILY CHECK BEAUTIFICATION INSPECTION HAS BEEN C/O'));
        $this->assertSame('DBI', CmlKind::detect('dbi performed, all ok'));
        $this->assertSame('DBI', CmlKind::detect('DBI: seat cover cleaned'));
        $this->assertSame('CML', CmlKind::detect('WINDOW LIGHT SN. 21-30ABC ALWAYS ILLUMINATE'));
        $this->assertSame('CML', CmlKind::detect('GDBIN CONNECTOR REPLACED'), 'DBI must be a word of its own');
        $this->assertSame('CML', CmlKind::detect(null));
    }

    public function test_saving_a_cml_classifies_it_and_clears_a_label_that_is_not_a_station(): void
    {
        $dbi = $this->cml('BEAUTIFICATION INSPECTION DAILY HAS BEEN PERFORMED');
        $defect = $this->cml('PROBLEM TRANSFER TO AFML (WINDOW LIGHT)', ['station' => 'DBI']);

        $this->assertSame('DBI', $dbi->fresh()->kind);
        $this->assertSame('CML', $defect->fresh()->kind, 'a defect found during a DBI is still a CML defect');
        $this->assertNull($defect->fresh()->station, '"DBI" in the station cell is a label, not a station');
    }

    public function test_rows_written_in_bulk_are_classified_by_the_archive_run(): void
    {
        DB::table('cml_logs')->insert([
            ['aircraft_registration' => 'PK-A', 'date' => '2026-10-13', 'status' => 'Closed', 'station' => 'DBI', 'description' => 'BEAUTIFICATION INSPECTION DAILY', 'created_at' => now(), 'updated_at' => now()],
            ['aircraft_registration' => 'PK-B', 'date' => '2026-10-13', 'status' => 'Closed', 'station' => 'DBI', 'description' => 'LAMP REPLACED', 'created_at' => now(), 'updated_at' => now()],
        ]);

        CmlKind::sync();

        $rows = DB::table('cml_logs')->orderBy('id')->get(['kind', 'station']);
        $this->assertSame(['DBI', 'CML'], $rows->pluck('kind')->all());
        $this->assertSame([null, null], $rows->pluck('station')->all());
    }

    public function test_the_cml_page_keeps_the_two_lists_apart(): void
    {
        $this->cml('BEAUTIFICATION INSPECTION DAILY HAS BEEN PERFORMED');
        $this->cml('BEAUTIFICATION INSPECTION DAILY HAS BEEN PERFORMED', ['aircraft_registration' => 'PK-LAU']);
        $this->cml('SEAT BELT REPLACED');

        $page = Livewire::actingAs($this->manager())->test(CmlPage::class);
        $this->assertSame(1, $page->viewData('logs')->total(), 'CML list: the defect only');
        $this->assertSame(2, $page->viewData('dbiCount'));

        $page->call('setTab', 'dbi');
        $this->assertSame(2, $page->viewData('logs')->total(), 'DBI tab: the two inspections');
    }

    public function test_the_daily_report_has_a_dbi_tab(): void
    {
        // the Daily Report lists what is older than the active day
        $this->cml('BEAUTIFICATION INSPECTION DAILY HAS BEEN PERFORMED', ['date' => '2026-10-10']);
        $this->cml('SEAT BELT REPLACED', ['date' => '2026-10-10']);
        $this->cml('SEAT COVER TORN', ['date' => '2026-10-10']);

        $page = Livewire::actingAs($this->manager())->test(DailyReportPage::class)->call('setTab', 'cml');
        $this->assertSame(2, $page->viewData('logs')->total());

        $page->call('setTab', 'dbi');
        $this->assertSame(1, $page->viewData('logs')->total());
    }

    public function test_dashboard_figures_do_not_count_a_dbi_as_a_cml_and_show_it_on_its_own(): void
    {
        $this->cml('BEAUTIFICATION INSPECTION DAILY HAS BEEN PERFORMED');
        $this->cml('BEAUTIFICATION INSPECTION DAILY HAS BEEN PERFORMED', ['aircraft_registration' => 'PK-LAU', 'status' => 'Open']);
        $this->cml('SEAT BELT REPLACED');

        $period = ReportPeriod::day(Carbon::parse('2026-10-13'));
        $pivot = app(OperationalPivot::class)->build($this->manager(), $period);
        $this->assertSame(1, $pivot['rows']['CGK']['cml']['total']);
        $this->assertSame(['total' => 2, 'closed' => 1, 'open' => 1, 'rate' => 50.0], $pivot['rows']['CGK']['dbi']);
        $this->assertSame('DBI', $pivot['blocks']['dbi']);

        $tiles = collect(app(ModuleTiles::class)->build($this->manager(), $period))->flatten(1)->keyBy('key');
        $this->assertSame('1', $tiles['cml']['value']);
    }

    public function test_dbi_is_not_aiec_work(): void
    {
        foreach (['Transit', 'DCI', 'DBI'] as $i => $type) {
            DB::table('aircraft_cleanings')->insert(['aircraft_registration' => 'PK-C'.$i, 'date' => '2026-10-10', 'shift' => 'Pagi', 'type' => $type, 'status' => 'Closed', 'station' => 'CGK', 'created_at' => now(), 'updated_at' => now()]);
        }

        $this->assertSame(['DCI', 'Transit'], AircraftCleaning::aiec()->orderBy('type')->pluck('type')->all());

        // the AIEC daily report lists every cleaning type except DBI
        $page = Livewire::actingAs($this->manager())->test(DailyReport::class);
        $this->assertSame(2, $page->viewData('logs')->total());
        $this->assertStringNotContainsString('wire:click="setCleaningType(\'DBI\')"', $page->html());

        // while the Cabin Maintenance DBI page still has it
        $dbi = Livewire::actingAs($this->manager())->test(Index::class);
        $this->assertSame(1, $dbi->viewData('cleanings')->total());
    }
}
