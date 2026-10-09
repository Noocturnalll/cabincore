<?php

namespace Tests\Feature;

use App\Helpers\RoleHelper;
use App\Livewire\Modules\Sources\Index;
use App\Models\Aircraft;
use App\Models\CmlLog;
use App\Models\JobCrew;
use App\Models\LeaderReportImport;
use App\Models\MasterEntry;
use App\Models\SyncSetting;
use App\Models\User;
use App\Services\Master\MasterSettings;
use App\Services\Sources\AmmSync;
use App\Services\Sources\CbmClosingSync;
use App\Services\Sources\DataSources;
use App\Services\Sources\SheetFetcher;
use App\Services\Sources\TargetSync;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RegistryPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SourcesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RegistryPermissionSeeder::class);
        $this->seed(MasterDataSeeder::class);
        MasterSettings::flush();
    }

    /** A fetcher that serves canned tabs instead of calling Google. */
    private function fetcher(array $tabs): SheetFetcher
    {
        return new class($tabs) extends SheetFetcher
        {
            public function __construct(private array $tabs) {}

            public function values(string $spreadsheetId, string $tab): ?array
            {
                return $this->tabs[$tab] ?? null;
            }

            public function lastError(): ?string
            {
                return 'Tab tidak ada.';
            }
        };
    }

    public function test_amm_working_groups_fill_the_aircraft_master(): void
    {
        Aircraft::create(['registration' => 'PK-LFF', 'tipe' => 'B737', 'maskapai' => 'Lion Air', 'status' => 'Aktif']);
        Aircraft::create(['registration' => 'PK-LFK', 'tipe' => 'B737', 'maskapai' => 'Lion Air', 'status' => 'Aktif', 'wg' => 'WG 09']);   // moved to another group
        Aircraft::create(['registration' => 'PK-LBK', 'tipe' => 'B737', 'maskapai' => 'Lion Air', 'status' => 'Aktif']);

        $result = (new AmmSync($this->fetcher(['WORKING GROUP' => [
            ['WORKING GROUP', 'REGISTRATION'],
            ['WG 1', 'PK-LFF,  PK-LFG ,PK-LXX'],
            ['WG 2', 'PK-LFK'],
            ['WG 4', ''],
            ['WG 5', 'PK-LBK  ,PK-LBS'],
            ['HOTLINE', 'PK-AAA'],
        ]])))->sync('sheet');

        $this->assertTrue($result['ok']);
        $this->assertSame('WG 01', Aircraft::where('registration', 'PK-LFF')->value('wg'));
        $this->assertSame('WG 02', Aircraft::where('registration', 'PK-LFK')->value('wg'), 'the sheet is the truth for the group');
        $this->assertSame('WG 05', Aircraft::where('registration', 'PK-LBK')->value('wg'));
        $this->assertStringContainsString('PK-LFG', $result['message'], 'registrations missing from the master are reported');

        $again = (new AmmSync($this->fetcher(['WORKING GROUP' => [['WG 1', 'PK-LFF']]])))->sync('sheet');
        $this->assertStringContainsString('0 pesawat diperbarui', $again['message']);
    }

    public function test_targets_become_master_entries_and_are_updated_in_place(): void
    {
        $tab = [
            ['', 'TARGETCLOSED PER HARI STA', 'JT', 'ID', 'IU', 'IW', 'GRAN TOTAL'],
            ['1', 'CGK', '2', '7', '7', '0', '16'],
            ['2', 'HLP', '0', '2', '0', '0', '2'],
            ['', 'TOTAL', '3', '9', '7', '0', '19'],
        ];

        $result = (new TargetSync($this->fetcher(['TARGET' => $tab])))->sync('sheet');

        $this->assertTrue($result['ok']);
        $this->assertSame(8, MasterEntry::where('type', 'kpi_target')->count());
        $this->assertEquals(7, MasterEntry::where('code', 'CGK-ID')->first()->attrs['target']);
        $this->assertSame('CGK', MasterEntry::where('code', 'CGK-ID')->first()->attrs['station']);

        $tab[1][3] = '9';
        (new TargetSync($this->fetcher(['TARGET' => $tab])))->sync('sheet');
        $this->assertEquals(9, MasterEntry::where('code', 'CGK-ID')->first()->attrs['target']);
        $this->assertSame(8, MasterEntry::where('type', 'kpi_target')->count(), 'no duplicates');
    }

    private function closingTab(): array
    {
        return [
            ['DATE', 'OPERATOR', 'A/C TYPE', 'A/C REG', 'A/C Status', 'STA', 'SHIFT', 'TASK TYPE', 'DOC TYPE', 'STATUS', 'NO.DOC', 'ACTION TAKEN / REASON', 'START PERFORM', 'FINISH PERFORM', 'MP 1', 'MP 2', 'MP 3', 'Total MP', 'Individual MHRS', 'Actual MHRS'],
            ['05-Oct-26', 'BATIK AIR', 'B737', 'PK-LBK', 'RON', 'CGK', 'MALAM', 'UNSCHEDULE', 'CML', 'CLOSED', 'B0141810', 'DAILY CHECK', '3:00', '4:30', '145354', '145572', '', '2', '1.5', '3.0'],
            ['05-Oct-26', 'BATIK AIR', 'A320', 'PK-LAY', 'LGT', 'HLP', 'PAGI', 'DBI', 'CML', 'CLOSED', 'B144698', 'ADJUST SEAT', '17:15', '18:30', '83124751', '', '', '1', '1.25', '1.25'],
        ];
    }

    public function test_cbm_closing_waits_for_review_unless_auto_apply_is_on(): void
    {
        $sync = new CbmClosingSync($this->fetcher(['DATA' => $this->closingTab()]));

        $first = $sync->sync('sheet');
        $this->assertTrue($first['ok']);
        $this->assertStringContainsString('siap ditinjau', $first['message']);
        $this->assertSame(0, CmlLog::count(), 'a preview changes nothing');
        $import = LeaderReportImport::first();
        $this->assertSame('preview', $import->status);
        $this->assertSame(2, $import->stats['cml_new']);

        // the same sheet again: nothing new to review
        $again = $sync->sync('sheet');
        $this->assertStringContainsString('Tidak ada perubahan', $again['message']);
        $this->assertSame(1, LeaderReportImport::count());
    }

    public function test_cbm_closing_with_auto_apply_saves_documents_and_the_crew(): void
    {
        MasterEntry::where('code', 'AUTO_APPLY_CBM_CLOSING')->update(['attrs' => ['value' => 'Ya']]);
        MasterSettings::flush();

        $result = (new CbmClosingSync($this->fetcher(['DATA' => $this->closingTab()])))->sync('sheet');

        $this->assertStringContainsString('diterapkan', $result['message']);
        $this->assertSame(2, CmlLog::count());
        $log = CmlLog::where('no_doc', 'B0141810')->first();
        $this->assertEquals(3.0, $log->man_hour);
        $this->assertSame(2, $log->man_power);

        $crew = JobCrew::where('jobable_type', 'cml_log')->where('jobable_id', $log->id)->orderBy('employee_ref')->get();
        $this->assertSame(['145354', '145572'], $crew->pluck('employee_ref')->all());
        $this->assertEquals([1.5, 1.5], $crew->pluck('man_hour')->all());
    }

    public function test_a_tab_that_cannot_be_read_reports_instead_of_crashing(): void
    {
        $result = (new CbmClosingSync($this->fetcher([])))->sync('sheet');
        $this->assertFalse($result['ok']);
        $this->assertSame('Tab tidak ada.', $result['message']);

        $bad = (new CbmClosingSync($this->fetcher(['DATA' => [['foo', 'bar'], [1, 2]]])))->sync('sheet');
        $this->assertFalse($bad['ok']);
    }

    public function test_registry_stores_the_link_and_the_result_of_each_run(): void
    {
        $sources = new DataSources;

        $this->assertSame(config('sources.sources.amm.default'), $sources->spreadsheetId('amm'));
        $this->assertTrue($sources->setSpreadsheet('amm', 'https://docs.google.com/spreadsheets/d/1AbCdEfGhIjKlMnOpQrStUvWxYz123456/edit?usp=sharing'));
        $this->assertSame('1AbCdEfGhIjKlMnOpQrStUvWxYz123456', $sources->spreadsheetId('amm'));
        $this->assertFalse($sources->setSpreadsheet('amm', 'bukan link'));
        $this->assertFalse($sources->setSpreadsheet('dja', '1AbCdEfGhIjKlMnOpQrStUvWxYz123456'), 'planner sheets are not edited here');

        $this->app->instance(SheetFetcher::class, $this->fetcher(['WORKING GROUP' => [['WG 1', 'PK-LFF']]]));
        $result = $sources->run('amm');
        $this->assertTrue($result['ok']);
        $this->assertSame('success', SyncSetting::where('key', 'amm')->value('last_status'));
        $this->assertFalse($sources->run('dja')['ok']);
    }

    public function test_page_permissions(): void
    {
        $manager = User::factory()->create(['is_default_password' => false, 'status' => 'active']);
        $manager->assignRole(RoleHelper::MANAGER);
        $pic = User::factory()->create(['is_default_password' => false, 'status' => 'active']);
        $pic->assignRole(RoleHelper::PIC_CABIN);
        $nobody = User::factory()->create(['is_default_password' => false, 'status' => 'active']);

        $this->actingAs($nobody)->get('/sources')->assertForbidden();
        $this->actingAs($pic)->get('/sources')->assertOk()->assertSee('DJA (Daily Job Assignment)', false);

        // a PIC may look but not sync
        Livewire::actingAs($pic)->test(Index::class)->call('sync', 'amm')->assertForbidden();
        Livewire::actingAs($manager)->test(Index::class)->assertSee('Closing CBM');
    }
}
