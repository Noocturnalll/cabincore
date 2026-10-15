<?php

namespace Tests\Feature;

use App\Helpers\RoleHelper;
use App\Livewire\Modules\Compliance\Index;
use App\Models\ComplianceEntry;
use App\Models\RosterEntry;
use App\Models\User;
use App\Services\Compliance\ComplianceReport;
use Carbon\Carbon;
use Database\Seeders\RegistryPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/** Briefing / attendant list / 5R for AIEC, Cabin Maintenance, Aircraft Painting and Tambahan, with evidence uploaded in the app. */
class ComplianceUnitsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-14 10:00:00');
        Storage::fake('public');
        $this->seed(RegistryPermissionSeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function user(string $role, string $station = 'CGK'): User
    {
        $u = User::factory()->create(['is_default_password' => false, 'status' => 'active', 'station' => $station]);
        $u->assignRole($role);

        return $u;
    }

    private function roster(string $team, string $station, string $date, string $shift, int $people = 2): void
    {
        for ($i = 0; $i < $people; $i++) {
            RosterEntry::insert(['work_date' => $date, 'station' => $station, 'employee_id' => "$team$station$date$shift$i", 'team' => $team, 'shift' => $shift, 'shift_code' => 'X', 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    private function entry(array $attrs): ComplianceEntry
    {
        return ComplianceEntry::create($attrs + ['entry_id' => 'T'.uniqid(), 'unit' => 'AIEC', 'station' => 'CGK', 'work_date' => '2026-10-13', 'shift' => 'Pagi']);
    }

    // ── what is expected

    public function test_cabin_maintenance_and_painting_expect_the_shifts_their_team_works(): void
    {
        $this->roster('CBM', 'CGK', '2026-10-13', 'PAGI');
        $this->roster('CBM', 'CGK', '2026-10-13', 'MALAM');
        $this->roster('IRREG', 'SUB', '2026-10-13', 'PAGI');      // Irreg is part of Cabin Maintenance
        $this->roster('PAINTING', 'CGK', '2026-10-13', 'PAGI');
        $this->roster('AIEC', 'CGK', '2026-10-13', 'PAGI');       // not theirs

        $report = new ComplianceReport;
        $cbm = $report->build(Carbon::parse('2026-10-13'), Carbon::parse('2026-10-14'), null, 'CBM');
        $painting = $report->build(Carbon::parse('2026-10-13'), Carbon::parse('2026-10-14'), null, 'PAINTING');

        $this->assertSame(2, $cbm['stations']['CGK']['expected']);
        $this->assertSame(1, $cbm['stations']['SUB']['expected']);
        $this->assertSame(3, $cbm['total']['expected']);
        $this->assertSame(1, $painting['total']['expected']);
        $this->assertSame(['CGK'], array_keys($painting['stations']));
    }

    public function test_extra_has_no_target_and_only_lists_what_was_uploaded(): void
    {
        $this->entry(['unit' => 'EXTRA', 'station' => 'DPS', 'url_brf' => 'u', 'url_att' => 'u', 'url_5r' => 'u']);
        $this->entry(['unit' => 'EXTRA', 'station' => 'DPS', 'shift' => 'Malam', 'url_brf' => 'u']);

        $report = (new ComplianceReport)->build(Carbon::parse('2026-10-01'), Carbon::parse('2026-10-14'), null, 'EXTRA');

        $this->assertSame(0, $report['total']['expected']);
        $this->assertSame(2, $report['stations']['DPS']['submitted']);
        $this->assertSame(1, $report['stations']['DPS']['complete']);
        $this->assertNull($report['stations']['DPS']['percent']);
    }

    public function test_the_sheet_and_an_upload_for_the_same_shift_combine_their_documents(): void
    {
        $this->entry(['entry_id' => 'CGK_2026-10-13_Pagi', 'url_brf' => 'sheet-brf', 'url_att' => 'sheet-att']);
        $this->entry(['entry_id' => 'WEB|AIEC|CGK|2026-10-13|Pagi', 'url_5r' => 'web-5r']);

        $report = (new ComplianceReport)->build(Carbon::parse('2026-10-13'), Carbon::parse('2026-10-14'), ['CGK'], 'AIEC');

        $this->assertSame(1, $report['stations']['CGK']['complete'], 'three documents from two rows make one complete report');
    }

    // ── uploading

    public function test_a_pic_uploads_evidence_for_their_unit_and_it_becomes_the_documents_link(): void
    {
        $this->roster('CBM', 'CGK', '2026-10-13', 'PAGI');
        $pic = $this->user(RoleHelper::PIC_CBM);

        Livewire::actingAs($pic)->test(Index::class)
            ->set('unit', 'CBM')->call('pick', 'CGK')
            ->set('files.2026-10-13_Pagi_brf', UploadedFile::fake()->image('briefing.jpg'))
            ->assertHasNoErrors();

        $entry = ComplianceEntry::where('unit', 'CBM')->firstOrFail();
        $this->assertSame('CGK', $entry->station);
        $this->assertSame('2026-10-13', $entry->work_date->toDateString());
        $this->assertSame('Pagi', $entry->shift);
        $this->assertSame($pic->id, $entry->uploaded_by);
        $this->assertNotNull($entry->url_brf);
        $this->assertNull($entry->url_att);
        Storage::disk('public')->assertExists(ltrim(parse_url($entry->url_brf, PHP_URL_PATH), '/storage/'));
    }

    public function test_all_three_documents_make_the_shift_complete(): void
    {
        $this->roster('PAINTING', 'CGK', '2026-10-13', 'PAGI');
        $page = Livewire::actingAs($this->user(RoleHelper::PIC_PAINTING))->test(Index::class)->set('unit', 'PAINTING')->call('pick', 'CGK');

        foreach (['brf', 'att', '5r'] as $doc) {
            $page->set("files.2026-10-13_Pagi_$doc", UploadedFile::fake()->image("$doc.png"));
        }

        $this->assertTrue(ComplianceEntry::where('unit', 'PAINTING')->firstOrFail()->isComplete());
        $report = (new ComplianceReport)->build(Carbon::parse('2026-10-13'), Carbon::parse('2026-10-14'), null, 'PAINTING');
        $this->assertSame(100.0, $report['total']['percent']);
    }

    public function test_a_role_cannot_upload_for_another_unit(): void
    {
        $this->roster('CBM', 'CGK', '2026-10-13', 'PAGI');

        Livewire::actingAs($this->user(RoleHelper::PIC_PAINTING))->test(Index::class)
            ->set('unit', 'CBM')->call('pick', 'CGK')
            ->set('files.2026-10-13_Pagi_brf', UploadedFile::fake()->image('briefing.jpg'))
            ->assertForbidden();

        $this->assertSame(0, ComplianceEntry::count());
    }

    public function test_manager_can_upload_for_any_unit(): void
    {
        $this->roster('CBM', 'CGK', '2026-10-13', 'PAGI');

        Livewire::actingAs($this->user(RoleHelper::MANAGER))->test(Index::class)
            ->set('unit', 'CBM')->call('pick', 'CGK')
            ->set('files.2026-10-13_Pagi_att', UploadedFile::fake()->image('att.jpg'));

        $this->assertNotNull(ComplianceEntry::firstOrFail()->url_att);
    }

    public function test_only_photos_and_pdf_within_the_size_limit_are_accepted(): void
    {
        $this->roster('CBM', 'CGK', '2026-10-13', 'PAGI');
        $page = Livewire::actingAs($this->user(RoleHelper::PIC_CBM))->test(Index::class)->set('unit', 'CBM')->call('pick', 'CGK');

        $page->set('files.2026-10-13_Pagi_brf', UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload'));
        $page->set('files.2026-10-13_Pagi_att', UploadedFile::fake()->create('huge.pdf', 9 * 1024, 'application/pdf'));
        $this->assertSame(0, ComplianceEntry::count());

        $page->set('files.2026-10-13_Pagi_5r', UploadedFile::fake()->create('5r.pdf', 200, 'application/pdf'));
        $this->assertNotNull(ComplianceEntry::firstOrFail()->url_5r);
    }

    public function test_a_shift_that_is_not_on_the_roster_cannot_receive_evidence(): void
    {
        $this->roster('CBM', 'CGK', '2026-10-13', 'PAGI');

        Livewire::actingAs($this->user(RoleHelper::PIC_CBM))->test(Index::class)
            ->set('unit', 'CBM')->call('pick', 'CGK')
            ->set('files.2026-10-13_Malam_brf', UploadedFile::fake()->image('b.jpg'));

        $this->assertSame(0, ComplianceEntry::count());
    }

    public function test_todays_shift_can_be_uploaded_even_though_it_is_not_counted_yet(): void
    {
        $this->roster('CBM', 'CGK', '2026-10-14', 'PAGI');

        Livewire::actingAs($this->user(RoleHelper::PIC_CBM))->test(Index::class)
            ->set('unit', 'CBM')->call('pick', 'CGK')
            ->set('files.2026-10-14_Pagi_brf', UploadedFile::fake()->image('b.jpg'));

        $this->assertNotNull(ComplianceEntry::firstOrFail()->url_brf);
        $report = (new ComplianceReport)->build(Carbon::parse('2026-10-14'), Carbon::parse('2026-10-14'), null, 'CBM');
        $this->assertSame(0, $report['total']['expected'], 'today is not expected yet');
    }

    public function test_evidence_can_be_removed_and_replaced(): void
    {
        $this->roster('CBM', 'CGK', '2026-10-13', 'PAGI');
        $page = Livewire::actingAs($this->user(RoleHelper::PIC_CBM))->test(Index::class)->set('unit', 'CBM')->call('pick', 'CGK');

        $page->set('files.2026-10-13_Pagi_brf', UploadedFile::fake()->image('first.jpg'));
        $first = ComplianceEntry::firstOrFail()->url_brf;

        $page->set('files.2026-10-13_Pagi_brf', UploadedFile::fake()->image('second.jpg'));
        $second = ComplianceEntry::firstOrFail()->url_brf;
        $this->assertNotSame($first, $second, 'the new file replaced the old link');
        $this->assertCount(1, Storage::disk('public')->allFiles(), 'the replaced file is deleted, not left behind');

        $page->call('removeDoc', ComplianceEntry::firstOrFail()->id, 'brf');
        $this->assertNull(ComplianceEntry::firstOrFail()->url_brf);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_tambahan_takes_free_lines_with_their_own_evidence(): void
    {
        $page = Livewire::actingAs($this->user(RoleHelper::PIC_SUPPORTING))->test(Index::class)
            ->set('unit', 'EXTRA')
            ->set('extraStation', 'dps')->set('extraDate', '2026-10-12')->set('extraShift', 'Malam')->set('extraNote', 'Audit mendadak')
            ->call('addExtra')->assertHasNoErrors();

        $entry = ComplianceEntry::where('unit', 'EXTRA')->firstOrFail();
        $this->assertSame('DPS', $entry->station);
        $this->assertSame('Audit mendadak', $entry->note);

        // adding a line opens its station; the evidence goes to that line
        $page->set('files.e'.$entry->id.'_5r', UploadedFile::fake()->image('5r.jpg'));
        $this->assertNotNull($entry->fresh()->url_5r);
    }

    public function test_tambahan_rejects_a_future_date(): void
    {
        Livewire::actingAs($this->user(RoleHelper::PIC_SUPPORTING))->test(Index::class)
            ->set('unit', 'EXTRA')->set('extraStation', 'CGK')->set('extraDate', '2026-10-20')->set('extraShift', 'Pagi')
            ->call('addExtra')->assertHasErrors(['extraDate']);

        $this->assertSame(0, ComplianceEntry::count());
    }

    // ── page

    public function test_every_unit_tab_opens_and_the_sidebar_says_cabin_technician(): void
    {
        $manager = $this->user(RoleHelper::MANAGER);

        foreach (['AIEC', 'CBM', 'PAINTING', 'EXTRA'] as $unit) {
            $this->actingAs($manager)->get('/compliance/daily?unit='.$unit)->assertOk();
        }

        $this->actingAs($manager)->get('/compliance/daily')->assertSee('Cabin Technician')->assertSee('Aircraft Painting')->assertSee('Tambahan');
    }

    public function test_the_existing_sheet_sync_still_lands_in_aiec(): void
    {
        $this->entry(['entry_id' => 'CGK_2026-10-13_Pagi', 'url_brf' => 'u', 'url_att' => 'u', 'url_5r' => 'u']);

        $report = (new ComplianceReport)->build(Carbon::parse('2026-10-13'), Carbon::parse('2026-10-13'), ['CGK']);   // default unit
        $this->assertSame(1, $report['stations']['CGK']['complete']);
    }
}
