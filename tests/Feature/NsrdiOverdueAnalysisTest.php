<?php

namespace Tests\Feature;

use App\Helpers\RoleHelper;
use App\Livewire\Modules\NsrdiOverdue\Index;
use App\Models\NsrdiLog;
use App\Models\NsrdiOverdue;
use App\Models\User;
use App\Services\Nsrdi\NsrdiOverdueSync;
use Carbon\Carbon;
use Database\Seeders\RegistryPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

/** The weekly NSRDI overdue workbook against the data bank: what closes, what stays open and until when. */
class NsrdiOverdueAnalysisTest extends TestCase
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

    private function admin(): User
    {
        $u = User::factory()->create(['is_default_password' => false, 'status' => 'active']);
        $u->assignRole(RoleHelper::MANAGER);

        return $u;
    }

    /** a two-sheet workbook like the weekly file: the defect list and the part status per document */
    private function workbook(array $defects, array $parts = []): string
    {
        $book = new Spreadsheet;
        $list = $book->getActiveSheet()->setTitle('NSRDIL');
        $list->fromArray(['PROVIDER', 'A/C', 'STATUS A/C', 'DEFECT', 'MDDR', 'STATUS', 'AREA', 'REPORTED_DATE', 'DUE-DATE', 'MONTH-DUE', 'DEFECT DESCRIPTION'], null, 'A1');
        foreach ($defects as $i => $d) {
            $list->fromArray([
                $d['aoc'] ?? 'JT', $d['ac'] ?? 'PK-LJQ', 'OPERATE', $d['defect'], $d['mddr'] ?? null, $d['status'] ?? 'OPEN', 'CBM',
                Date::PHPToExcel(Carbon::parse($d['reported'] ?? '2026-09-01')), Date::PHPToExcel(Carbon::parse($d['due'] ?? '2026-12-01')),
                $d['month'] ?? '12. DEC', $d['desc'] ?? 'COVER WINDOW LIGHT BROKEN',
            ], null, 'A'.($i + 2));
        }
        $z = $book->createSheet()->setTitle('Z');
        $z->fromArray(['DOC NO.', 'STATUS', 'AOC', 'AC REG', 'A+D', 'MONTH DATE', 'FINDING DESCRIPTION', 'PART NUMBER', 'PART DESCRIPTION', 'ORDER NUMBER', 'Remark', 'STATUS PER PN', 'STATUS FINAL'], null, 'A1');
        foreach ($parts as $i => $p) {
            $z->fromArray([$p['doc'], 'OPEN', 'JT', 'PK-LJQ', '', null, '', $p['pn'], 'LENS', null, 'AVAILABLE: 1', 'AVAILABLE', $p['final'] ?? 'AVAILABLE'], null, 'A'.($i + 2));
        }

        $path = tempnam(sys_get_temp_dir(), 'nsrdi').'.xlsx';
        IOFactory::createWriter($book, 'Xlsx')->save($path);

        return $path;
    }

    private function bank(string $number, string $status = 'Closed', ?string $closeDate = '2026-10-10', bool $submitted = true): NsrdiLog
    {
        return NsrdiLog::create(['nsrdi_number' => $number, 'status' => $status, 'close_date' => $closeDate, 'is_submitted' => $submitted, 'aircraft_registration' => 'PK-LJQ', 'description' => 'seat track cover']);
    }

    // ── matching

    public function test_a_document_closes_when_its_mddr_is_closed_in_the_bank_on_the_banks_date(): void
    {
        $this->bank('NLA087482', 'Closed', '2026-10-09', true);
        $path = $this->workbook([['defect' => 'E0400817', 'mddr' => 'NLA087482', 'due' => '2026-10-20']]);

        $report = app(NsrdiOverdueSync::class)->apply($path);

        $row = NsrdiOverdue::where('nsrdi_number', 'E0400817')->firstOrFail();
        $this->assertSame('Closed', $row->status);
        $this->assertSame('2026-10-09', $row->closed_at->toDateString(), 'closed on the day the bank closed it, not on the day of the import');
        $this->assertSame('daily_report', $row->closed_via);
        $this->assertSame('NLA087482', $row->bank_ref);
        $this->assertCount(1, $report['closed_by_bank']);
    }

    public function test_numbers_match_whatever_the_spaces_dashes_and_case(): void
    {
        $this->bank('nla-087482 ');
        $path = $this->workbook([['defect' => 'E1', 'mddr' => 'NLA 087482']]);

        app(NsrdiOverdueSync::class)->apply($path);

        $this->assertSame('Closed', NsrdiOverdue::where('nsrdi_number', 'E1')->value('status'));
    }

    public function test_the_defect_number_matches_too(): void
    {
        $this->bank('E0431077', 'Closed', '2026-10-05', false);
        $path = $this->workbook([['defect' => 'E0431077', 'mddr' => 'NLA074512']]);

        app(NsrdiOverdueSync::class)->apply($path);

        $row = NsrdiOverdue::firstOrFail();
        $this->assertSame('Closed', $row->status);
        $this->assertSame('bank', $row->closed_via, 'not yet archived into the Daily Report');
    }

    public function test_a_bank_record_that_is_still_open_does_not_close_anything(): void
    {
        $this->bank('NLA087482', 'Open', null, false);
        $path = $this->workbook([['defect' => 'E1', 'mddr' => 'NLA087482', 'due' => '2026-10-01']]);

        $report = app(NsrdiOverdueSync::class)->apply($path);

        $row = NsrdiOverdue::firstOrFail();
        $this->assertSame('Open', $row->status);
        $this->assertNull($row->closed_at);
        $this->assertSame(1, $report['past_due'], 'due 1 Oct, today is 14 Oct');
    }

    // ── analysis vs apply

    public function test_analysis_reports_everything_and_writes_nothing(): void
    {
        $this->bank('NLA1');
        NsrdiOverdue::create(['nsrdi_number' => 'OLD-1', 'status' => 'Open', 'due_date' => '2026-11-01']);
        $path = $this->workbook([
            ['defect' => 'E1', 'mddr' => 'NLA1', 'aoc' => 'JT'],
            ['defect' => 'E2', 'mddr' => 'NLA2', 'aoc' => 'ID', 'due' => '2026-10-01'],
            ['defect' => 'E3', 'mddr' => 'NLA3', 'aoc' => 'ID', 'due' => '2026-11-05'],
            ['defect' => 'E3', 'mddr' => 'NLA3'],     // listed twice
        ]);

        $r = app(NsrdiOverdueSync::class)->analyze($path);

        $this->assertSame(0, NsrdiOverdue::where('nsrdi_number', '!=', 'OLD-1')->count(), 'nothing written');
        $this->assertSame(4, $r['rows']);
        $this->assertSame(3, $r['unique']);
        $this->assertSame(1, $r['duplicates']);
        $this->assertSame(3, $r['created']);
        $this->assertCount(1, $r['closed_by_bank']);
        $this->assertSame(2, $r['still_open']);
        $this->assertSame(1, $r['past_due']);
        $this->assertSame(1, $r['due_30']);
        $this->assertSame(['ID' => 2], $r['by_operator']);
        $this->assertSame(1, $r['missing_from_file'], 'OLD-1 is open in the system but no longer in the file');
        $this->assertFalse($r['applied']);
    }

    public function test_importing_again_refreshes_dates_and_never_reopens_a_closed_document(): void
    {
        $first = $this->workbook([['defect' => 'E1', 'mddr' => 'NLA1', 'due' => '2026-11-01'], ['defect' => 'E2', 'mddr' => 'NLA2']]);
        app(NsrdiOverdueSync::class)->apply($first);
        $this->bank('NLA2');   // E2 is closed in the bank afterwards

        $second = $this->workbook([['defect' => 'E1', 'mddr' => 'NLA1', 'due' => '2026-12-15'], ['defect' => 'E2', 'mddr' => 'NLA2']]);
        $report = app(NsrdiOverdueSync::class)->apply($second);

        $this->assertSame(0, $report['created']);
        $this->assertSame('2026-12-15', NsrdiOverdue::where('nsrdi_number', 'E1')->first()->due_date->toDateString(), 'the new file wins for the due date');
        $this->assertSame('Closed', NsrdiOverdue::where('nsrdi_number', 'E2')->value('status'));

        // a third file that still says OPEN does not reopen it
        app(NsrdiOverdueSync::class)->apply($second);
        $this->assertSame('Closed', NsrdiOverdue::where('nsrdi_number', 'E2')->value('status'));
    }

    public function test_the_parts_sheet_adds_the_part_and_its_status(): void
    {
        $path = $this->workbook([['defect' => 'E1', 'mddr' => 'NLA1']], [['doc' => 'E1', 'pn' => '5862-00', 'final' => 'AVAILABLE'], ['doc' => 'E1', 'pn' => 'SECOND', 'final' => 'ON ORDER']]);

        app(NsrdiOverdueSync::class)->apply($path);

        $row = NsrdiOverdue::firstOrFail();
        $this->assertSame('5862-00', $row->part_number);
        $this->assertSame('AVAILABLE', $row->status_final);
    }

    // ── closing later, from the bank

    public function test_a_bank_record_closed_after_the_import_closes_the_open_document(): void
    {
        app(NsrdiOverdueSync::class)->apply($this->workbook([['defect' => 'E1', 'mddr' => 'NLA1']]));
        $this->assertSame('Open', NsrdiOverdue::firstOrFail()->status);

        $this->bank('NLA1', 'Open', null, false)->update(['status' => 'Closed', 'close_date' => '2026-10-13']);

        $row = NsrdiOverdue::firstOrFail();
        $this->assertSame('Closed', $row->status);
        $this->assertSame('2026-10-13', $row->closed_at->toDateString());
        $this->assertSame('NLA1', $row->bank_ref);
    }

    public function test_rows_closed_by_an_older_import_get_the_banks_date_and_reason(): void
    {
        NsrdiOverdue::create(['nsrdi_number' => 'E1', 'mddr' => 'NLA1', 'status' => 'Closed', 'closed_at' => '2026-10-14 08:00:00']);
        $this->bank('NLA1', 'Closed', '2026-10-02');

        $n = app(NsrdiOverdueSync::class)->tagClosedFromBank();

        $this->assertSame(1, $n);
        $this->assertSame('2026-10-02', NsrdiOverdue::firstOrFail()->closed_at->toDateString());
    }

    // ── the page

    public function test_the_page_analyzes_first_then_imports_and_shows_the_open_tab_with_days_to_due(): void
    {
        $this->bank('NLA1', 'Closed', '2026-10-09');
        $path = $this->workbook([
            ['defect' => 'E1', 'mddr' => 'NLA1'],
            ['defect' => 'E2', 'mddr' => 'NLA2', 'due' => '2026-10-10'],
            ['defect' => 'E3', 'mddr' => 'NLA3', 'due' => '2026-10-20'],
        ]);
        $file = UploadedFile::fake()->createWithContent('weekly.xlsx', file_get_contents($path));

        $page = Livewire::actingAs($this->admin())->test(Index::class)
            ->set('isImportModalOpen', true)->set('file', $file)
            ->call('analyzeFile')->assertHasNoErrors();

        $this->assertSame(3, $page->get('analysis')['unique']);
        $this->assertSame(0, NsrdiOverdue::count(), 'analysis does not save');

        $page->call('importData')->assertHasNoErrors();
        $this->assertTrue($page->get('importDone'));
        $this->assertSame(3, NsrdiOverdue::count());

        $page->call('closeImportModal')->call('setTab', 'open')
            ->assertSee('E2')->assertSee('terlambat 4 hari')
            ->assertSee('E3')->assertSee('6 hari lagi')
            ->assertDontSee('E1');

        $page->call('setTab', 'closed')->assertSee('E1')->assertSee('Daily Report')->assertSee('NLA1');
    }

    public function test_a_file_that_is_not_the_weekly_list_is_reported_not_crashed(): void
    {
        $book = new Spreadsheet;
        $book->getActiveSheet()->fromArray([['foo', 'bar'], [1, 2]]);
        $path = tempnam(sys_get_temp_dir(), 'bad').'.xlsx';
        IOFactory::createWriter($book, 'Xlsx')->save($path);
        $file = UploadedFile::fake()->createWithContent('bad.xlsx', file_get_contents($path));

        $page = Livewire::actingAs($this->admin())->test(Index::class)->set('file', $file)->call('analyzeFile');

        $this->assertSame(0, $page->get('analysis')['unique']);
        $this->assertSame(0, NsrdiOverdue::count());
    }
}
