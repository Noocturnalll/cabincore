<?php

namespace Tests\Feature;

use App\Helpers\RoleHelper;
use App\Models\User;
use App\Services\Kpi\AchievementStatus;
use App\Services\Kpi\ManHourService;
use App\Services\Kpi\ReportPeriod;
use Carbon\Carbon;
use Database\Seeders\RegistryPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class KpiManHoursTest extends TestCase
{
    use RefreshDatabase;

    public function test_week_runs_thursday_to_wednesday(): void
    {
        // 2026-10-08 is a Thursday
        $w = ReportPeriod::week(Carbon::parse('2026-10-08'));
        $this->assertSame('2026-10-08', $w->from->toDateString());
        $this->assertSame('2026-10-14', $w->to->toDateString());

        // Wednesday belongs to the week that started the Thursday before
        $w = ReportPeriod::week(Carbon::parse('2026-10-14'));
        $this->assertSame('2026-10-08', $w->from->toDateString());

        // Friday, Tuesday
        $this->assertSame('2026-10-08', ReportPeriod::week(Carbon::parse('2026-10-09'))->from->toDateString());
        $this->assertSame('2026-10-01', ReportPeriod::week(Carbon::parse('2026-10-07'))->from->toDateString());
        $this->assertSame(7, $w->days());
    }

    public function test_achievement_colours(): void
    {
        $this->assertSame('green', AchievementStatus::for(100, 100));
        $this->assertSame('green', AchievementStatus::for(120, 100));
        $this->assertSame('yellow', AchievementStatus::for(90, 100));
        $this->assertSame('red', AchievementStatus::for(89, 100));
        $this->assertSame('none', AchievementStatus::for(5, 0));
        $this->assertSame('none', AchievementStatus::for(null, 100));
    }

    public function test_capacity_uses_ten_effective_hours_per_technician_per_day(): void
    {
        DB::table('capacity_stations')->insert([
            ['kh_region' => 'A', 'station_code' => 'CGK', 'tech_day' => 4, 'tech_night' => 6],
            ['kh_region' => 'A', 'station_code' => 'HLP', 'tech_day' => null, 'tech_night' => 3],
        ]);

        $cap = (new ManHourService)->capacity(ReportPeriod::week(Carbon::parse('2026-10-08')));

        $this->assertSame(13, $cap['technicians']);
        $this->assertEquals(13 * 10 * 7, $cap['hours']);
    }

    public function test_used_hours_per_module_and_coverage(): void
    {
        $day = '2026-10-08';
        DB::table('wo_logs')->insert([
            ['aircraft_registration' => 'PK-AAA', 'date' => $day, 'status' => 'Closed', 'man_hour' => 3.5, 'created_at' => now(), 'updated_at' => now()],
            ['aircraft_registration' => 'PK-AAA', 'date' => $day, 'status' => 'Open', 'man_hour' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('aircraft_cleanings')->insert([
            ['aircraft_registration' => 'PK-AAA', 'date' => $day, 'status' => 'Closed', 'type' => 'General', 'shift' => 'Pagi', 'start_at' => "$day 08:00:00", 'end_at' => "$day 10:30:00", 'created_at' => now(), 'updated_at' => now()],
            ['aircraft_registration' => 'PK-AAA', 'date' => $day, 'status' => 'Open', 'type' => 'General', 'shift' => 'Pagi', 'start_at' => null, 'end_at' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('cml_logs')->insert([
            ['aircraft_registration' => 'PK-AAA', 'date' => $day, 'status' => 'Closed', 'man_hour' => 3.0, 'created_at' => now(), 'updated_at' => now()],
            ['aircraft_registration' => 'PK-AAA', 'date' => $day, 'status' => 'Closed', 'man_hour' => null, 'created_at' => now(), 'updated_at' => now()], // end before start: ignored
        ]);

        $used = (new ManHourService)->used(ReportPeriod::day(Carbon::parse($day)));

        $this->assertEquals(3.5, $used['wo']['hours']);
        $this->assertSame(2, $used['wo']['records']);
        $this->assertSame(1, $used['wo']['timed']);
        $this->assertEquals(2.5, $used['cleaning']['hours']);
        $this->assertSame(1, $used['cleaning']['timed']);
        $this->assertEquals(3.0, $used['cml']['hours']);
        $this->assertSame(1, $used['cml']['timed']);
        $this->assertNull($used['dmi']['hours']); // no man hours recorded yet
    }

    public function test_kpi_page_opens_for_manager(): void
    {
        $this->seed(RegistryPermissionSeeder::class);
        $u = User::factory()->create(['is_default_password' => false, 'status' => 'active']);
        $u->assignRole(RoleHelper::MANAGER);

        $this->actingAs($u)->get('/reports/kpi')->assertOk()->assertSee('KPI &amp; Man Hours', false)->assertSee('Mingguan');
    }
}
