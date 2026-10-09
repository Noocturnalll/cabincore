<?php

namespace Tests\Feature;

use App\Helpers\RoleHelper;
use App\Livewire\Reports\NsrdiPivot;
use App\Models\Aoc;
use App\Models\DailyJobAssignment;
use App\Models\MasterEntry;
use App\Models\User;
use App\Services\Kpi\NsrdiPivotService;
use Carbon\Carbon;
use Database\Seeders\RegistryPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class NsrdiPivotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-08 10:00:00');   // Thursday: the 8th is still pending
        $this->seed(RegistryPermissionSeeder::class);
        Aoc::create(['code' => 'IU', 'name' => 'Super Air Jet', 'aliases' => ['SUPER AIR JET'], 'include_in_report' => true, 'sort_order' => 3]);
        Aoc::create(['code' => 'JT', 'name' => 'Lion Air', 'aliases' => ['LION AIR'], 'include_in_report' => true, 'sort_order' => 1]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function nsrdi(string $djaDate, string $aoc, string $station, string $status, string $category = 'CBM'): void
    {
        $dja = DailyJobAssignment::create(['aircraft_registration' => 'PK-'.uniqid(), 'date' => $djaDate, 'job_type' => 'AOC/NSRDI', 'station' => $station, 'task_id' => uniqid('T')]);
        DB::table('nsrdi_logs')->insert([
            'dja_id' => $dja->id, 'aircraft_registration' => $dja->aircraft_registration, 'nsrdi_number' => uniqid('N'), 'aoc' => $aoc,
            'category' => $category, 'status' => $status, 'act_station' => $station, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_week_runs_friday_to_thursday(): void
    {
        $this->assertSame('2026-10-02', NsrdiPivotService::weekStart(Carbon::parse('2026-10-08'))->toDateString());
        $this->assertSame('2026-10-02', NsrdiPivotService::weekStart(Carbon::parse('2026-10-02'))->toDateString());
        $this->assertSame('2026-10-09', NsrdiPivotService::weekStart(Carbon::parse('2026-10-09'))->toDateString());
        $this->assertSame('2026-09-25', NsrdiPivotService::weekStart(Carbon::parse('2026-10-01'))->toDateString());
    }

    public function test_deploy_and_closed_per_aoc_station_day_and_defer_type(): void
    {
        // IU at CGK: 2 Oct deploy 3 closed 2 ; 3 Oct deploy 1 closed 1 ; painting 3 Oct deploy 1 closed 1
        foreach (['Closed', 'Closed', 'Open'] as $s) {
            $this->nsrdi('2026-10-02', 'IU', 'CGK', $s);
        }
        $this->nsrdi('2026-10-03', 'IU', 'CGK', 'Closed');
        $this->nsrdi('2026-10-03', 'IU', 'CGK', 'Closed', 'PAINTING');
        $this->nsrdi('2026-10-02', 'IU', 'SUB', 'Closed');
        $this->nsrdi('2026-10-02', 'JT', 'CGK', 'Closed');                  // another AOC
        $this->nsrdi('2026-10-01', 'IU', 'CGK', 'Closed');                  // previous week: not counted

        MasterEntry::create(['type' => 'kpi_target', 'code' => 'CGK-IU', 'label' => 'CGK / IU', 'attrs' => ['station' => 'CGK', 'aoc' => 'IU', 'target' => 7]]);
        MasterEntry::create(['type' => 'kpi_target', 'code' => 'SUB-IU', 'label' => 'SUB / IU', 'attrs' => ['station' => 'SUB', 'aoc' => 'IU', 'target' => 1]]);
        MasterEntry::create(['type' => 'kpi_target', 'code' => 'HLP-IU', 'label' => 'HLP / IU', 'attrs' => ['station' => 'HLP', 'aoc' => 'IU', 'target' => 2]]);

        $data = (new NsrdiPivotService)->build(Carbon::parse('2026-10-02'));

        $this->assertSame('2026-10-02', $data['days'][0]);
        $this->assertSame('2026-10-08', $data['days'][6]);
        $this->assertSame([false, false, false, false, false, false, true], $data['pending'], 'today (8 Oct) has not finished');

        $iu = $data['aocs']['IU'];
        $cgk = $iu['stations']['CGK'];
        $this->assertEquals(7, $cgk['capacity']);
        $this->assertSame([3, 2], $cgk['categories']['CBM']['days'][0], '2 Oct: deploy 3, closed 2');
        $this->assertSame([1, 1], $cgk['categories']['CBM']['days'][1]);
        $this->assertSame(4, $cgk['categories']['CBM']['deploy']);
        $this->assertSame([1, 1], $cgk['categories']['PAINTING']['days'][1]);
        $this->assertArrayNotHasKey('PAINTING', $iu['stations']['SUB']['categories'], 'a painting row exists only where painting was deployed');

        $this->assertArrayHasKey('HLP', $iu['stations'], 'a station with a target is listed even without work');
        $this->assertSame(['CGK', 'HLP', 'SUB'], array_keys($iu['stations']), 'workbook station order');

        $this->assertEquals(10, $iu['capacity']);                              // 7 + 1 + 2 per day
        $this->assertSame(6, $iu['totals']['deploy']);                         // 3 + 1 + 1 (CGK CBM+painting) + 1 SUB ...
        $this->assertSame(1, $data['aocs']['JT']['totals']['deploy']);
        $this->assertSame($iu['totals']['deploy'] + 1, $data['overview']['deploy']);
    }

    public function test_aoc_is_found_from_the_aircraft_when_the_text_is_a_name(): void
    {
        $this->nsrdi('2026-10-02', 'SUPER AIR JET', 'CGK', 'Closed');
        $data = (new NsrdiPivotService)->build(Carbon::parse('2026-10-02'));

        $this->assertSame(1, $data['aocs']['IU']['totals']['deploy']);
    }

    public function test_station_scope_and_page_access(): void
    {
        $this->nsrdi('2026-10-02', 'IU', 'CGK', 'Closed');
        $this->nsrdi('2026-10-02', 'IU', 'SUB', 'Closed');

        $scoped = (new NsrdiPivotService)->build(Carbon::parse('2026-10-02'), ['SUB']);
        $this->assertSame(['SUB'], array_keys($scoped['aocs']['IU']['stations']));

        $manager = User::factory()->create(['is_default_password' => false, 'status' => 'active']);
        $manager->assignRole(RoleHelper::MANAGER);
        Livewire::actingAs($manager)->test(NsrdiPivot::class)->set('date', '2026-10-05')
            ->assertSee('NSRDI AOC')->assertSee('2 – 8 October 2026')
            ->call('setTab', 'IU')->assertSee('act per station')->assertSee('CGK')->assertSee('SUB')
            ->call('move', 1)->assertSee('9 – 15 October 2026');

        $pic = User::factory()->create(['is_default_password' => false, 'status' => 'active', 'station' => 'SUB']);
        $pic->assignRole(RoleHelper::PIC_CABIN);
        Livewire::actingAs($pic)->test(NsrdiPivot::class)->set('date', '2026-10-05')->call('setTab', 'IU')->assertSee('SUB')->assertDontSeeHtml('np-IU-CGK');

        $nobody = User::factory()->create(['is_default_password' => false, 'status' => 'active']);
        $this->actingAs($nobody)->get('/reports/nsrdi-pivot')->assertForbidden();
    }
}
