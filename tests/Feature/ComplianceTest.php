<?php

namespace Tests\Feature;

use App\Helpers\RoleHelper;
use App\Livewire\Modules\Compliance\Index;
use App\Models\ComplianceEntry;
use App\Models\User;
use App\Services\Compliance\ComplianceReport;
use App\Services\Compliance\ComplianceSyncService;
use App\Services\GoogleSheetsReader;
use Carbon\Carbon;
use Database\Seeders\RegistryPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ComplianceTest extends TestCase
{
    use RefreshDatabase;

    private const HEADER = ['entryId', 'stasiun', 'tanggal', 'Shift', 'url_5r', 'url_att', 'url_brf', 'id_5r', 'id_att', 'id_brf', 'Timestamp'];

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function entry(string $station, string $date, string $shift, array $urls = ['brf' => 'u', 'att' => 'u', '5r' => 'u']): ComplianceEntry
    {
        return ComplianceEntry::create([
            'entry_id' => "{$station}_{$date}_{$shift}", 'station' => $station, 'work_date' => $date, 'shift' => $shift,
            'url_5r' => $urls['5r'] ?? null, 'url_att' => $urls['att'] ?? null, 'url_brf' => $urls['brf'] ?? null,
        ]);
    }

    public function test_sync_stores_rows_and_is_idempotent(): void
    {
        $rows = [
            self::HEADER,
            ['KOE_2026-04-10_Malam', 'KOE', '2026-04-10', 'Malam', '', 'https://d/att', 'https://d/brf', '', 'a', 'b', '11/4/2026, 09.47.58'],
            ['CGK_2026-04-12_Pagi', 'CGK', '2026-04-12', 'Pagi', 'https://d/5r', 'https://d/att', 'https://d/brf', 'x', 'a', 'b', '12/4/2026, 07.01.00'],
            ['', '', '', '', '', '', '', '', '', '', ''],   // blank line ignored
        ];
        $service = new ComplianceSyncService;

        $first = $service->store($rows);
        $this->assertSame(['read' => 2, 'created' => 2, 'updated' => 0], $first);

        $koe = ComplianceEntry::where('entry_id', 'KOE_2026-04-10_Malam')->first();
        $this->assertNull($koe->url_5r);
        $this->assertFalse($koe->isComplete());
        $this->assertSame('2026-04-11 09:47:58', $koe->submitted_at->toDateTimeString());

        $rows[1][4] = 'https://d/5r-late';   // the 5R photo arrives later
        $second = $service->store($rows);
        $this->assertSame(['read' => 2, 'created' => 0, 'updated' => 1], $second);
        $this->assertSame(2, ComplianceEntry::count());
        $this->assertTrue($koe->fresh()->isComplete());
    }

    public function test_sync_reads_the_public_csv_when_there_are_no_credentials(): void
    {
        Http::fake(['docs.google.com/*' => Http::response(
            "entryId,stasiun,tanggal,Shift,url_5r,url_att,url_brf,id_5r,id_att,id_brf,Timestamp\nSUB_2026-10-07_Pagi,SUB,2026-10-07,Pagi,https://d/5,https://d/a,https://d/b,1,2,3,\"8/10/2026, 09.47.58\"\n"
        )]);

        $stats = (new ComplianceSyncService(new class extends GoogleSheetsReader
        {
            public function __construct() {}

            public function isReady(): bool
            {
                return false;
            }
        }))->sync();

        $this->assertSame(1, $stats['created']);
        $this->assertSame('SUB', ComplianceEntry::first()->station);

    }

    public function test_an_html_login_page_is_not_treated_as_data(): void
    {
        Http::fake(['docs.google.com/*' => Http::response('<html>login</html>', 200)]);
        $this->assertNull((new ComplianceSyncService(new class extends GoogleSheetsReader
        {
            public function __construct() {}

            public function isReady(): bool
            {
                return false;
            }
        }))->sync(), 'an HTML login page is not data');
    }

    public function test_report_counts_expected_submitted_and_complete_per_station_shift(): void
    {
        Carbon::setTestNow('2026-10-10 12:00:00');   // today is not counted; 7-10 Oct are

        // SUB: Pagi + Malam. Days 2026-10-07, 08, 09 expected = 6 reports
        $this->entry('SUB', '2026-10-07', 'Pagi');
        $this->entry('SUB', '2026-10-07', 'Malam');
        $this->entry('SUB', '2026-10-08', 'Pagi', ['brf' => 'u', 'att' => 'u']);   // 5R missing
        // 2026-10-08 Malam and all of 10-09 missing
        // KOE: Malam only -> 3 expected, 1 submitted
        $this->entry('KOE', '2026-10-09', 'Malam');
        $this->entry('KOE', '2026-10-10', 'Malam');   // today: shown in the list but not expected

        $report = (new ComplianceReport)->build(Carbon::parse('2026-10-07'), Carbon::parse('2026-10-10'), ['SUB', 'KOE']);

        $sub = $report['stations']['SUB'];
        $this->assertSame(6, $sub['expected']);
        $this->assertSame(3, $sub['submitted']);
        $this->assertSame(2, $sub['complete']);
        $this->assertSame(33.3, $sub['percent']);
        $this->assertSame(['5r'], $sub['incomplete'][0]['lacking']);
        $this->assertCount(3, $sub['missing']);
        $this->assertSame(2, $sub['docs']['5r']);

        $koe = $report['stations']['KOE'];
        $this->assertSame(3, $koe['expected']);
        $this->assertSame(1, $koe['complete']);

        $this->assertSame(9, $report['total']['expected']);
        $this->assertSame(3, $report['total']['complete']);
    }

    public function test_a_station_is_not_expected_before_its_start_date(): void
    {
        Carbon::setTestNow('2026-04-14 08:00:00');

        // CGK starts on 2026-04-11 per config; asking from 04-01 must not count 04-01..04-10
        $report = (new ComplianceReport)->build(Carbon::parse('2026-04-01'), Carbon::parse('2026-04-14'), ['CGK']);

        $this->assertSame(3 * 2, $report['stations']['CGK']['expected']);   // 11, 12, 13 April x 2 shifts
    }

    public function test_page_access_and_station_scope(): void
    {
        $this->seed(RegistryPermissionSeeder::class);
        Carbon::setTestNow('2026-10-10 12:00:00');
        $this->entry('SUB', '2026-10-09', 'Pagi');
        $this->entry('CGK', '2026-10-09', 'Pagi');

        $nobody = User::factory()->create(['is_default_password' => false, 'status' => 'active']);
        $this->actingAs($nobody)->get('/compliance/daily')->assertForbidden();

        $manager = User::factory()->create(['is_default_password' => false, 'status' => 'active']);
        $manager->assignRole(RoleHelper::MANAGER);
        Livewire::actingAs($manager)->test(Index::class)->assertSee('SUB')->assertSee('CGK');

        $pic = User::factory()->create(['is_default_password' => false, 'status' => 'active', 'station' => 'SUB']);
        $pic->assignRole(RoleHelper::PIC_CABIN);
        $c = Livewire::actingAs($pic)->test(Index::class);
        $c->assertSee('SUB')->assertDontSeeHtml('wire:key="cs-CGK"');
        $c->call('syncNow')->assertForbidden();
    }
}
