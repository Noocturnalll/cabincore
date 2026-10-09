<?php

namespace Tests\Feature;

use App\Helpers\RoleHelper;
use App\Livewire\Modules\LeaderReport\Index;
use App\Livewire\Traits\ManagesLogStatus;
use App\Models\CmlLog;
use App\Models\DailyJobAssignment;
use App\Models\DmiLog;
use App\Models\LeaderReportImport;
use App\Models\NsrdiLog;
use App\Models\User;
use App\Models\WoLog;
use App\Services\Dja\DjaClassifier;
use App\Services\Leader\LeaderReportParser;
use App\Services\Leader\LeaderReportReconciler;
use App\Services\Leader\UnplannedSheetWriter;
use Carbon\Carbon;
use Database\Seeders\RegistryPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class LeaderReportTest extends TestCase
{
    use RefreshDatabase;

    private function xlsx(array $sheets): string
    {
        $book = new Spreadsheet;
        $book->removeSheetByIndex(0);
        foreach ($sheets as $title => $rows) {
            $ws = $book->createSheet();
            $ws->setTitle($title);
            foreach ($rows as $r => $row) {
                foreach (array_values($row) as $c => $value) {
                    $ws->setCellValue([$c + 1, $r + 1], $value);
                }
            }
        }
        $path = tempnam(sys_get_temp_dir(), 'lr').'.xlsx';
        (new Xlsx($book))->save($path);

        return $path;
    }

    private function dja(string $reg = 'PK-AAA'): DailyJobAssignment
    {
        return DailyJobAssignment::create(['aircraft_registration' => $reg, 'date' => '2026-10-08', 'job_type' => 'R01/WO', 'station' => 'CGK', 'task_id' => 'T-'.uniqid(), 'source_spreadsheet_id' => null]);
    }

    private function import(array $sheets, string $date = '2026-10-08'): LeaderReportImport
    {
        $rows = (new LeaderReportParser)->parse($this->xlsx($sheets), Carbon::parse($date));
        $import = LeaderReportImport::create(['file_name' => 'x.xlsx', 'report_date' => $date]);
        foreach ($rows as $r) {
            $import->rows()->create($r);
        }

        return $import;
    }

    public function test_reason_codes_match_the_status_dialog(): void
    {
        $trait = (new \ReflectionClass(ManagesLogStatus::class))->getConstant('REASON_CODES');
        $this->assertSame($trait, LeaderReportReconciler::REASON_CODES);
    }

    public function test_parser_reads_daily_workbook_style_sheets_and_merges_wo_lines(): void
    {
        $path = $this->xlsx([
            'SUMMARY' => [['NO', 'STA'], [1, 'CGK']],
            'WO' => [
                ['ALL GROUP LEADER'],
                ['DATE', 'WG', 'AC REG', 'WO', 'WO DESCRIPTION', 'MAN HOURS', 'PLAN STA', 'STATUS'],
                [46303, 'WG 01', 'pk-lor', 2332898, 'LIFE VEST', '2,00', 'BPN', 'CLOSED'],
                [46303, 'WG 01', 'PK-LOR', 2332898.0, 'RECORD', '0,25', 'BPN', 'CLOSED'],
            ],
            'UNPLANNED' => [
                ['DATE', 'AOC', 'REG', 'DOC TYPE', 'NO. DOC', 'DEFFECT DESCRIPTION', 'STA', 'STATUS'],
                [46303, 'SAJ', 'PK-SAK', 'NSRDI', 'NSAJ026764', 'HEADRACK', 'CGK', 'CLOSED'],
                [46303, 'SAJ', 'PK-SAK', 'XYZ', '55', 'unknown type', 'CGK', 'CLOSED'],
            ],
        ]);

        $rows = collect((new LeaderReportParser)->parse($path, Carbon::parse('2026-10-08')))->keyBy(fn ($r) => ($r['doc_type'] ?? '?').'|'.$r['doc_no']);

        $this->assertCount(3, $rows);                                  // SUMMARY ignored, WO lines merged
        $wo = $rows['WO|2332898'];
        $this->assertSame('PK-LOR', $wo['aircraft_registration']);
        $this->assertEquals(2.25, $wo['man_hour']);
        $this->assertSame('Closed', $wo['status']);
        $this->assertSame('NSRDI', $rows['NSRDI|NSAJ026764']['doc_type']);
        $this->assertArrayHasKey('?|55', $rows->all());
    }

    public function test_parser_computes_man_hours_from_man_power_and_times_including_overnight(): void
    {
        $rows = (new LeaderReportParser)->parse($this->xlsx(['LAPORAN' => [
            ['TANGGAL', 'AC REG', 'NO DOC', 'DOC TYPE', 'STATUS', 'MP', 'START', 'FINISH'],
            ['2026-10-08', 'PK-AAA', 'C001', 'CML', 'CLOSED', 3, '08:00', '10:30'],
            ['2026-10-08', 'PK-AAB', 'C002', 'CML', 'CLOSED', 2, '22.00', '02:00'],
            ['2026-10-08', 'PK-AAC', 'C003', 'CML', 'CLOSED', 2, 0.3333333333, 0.375],   // Excel times 08:00 - 09:00
        ]]), Carbon::parse('2026-10-08'));

        $byDoc = collect($rows)->keyBy('doc_no');
        $this->assertEquals(7.5, $byDoc['C001']['man_hour']);          // 3 x 2.5h
        $this->assertEquals(8.0, $byDoc['C002']['man_hour']);          // 2 x 4h, finish after midnight
        $this->assertSame('2026-10-09 02:00:00', $byDoc['C002']['end_at']);
        $this->assertEquals(2.0, $byDoc['C003']['man_hour']);
        $this->assertSame(3, $byDoc['C001']['man_power']);
    }

    public function test_document_in_dja_is_closed_and_unknown_document_becomes_unplanned(): void
    {
        $dja = $this->dja();
        $planned = WoLog::create(['dja_id' => $dja->id, 'aircraft_registration' => 'PK-AAA', 'wo_number' => '2332898', 'status' => 'Open', 'date' => '2026-10-08', 'hold_reason_category' => 'MP', 'hold_remarks' => 'tunggu MP']);
        $dmiPlanned = DmiLog::create(['dja_id' => $this->dja('PK-AAB')->id, 'aircraft_registration' => 'PK-AAB', 'dmi_number' => 'SAJ1', 'status' => 'Open', 'date' => '2026-10-08']);

        $import = $this->import([
            'LAPORAN' => [
                ['DATE', 'AC REG', 'DOC TYPE', 'NO DOC', 'STATUS', 'MP', 'START', 'END', 'WO DESCRIPTION', 'STA'],
                ['2026-10-08', 'PK-AAA', 'WO', 2332898, 'CLOSED', 2, '08:00', '10:00', 'LIFE VEST', 'CGK'],
                ['2026-10-08', 'PK-AAB', 'DMI', 'SAJ1', 'CLOSED', 1, '09:00', '09:30', 'LAMP', 'CGK'],
                ['2026-10-08', 'PK-ZZZ', 'WO', '999111', 'CLOSED', 2, '11:00', '12:00', 'LIFE VEST REPLACEMENT', 'HLP'],
                ['2026-10-08', 'PK-ZZY', 'NSRDI', 'NSX1', 'CLOSED', 2, '11:00', '12:00', 'NSRDI LUAR DJA', 'HLP'],
                ['2026-10-08', 'PK-ZZX', 'CML', 'C77', 'CLOSED', 4, '13:00', '14:00', 'CML', 'KNO'],
            ],
        ]);

        $reconciler = app(LeaderReportReconciler::class);
        $plan = $reconciler->plan($import);
        $this->assertSame(2, $plan['closed_planned']);
        $this->assertSame(2, $plan['unplanned_new']);
        $this->assertSame(1, $plan['cml_new']);
        $this->assertSame('Open', $planned->fresh()->status, 'preview must not change data');
        $this->assertSame(0, WoLog::whereNull('dja_id')->count());

        $reconciler->apply($import);

        $closed = $planned->fresh();
        $this->assertSame('Closed', $closed->status);
        $this->assertNull($closed->hold_reason_category);
        $this->assertSame(2, $closed->man_power);
        $this->assertEquals(4.0, $closed->man_hour);
        $this->assertSame('Closed', $dmiPlanned->fresh()->status);
        $this->assertEquals(0.5, $dmiPlanned->fresh()->man_hour);

        $unplannedWo = WoLog::where('wo_number', '999111')->first();
        $this->assertNull($unplannedWo->dja_id);
        $this->assertSame('Closed', $unplannedWo->status);
        $this->assertEquals(2.0, $unplannedWo->man_hour);
        $this->assertSame('NSX1', NsrdiLog::whereNull('dja_id')->value('nsrdi_number'));
        $this->assertEquals(8.0, CmlLog::where('no_doc', 'C77')->value('man_hour') + 4.0);   // 4 MP x 1h = 4h
        $this->assertSame('applied', $import->fresh()->status);
    }

    public function test_reimport_updates_instead_of_duplicating(): void
    {
        $sheets = ['LAPORAN' => [
            ['DATE', 'AC REG', 'DOC TYPE', 'NO DOC', 'STATUS', 'MP', 'START', 'END'],
            ['2026-10-08', 'PK-ZZZ', 'WO', '999111', 'CLOSED', 2, '11:00', '12:00'],
            ['2026-10-08', 'PK-ZZX', 'CML', 'C77', 'CLOSED', 4, '13:00', '14:00'],
        ]];
        $reconciler = app(LeaderReportReconciler::class);

        $reconciler->apply($first = $this->import($sheets));
        $second = $this->import($sheets);
        $plan = $reconciler->plan($second);

        $this->assertSame(1, $plan['unplanned_update']);
        $this->assertSame(1, $plan['cml_update']);
        $reconciler->apply($second);
        $this->assertSame(1, WoLog::where('wo_number', '999111')->count());
        $this->assertSame(1, CmlLog::where('no_doc', 'C77')->count());
    }

    public function test_open_report_never_closes_dja_and_needs_valid_reason_to_reopen(): void
    {
        $log = WoLog::create(['dja_id' => $this->dja()->id, 'aircraft_registration' => 'PK-AAA', 'wo_number' => '111', 'status' => 'Open', 'date' => '2026-10-08']);
        $import = $this->import(['L' => [
            ['DATE', 'AC REG', 'WO', 'STATUS', 'MP', 'START', 'END', 'REASON OPEN', 'CODE OPEN'],
            ['2026-10-08', 'PK-AAA', '111', 'OPEN', 2, '08:00', '09:00', 'Tunggu part', 'XX'],
        ]]);

        $plan = app(LeaderReportReconciler::class)->plan($import);
        $this->assertSame(1, $plan['updated_planned']);
        $this->assertStringContainsString('tidak diubah', $import->rows()->first()->note);

        app(LeaderReportReconciler::class)->apply($import);
        $log = $log->fresh();
        $this->assertSame('Open', $log->status);
        $this->assertNull($log->hold_reason_category);
        $this->assertEquals(2.0, $log->man_hour);                 // hours are still recorded
    }

    public function test_page_requires_permission_and_runs_the_two_steps(): void
    {
        $this->seed(RegistryPermissionSeeder::class);
        $nobody = User::factory()->create(['is_default_password' => false, 'status' => 'active']);
        $this->actingAs($nobody)->get('/modules/leader-report')->assertForbidden();

        $pic = User::factory()->create(['is_default_password' => false, 'status' => 'active']);
        $pic->assignRole(RoleHelper::PIC_CABIN);
        $this->actingAs($pic)->get('/modules/leader-report')->assertOk();

        $path = $this->xlsx(['L' => [
            ['DATE', 'AC REG', 'DOC TYPE', 'NO DOC', 'STATUS', 'MP', 'START', 'END'],
            ['2026-10-08', 'PK-ZZX', 'CML', 'C99', 'CLOSED', 2, '08:00', '09:00'],
        ]]);
        $file = UploadedFile::fake()->createWithContent('laporan.xlsx', file_get_contents($path));

        $c = Livewire::actingAs($pic)->test(Index::class)
            ->set('reportDate', '2026-10-08')->set('file', $file)->call('readFile')->assertHasNoErrors();
        $this->assertSame(0, CmlLog::count());
        $c->call('apply');
        $this->assertSame(1, CmlLog::where('no_doc', 'C99')->count());
    }

    public function test_unplanned_nsrdi_gets_painting_or_cbm_category_from_description(): void
    {
        $c = new DjaClassifier;
        foreach ([
            'PAINT PEEL OFF AT WING TO BODY FAIRING', 'PPO ON FWD CARGO DOOR', 'CAT PESAWAT LUNTUR',
            'EXTERIOR PLACARD MISSING AT ENTRY DOOR', 'LOGO WINGS AT BODY FUSELAGE FADED',
        ] as $text) {
            $this->assertSame('PAINTING', $c->inferNsrdiCategory($text), $text);
        }
        foreach (['HEADRACK LATCH BROKEN', 'PLACARD NO SMOKING IN LAVATORY', 'SEAT COVER TORN'] as $text) {
            $this->assertSame('CBM', $c->inferNsrdiCategory($text), $text);
        }
        $this->assertNull($c->inferNsrdiCategory(''));

        $import = $this->import(['L' => [
            ['DATE', 'AC REG', 'DOC TYPE', 'NO DOC', 'STATUS', 'DESCRIPTION'],
            ['2026-10-08', 'PK-ZZZ', 'NSRDI', 'NP1', 'CLOSED', 'PAINT PEEL OFF AT RADOME'],
            ['2026-10-08', 'PK-ZZY', 'NSRDI', 'NP2', 'CLOSED', 'ARMREST BROKEN'],
        ]]);
        app(LeaderReportReconciler::class)->apply($import);

        $this->assertSame('PAINTING', NsrdiLog::where('nsrdi_number', 'NP1')->value('category'));
        $this->assertSame('CBM', NsrdiLog::where('nsrdi_number', 'NP2')->value('category'));
    }

    public function test_category_certainty_and_correction_in_preview(): void
    {
        $c = new DjaClassifier;
        $this->assertSame(['category' => 'PAINTING', 'check' => false], $c->nsrdiCategory('PPO AT WING'));
        $this->assertSame(['category' => 'PAINTING', 'check' => true], $c->nsrdiCategory('CAT SUDAH KUSAM'));
        $this->assertSame(['category' => 'CBM', 'check' => true], $c->nsrdiCategory('PLACARD NO SMOKING'));
        $this->assertSame(['category' => 'CBM', 'check' => false], $c->nsrdiCategory('ARMREST BROKEN'));

        $this->seed(RegistryPermissionSeeder::class);
        $pic = User::factory()->create(['is_default_password' => false, 'status' => 'active']);
        $pic->assignRole(RoleHelper::PIC_PAINTING);

        $import = $this->import(['L' => [
            ['DATE', 'AC REG', 'DOC TYPE', 'NO DOC', 'STATUS', 'DESCRIPTION'],
            ['2026-10-08', 'PK-ZZZ', 'NSRDI', 'NQ1', 'CLOSED', 'CAT SUDAH KUSAM'],
        ]]);
        app(LeaderReportReconciler::class)->plan($import);
        $row = $import->rows()->first();
        $this->assertSame('PAINTING', $row->category);
        $this->assertTrue((bool) $row->category_check);

        Livewire::actingAs($pic)->test(Index::class)->set('importId', $import->id)->call('setCategory', $row->id, 'CBM');
        $row->refresh();
        $this->assertSame('CBM', $row->category);
        $this->assertFalse((bool) $row->category_check);

        app(LeaderReportReconciler::class)->apply($import);
        $this->assertSame('CBM', NsrdiLog::where('nsrdi_number', 'NQ1')->value('category'), 'the corrected category must survive apply');
    }

    public function test_unplanned_documents_are_sent_to_the_sheet_writer_but_dja_ones_are_not(): void
    {
        WoLog::create(['dja_id' => $this->dja()->id, 'aircraft_registration' => 'PK-AAA', 'wo_number' => '555', 'status' => 'Open', 'date' => '2026-10-08']);

        $fake = new class extends UnplannedSheetWriter
        {
            public array $sent = [];

            public function __construct() {}

            public function upsert(string $type, array $row): ?bool
            {
                $this->sent[] = [$type, $row['doc_no']];

                return $row['doc_no'] === 'FAILME' ? false : true;
            }
        };
        $this->app->instance(UnplannedSheetWriter::class, $fake);

        $import = $this->import(['L' => [
            ['DATE', 'AC REG', 'DOC TYPE', 'NO DOC', 'STATUS'],
            ['2026-10-08', 'PK-AAA', 'WO', '555', 'CLOSED'],          // in DJA
            ['2026-10-08', 'PK-ZZZ', 'WO', '777', 'CLOSED'],
            ['2026-10-08', 'PK-ZZY', 'DMI', 'D1', 'CLOSED'],
            ['2026-10-08', 'PK-ZZX', 'NSRDI', 'FAILME', 'CLOSED'],
            ['2026-10-08', 'PK-ZZW', 'CML', 'C1', 'CLOSED'],          // CML stays CML
        ]]);
        $stats = app(LeaderReportReconciler::class)->apply($import);

        $this->assertEqualsCanonicalizing([['WO', '777'], ['DMI', 'D1'], ['NSRDI', 'FAILME']], $fake->sent);
        $this->assertSame(2, $stats['unplanned_sheet_written']);
        $this->assertSame(1, $stats['unplanned_sheet_failed']);
        $this->assertSame(1, NsrdiLog::where('nsrdi_number', 'FAILME')->count(), 'a sheet failure must not lose the CBM record');
    }

    public function test_unplanned_wo_and_dmi_are_split_into_cbm_and_line_maintenance(): void
    {
        $c = new DjaClassifier;
        $this->assertSame('CBM', $c->classify('wo', '25', null, 'x')['action'] === 'accept' ? 'CBM' : 'LINE');

        $import = $this->import(['L' => [
            ['DATE', 'AC REG', 'DOC TYPE', 'NO DOC', 'STATUS', 'DESCRIPTION', 'ATA'],
            ['2026-10-08', 'PK-ZZ1', 'WO', 'W1', 'CLOSED', 'LIFE VEST REPLACEMENT', ''],          // keyword  -> CBM
            ['2026-10-08', 'PK-ZZ2', 'WO', 'W2', 'CLOSED', 'REPLACE ENGINE OIL FILTER', '79'],    // no match -> Line
            ['2026-10-08', 'PK-ZZ3', 'WO', 'W3', 'CLOSED', 'CHECK ANYTHING', '25'],               // ATA 25   -> CBM
            ['2026-10-08', 'PK-ZZ4', 'DMI', 'D2', 'CLOSED', 'HYDRAULIC LEAK AT NLG', '29'],       // no match -> Line
            ['2026-10-08', 'PK-ZZ5', 'DMI', 'D3', 'CLOSED', 'SEAT BELT ENGINE NOISE', ''],        // conflict -> CBM, check
            ['2026-10-08', 'PK-ZZ6', 'WO', 'W4', 'CLOSED', 'LANDING GEAR WHEEL', '32'],           // ATA 32 rejected -> Line
        ]]);

        $reconciler = app(LeaderReportReconciler::class);
        $plan = $reconciler->plan($import);
        $byDoc = $import->rows()->get()->keyBy('doc_no');

        $this->assertSame(3, $plan['unplanned_new']);
        $this->assertSame(3, $plan['line_maintenance']);
        $this->assertSame('CBM', $byDoc['W1']->category);
        $this->assertSame('line_maintenance', $byDoc['W2']->outcome);
        $this->assertSame('line_maintenance', $byDoc['W4']->outcome);
        $this->assertSame('unplanned_new', $byDoc['W3']->outcome);
        $this->assertSame('line_maintenance', $byDoc['D2']->outcome);
        $this->assertSame('CBM', $byDoc['D3']->category);
        $this->assertTrue((bool) $byDoc['D3']->category_check);

        // The person overrules W2: it is cabin work after all
        $this->seed(RegistryPermissionSeeder::class);
        $pic = User::factory()->create(['is_default_password' => false, 'status' => 'active']);
        $pic->assignRole(RoleHelper::PIC_CABIN);
        Livewire::actingAs($pic)->test(Index::class)->set('importId', $import->id)->call('setCategory', $byDoc['W2']->id, 'CBM');
        $this->assertSame('unplanned_new', $byDoc['W2']->fresh()->outcome);

        $reconciler->apply($import);

        $this->assertSame(['W1', 'W2', 'W3'], WoLog::whereNull('dja_id')->orderBy('wo_number')->pluck('wo_number')->all());
        $this->assertSame(0, WoLog::where('wo_number', 'W4')->count(), 'line maintenance is not saved as CBM');
        $this->assertSame(['D3'], DmiLog::pluck('dmi_number')->all());
    }

    public function test_parser_reads_the_lgt_closing_layout_with_named_mp_columns(): void
    {
        $rows = (new LeaderReportParser)->parse($this->xlsx(['LGT CLOSINGAN' => [
            ['DATE', 'OPERATOR', 'A/C REG', 'STA', 'STATUS', 'DOC TYPE', 'NO. DOC', 'ACTION TAKEN / REASON', 'START PERFORM', 'FINISH PERFORM', 'MP 1', 'MP 2', 'MP 3', 'Total MP'],
            ['2026-10-08', 'BATIK AIR', 'PK-LUW', 'UPG', 'CLOSED', 'CML', 'B80823', 'CLEAN UP FILTER', 0.35416666666667, 0.38194444444444, 'ARFAN', 'RIZAL', '', ''],
            ['2026-10-08', 'BATIK AIR', 'PK-LUQ', 'UPG', 'CLOSED', 'NSRDI', 'NID1', 'SEAT', '08:00', '09:00', 'ARFAN', '', '', 3],
        ]]), Carbon::parse('2026-10-08'));

        $byDoc = collect($rows)->keyBy('doc_no');
        $this->assertSame(2, $byDoc['B80823']['man_power'], 'names in MP 1..MP n are counted');
        $this->assertEquals(round(2 * 0.0277777778 * 24, 2), $byDoc['B80823']['man_hour']);   // 2 people x 40 min
        $this->assertSame(3, $byDoc['NID1']['man_power'], 'an explicit Total MP wins over the name count');
        $this->assertEquals(3.0, $byDoc['NID1']['man_hour']);
    }

    public function test_sheet_writer_places_values_by_header_name_like_the_planner_tabs(): void
    {
        $w = new UnplannedSheetWriter(null);

        // "Unplaned NSRD" tab: no title over the first column, STATUS, AOC, CATEGORY, ACT STA ...
        $nsrdiHeader = ['', 'WG', 'AC REG', 'NSRDI', 'FINDING DESCRIPTION', 'CATEGORY', 'REPORT DATE', 'AOC', 'TYPE', 'PLAN STA', 'REMARKS', 'STATUS', '', 'ACT STA', 'REASON OPEN', 'CODE OPEN'];
        $map = [];
        foreach ($nsrdiHeader as $i => $name) {
            if ($name !== '') {
                $map[$name] = $i;
            }
        }
        $cols = $w->columns($map, 'NSRDI', $nsrdiHeader);

        $this->assertSame(0, $cols['date'], 'blank first header holds the date');
        $this->assertSame(3, $cols['doc_no']);
        $this->assertSame(4, $cols['description']);
        $this->assertSame(7, $cols['operator']);
        $this->assertSame(11, $cols['status']);

        $cells = $w->cells($cols, 'NSRDI', [
            'work_date' => '2026-10-08', 'doc_no' => 'NP1', 'aircraft_registration' => 'PK-ZZZ', 'description' => 'PAINT PEEL', 'station' => 'CGK',
            'status' => 'Closed', 'category' => 'PAINTING', 'aoc_code' => 'IU', 'fleet' => 'A320', 'wg' => 'WG 08',
        ]);

        $this->assertSame('08-Oct-26', $cells[0]);
        $this->assertSame('WG 08', $cells[1]);
        $this->assertSame('PK-ZZZ', $cells[2]);
        $this->assertSame('NP1', $cells[3]);
        $this->assertSame('PAINTING', $cells[5]);
        $this->assertSame('IU', $cells[7], 'AOC column gets the airline code, not the name');
        $this->assertSame('A320', $cells[8]);
        $this->assertSame('CLOSED', $cells[11]);
        $this->assertSame('CGK', $cells[13]);
    }

    public function test_status_header_depends_on_the_tab_and_tab_names_accept_the_planners_spelling(): void
    {
        $w = new UnplannedSheetWriter(null);

        $dmi = ['REFRESH DATE' => 0, 'REG' => 2, 'DMI NO' => 3, 'STATUS DMI' => 6, 'STATUS WO' => 18, 'CLOSE DATE' => 19, 'ACT STA' => 20, 'ATA CHAPTER' => 9];
        $cols = $w->columns($dmi, 'DMI');
        $this->assertSame(6, $cols['status'], 'DMI tab: STATUS DMI, not STATUS WO');
        $this->assertSame(9, $cols['ata']);

        $wo = ['AC REG' => 2, 'WO' => 3, 'STATUS WO' => 23, 'CLOSE DATE' => 24, 'MAN HOURS' => 9];
        $this->assertSame(23, $w->columns($wo, 'WO')['status']);

        $closed = $w->cells($w->columns($wo, 'WO'), 'WO', ['work_date' => '2026-10-08', 'status' => 'Closed', 'man_hour' => 2.5, 'doc_no' => '1']);
        $this->assertSame('08-Oct-26', $closed[24], 'close date is written when the document is closed');
        $this->assertSame('2.5', $closed[9]);

        $open = $w->cells($w->columns($wo, 'WO'), 'WO', ['work_date' => '2026-10-08', 'status' => 'Open', 'doc_no' => '1']);
        $this->assertSame('', $open[24]);

        $this->assertContains('UNPLANED DMI', UnplannedSheetWriter::TABS['DMI']);
        $this->assertContains('UNPLANED NSRD', UnplannedSheetWriter::TABS['NSRDI']);
    }
}
