<?php

namespace Tests\Feature;

use App\Livewire\Reports\Insights;
use App\Livewire\Sync\Log as SyncLog;
use App\Models\CmlLog;
use App\Models\SheetSyncLog;
use App\Models\User;
use App\Models\WoLog;
use App\Services\Kpi\ModuleTiles;
use App\Services\Kpi\OperationalInsights;
use App\Services\Kpi\ReportPeriod;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InsightsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $u = User::factory()->create(['status' => 'active']);
        $u->assignRole(Role::findOrCreate('Super Admin'));

        return $u;
    }

    public function test_pivots_group_by_wg_operator_aging_reason_and_planned(): void
    {
        $today = now()->toDateString();
        WoLog::create(['wo_number' => 'W1', 'aircraft_registration' => 'PK-A', 'work_group' => 'CBM', 'operator' => 'LION', 'status' => 'Closed', 'date' => $today, 'act_station' => 'CGK']);
        WoLog::create(['wo_number' => 'W2', 'aircraft_registration' => 'PK-B', 'work_group' => 'CBM', 'operator' => 'LION', 'status' => 'Open', 'date' => now()->subDays(10)->toDateString(), 'act_station' => 'CGK', 'hold_reason_category' => 'LT']);

        $period = ReportPeriod::of('month', Carbon::now());
        $data = app(OperationalInsights::class)->build($period, null);

        $this->assertSame(1, $data['byWg']['CBM']['wo']['closed'] ?? null);
        $this->assertArrayHasKey('LION', $data['byAoc']);
        $this->assertSame(1, $data['aging']['8-14 hari']['wo']);
        $this->assertSame(1, $data['reasons']['LT']);
        $this->assertSame(2, $data['planned']['CGK']['unplanned']);
    }

    public function test_man_hours_use_cml_perform_times(): void
    {
        CmlLog::create(['date' => now()->toDateString(), 'aircraft_registration' => 'PK-A', 'station' => 'KOE', 'no_doc' => 'C1', 'description' => 'seat', 'status' => 'Closed',
            'start_perform' => '17,4', 'finish_perform' => '18,4', 'mp1' => 'A', 'mp2' => 'B']);
        CmlLog::create(['date' => now()->toDateString(), 'aircraft_registration' => 'PK-B', 'station' => 'KOE', 'no_doc' => 'C2', 'description' => 'DBI check', 'status' => 'Closed', 'task_type' => 'DBI']);

        $m = app(OperationalInsights::class)->build(ReportPeriod::of('day', Carbon::now()), null)['manHours'];

        $this->assertEquals(2.0, $m['hours']);
        $this->assertSame(1, $m['timed']);
        $this->assertSame(2, $m['jobs']);
        $this->assertSame(1, $m['perStation']['KOE']['DBI']['jobs']);
    }

    public function test_pages_render_and_tiles_show_for_admin(): void
    {
        $this->actingAs($admin = $this->admin());
        SheetSyncLog::note('push', 'failed', 'wo', 'W9', 'gagal');

        Livewire::test(Insights::class)->assertOk()->assertSee('Man-hour CML');
        Livewire::test(SyncLog::class)->assertOk()->assertSee('W9');

        $tiles = collect(app(ModuleTiles::class)->build($admin, ReportPeriod::of('week', now())))->flatten(1)->pluck('key');
        $this->assertTrue($tiles->contains('synclog'));
    }

    public function test_sync_log_is_closed_to_ordinary_users(): void
    {
        $this->actingAs(User::factory()->create(['status' => 'active']));
        $this->get(route('sync.log'))->assertForbidden();
    }
}
