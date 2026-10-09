<?php

namespace Tests\Feature;

use App\Helpers\RoleHelper;
use App\Livewire\Reports\KpiDashboard;
use App\Models\User;
use App\Services\Kpi\OperationalCharts;
use App\Services\Kpi\OperationalPivot;
use App\Services\Kpi\ReportPeriod;
use Carbon\Carbon;
use Database\Seeders\RegistryPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/** The dashboard draws its percentages: closed rate, cleaning mix, KPI per station. */
class OperationalChartsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-09 10:00:00');
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

    private function seedData(): void
    {
        $dja = fn ($d) => DB::table('daily_job_assignments')->insertGetId(['date' => $d, 'aircraft_registration' => 'PK-A', 'task_id' => 'T'.uniqid(), 'station' => 'CGK', 'created_at' => now(), 'updated_at' => now()]);
        foreach ([['2026-10-07', 'Closed'], ['2026-10-07', 'Closed'], ['2026-10-08', 'Open']] as [$d, $s]) {
            DB::table('wo_logs')->insert(['aircraft_registration' => 'PK-A', 'date' => $d, 'status' => $s, 'act_station' => 'CGK', 'dja_id' => $dja($d), 'created_at' => now(), 'updated_at' => now()]);
        }
        foreach ([['Transit', 'CGK', '2026-10-07', 'Closed'], ['Transit', 'CGK', '2026-10-08', 'Closed'], ['Transit', 'SUB', '2026-10-08', 'Open'], ['GCE', 'CGK', '2026-10-07', 'Closed']] as [$t, $s, $d, $st]) {
            DB::table('aircraft_cleanings')->insert(['aircraft_registration' => 'PK-'.uniqid(), 'date' => $d, 'shift' => 'Pagi', 'type' => $t, 'status' => $st, 'station' => $s, 'man_hour' => 1, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    private function build(): array
    {
        $pivot = app(OperationalPivot::class)->build($this->manager(), ReportPeriod::of('week', Carbon::parse('2026-10-07'), 3));

        return app(OperationalCharts::class)->build($pivot, [
            ['station' => 'CGK', 'capacity' => 100, 'utilisation' => 80.5, 'accuracy' => 99.0, 'compliance' => 90.0],
            ['station' => 'SUB', 'capacity' => 40, 'utilisation' => 55.0, 'accuracy' => null, 'compliance' => 70.0],
        ]);
    }

    public function test_production_charts_are_percentages(): void
    {
        $this->seedData();
        $charts = collect($this->build()['production'])->keyBy('id');

        $this->assertSame(['rates', 'by_station', 'trend'], $charts->keys()->all());
        $rates = $charts['rates'];
        $this->assertTrue($rates['percent']);
        $this->assertSame(100, $rates['max']);
        $this->assertContains('WO', $rates['labels']);
        $this->assertSame(66.7, $rates['datasets'][0]['data'][array_search('WO', $rates['labels'])]);
        $this->assertSame('line', $charts['trend']['type']);
        $this->assertCount(2, $charts['trend']['labels'], 'one point per day');
    }

    public function test_cleaning_has_share_rate_station_mix_and_daily_volume(): void
    {
        $this->seedData();
        $charts = collect($this->build()['cleaning'])->keyBy('id');

        $this->assertSame(['cl_share', 'cl_rate', 'cl_station', 'cl_trend'], $charts->keys()->all());

        $share = $charts['cl_share'];
        $this->assertSame('doughnut', $share['type']);
        $this->assertEqualsWithDelta(100.0, array_sum($share['datasets'][0]['data']), 0.2, 'the shares add up to 100%');
        $this->assertSame(75.0, $share['datasets'][0]['data'][array_search('Transit', $share['labels'])]);

        $rate = $charts['cl_rate'];
        $this->assertSame(66.7, $rate['datasets'][0]['data'][array_search('Transit', $rate['labels'])]);
        $this->assertSame(100.0, $rate['datasets'][0]['data'][array_search('GCE', $rate['labels'])]);

        $station = $charts['cl_station'];
        $this->assertTrue($station['stacked'] && $station['horizontal']);
        $cgk = array_search('CGK', $station['labels']);
        $this->assertEqualsWithDelta(100.0, array_sum(array_map(fn ($d) => $d['data'][$cgk], $station['datasets'])), 0.2, 'each station adds up to 100%');

        $this->assertFalse($charts['cl_trend']['percent'], 'daily volume is a count, not a percentage');
    }

    public function test_kpi_chart_skips_series_without_data(): void
    {
        $this->seedData();
        $kpi = $this->build()['kpi'][0];

        $this->assertSame(['CGK', 'SUB'], $kpi['labels']);
        $this->assertSame(['Utilisasi man hours', 'Document accuracy', 'Compliance'], array_column($kpi['datasets'], 'label'));
        $this->assertNull($kpi['datasets'][1]['data'][1]);
    }

    public function test_no_data_means_no_charts(): void
    {
        $charts = $this->build();

        $this->assertSame([], $charts['production']);
        $this->assertSame([], $charts['cleaning']);
    }

    public function test_the_page_draws_the_charts(): void
    {
        $this->seedData();

        Livewire::actingAs($this->manager())->test(KpiDashboard::class)
            ->set('kind', 'month')->set('date', '2026-10-08')
            ->assertSee('Closed rate per modul')->assertSee('Porsi tipe cleaning')->assertSee('Komposisi tipe per station')
            ->assertSee('Aircraft Cleaning (AIEC)')->assertSeeHtml('kdChart(')->assertSeeHtml('id="sec-charts"');
    }
}
