<?php

namespace Tests\Feature;

use App\Console\Commands\OpsWatch;
use App\Helpers\RoleHelper;
use App\Models\Airport;
use App\Models\AuditLog;
use App\Models\SyncSetting;
use App\Models\User;
use App\Models\WoLog;
use App\Services\Kpi\OperationalPivot;
use App\Services\Kpi\ReportPeriod;
use App\Services\Kpi\StationKpiService;
use App\Support\StationNormalizer;
use Carbon\Carbon;
use Database\Seeders\RegistryPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Station cells, carried-over documents, silent failures and the audit trail. */
class DataQualityTest extends TestCase
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

    private function user(string $role): User
    {
        $u = User::factory()->create(['is_default_password' => false, 'status' => 'active']);
        $u->assignRole($role);

        return $u;
    }

    // ── station cells

    public function test_normalizer_keeps_codes_and_rejects_everything_else(): void
    {
        $this->assertSame('CGK', StationNormalizer::normalize(' cgk '));
        $this->assertSame('PNK', StationNormalizer::normalize('BANTEK PNK'));
        $this->assertSame('NBX', StationNormalizer::normalize('bantek  nbx'));
        $this->assertNull(StationNormalizer::normalize('UNSCEDHULE'));
        $this->assertNull(StationNormalizer::normalize("=IFNA(IFS(COUNTIFS('List AC'!\$A:\$A,\$C162)>0,'List AC'!\$A\$2),\"\")"));
        $this->assertNull(StationNormalizer::normalize(''));
        $this->assertNull(StationNormalizer::normalize(null));
        $this->assertNull(StationNormalizer::normalize('Unknown'));
    }

    public function test_models_never_store_a_junk_station(): void
    {
        $log = WoLog::create(['aircraft_registration' => 'PK-A', 'date' => '2026-10-14', 'status' => 'Open', 'act_station' => 'bantek pnk', 'plan_station' => 'UNSCEDHULE']);

        $this->assertSame('PNK', $log->fresh()->act_station);
        $this->assertNull($log->fresh()->plan_station);
    }

    public function test_the_clean_command_fixes_old_rows_and_registers_real_stations(): void
    {
        DB::table('cml_logs')->insert(['aircraft_registration' => 'PK-A', 'date' => '2026-10-13', 'status' => 'Open', 'station' => 'BANTEK NBX', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('cml_logs')->insert(['aircraft_registration' => 'PK-B', 'date' => '2026-10-13', 'status' => 'Open', 'station' => 'UNSCEDHULE', 'created_at' => now(), 'updated_at' => now()]);

        $this->artisan('stations:clean')->assertSuccessful();

        $this->assertSame(['NBX', null], DB::table('cml_logs')->orderBy('id')->pluck('station')->all());
        $this->assertTrue(Airport::where('kode', 'NBX')->exists(), 'a real station with work gets a master row');
        $this->assertFalse(Airport::where('kode', 'UNSCEDHULE')->exists());
    }

    public function test_a_station_without_roster_still_shows_its_work_and_is_marked(): void
    {
        $dja = DB::table('daily_job_assignments')->insertGetId(['date' => '2026-10-13', 'aircraft_registration' => 'PK-A', 'task_id' => 'T1', 'station' => 'BDJ', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('wo_logs')->insert(['aircraft_registration' => 'PK-A', 'date' => '2026-10-13', 'status' => 'Closed', 'plan_station' => 'BDJ', 'act_station' => 'BDJ', 'dja_id' => $dja, 'man_hour' => 4, 'created_at' => now(), 'updated_at' => now()]);

        $pivot = app(OperationalPivot::class)->build($this->user(RoleHelper::MANAGER), ReportPeriod::day(Carbon::parse('2026-10-13')));
        $this->assertSame(1, $pivot['rows']['BDJ']['wo']['closed'], 'its tasks are counted');

        $kpi = (new StationKpiService)->build(ReportPeriod::day(Carbon::parse('2026-10-13')));
        $row = collect($kpi['rows'])->firstWhere('station', 'BDJ');
        $this->assertTrue($row['no_mp']);
        $this->assertNull($row['utilisation'], 'no roster means "not measured", never 0% or a divide by zero');
    }

    // ── carried-over documents

    public function test_a_carried_over_document_is_counted_once_with_its_last_status(): void
    {
        $mon = DB::table('daily_job_assignments')->insertGetId(['date' => '2026-10-12', 'aircraft_registration' => 'PK-A', 'task_id' => 'A', 'station' => 'CGK', 'created_at' => now(), 'updated_at' => now()]);
        $tue = DB::table('daily_job_assignments')->insertGetId(['date' => '2026-10-13', 'aircraft_registration' => 'PK-A', 'task_id' => 'B', 'station' => 'CGK', 'created_at' => now(), 'updated_at' => now()]);
        $row = fn ($dja, $day, $status, $no) => DB::table('wo_logs')->insert(['aircraft_registration' => 'PK-A', 'date' => $day, 'status' => $status, 'wo_number' => $no, 'plan_station' => 'CGK', 'dja_id' => $dja, 'created_at' => now(), 'updated_at' => now()]);
        $row($mon, '2026-10-12', 'Open', '555');     // the same WO on Monday...
        $row($tue, '2026-10-13', 'Closed', '555');   // ...closed on Tuesday
        $row($tue, '2026-10-13', 'Open', '777');
        $row($tue, '2026-10-13', 'Open', null);      // no number: kept as it is

        $pivot = app(OperationalPivot::class)->build($this->user(RoleHelper::MANAGER), ReportPeriod::of('week', Carbon::parse('2026-10-12'), 1));

        $cell = $pivot['rows']['CGK']['wo'];
        $this->assertSame(3, $cell['total'], '555 once, 777, and the one without a number');
        $this->assertSame(1, $cell['closed'], '555 counts with its last status (Closed)');
    }

    // ── silent failures

    public function test_ops_watch_reports_a_failed_sync_and_a_stopped_archive(): void
    {
        SyncSetting::create(['key' => SyncSetting::Dja, 'last_status' => 'failed', 'last_message' => 'Sheet tidak bisa dibuka', 'last_synced_at' => now()->subMinutes(5)]);
        SyncSetting::create(['key' => SyncSetting::AcMovement, 'last_status' => 'success', 'last_synced_at' => now()->subMinutes(90)]);

        $issues = (new OpsWatch)->issues(now());

        $this->assertArrayHasKey('sync-failed-dja', $issues);
        $this->assertStringContainsString('Sheet tidak bisa dibuka', $issues['sync-failed-dja']);
        $this->assertArrayHasKey('sync-stale-ac_movement', $issues);
        $this->assertArrayHasKey('archive-stopped', $issues);

        Cache::forever(OpsWatch::ARCHIVE_MARKER, now()->subMinutes(10)->toIso8601String());
        $this->assertArrayNotHasKey('archive-stopped', (new OpsWatch)->issues(now()));
    }

    public function test_ops_watch_stays_quiet_at_night_and_alerts_once(): void
    {
        SyncSetting::create(['key' => SyncSetting::AcMovement, 'last_status' => 'success', 'last_synced_at' => now()->subHours(6)]);

        $this->assertSame([], (new OpsWatch)->issues(Carbon::parse('2026-10-14 02:00:00')), 'no stale alarm when nobody is working');

        $admin = $this->user(RoleHelper::SUPER_ADMIN);
        $this->artisan('ops:watch')->assertSuccessful();
        $this->artisan('ops:watch')->assertSuccessful();

        $this->assertSame(2, $admin->notifications()->count(), 'two problems (stale sync, stopped archive), each once, not once per run');
    }

    // ── audit

    public function test_changes_to_accounts_and_masters_are_written_to_the_audit_trail(): void
    {
        $admin = $this->user(RoleHelper::SUPER_ADMIN);
        $this->actingAs($admin);

        $airport = Airport::create(['kode' => 'ZZZ', 'nama' => 'Test', 'kota' => 'X', 'status' => 'Aktif']);
        $airport->update(['status' => 'Tidak Aktif']);
        $user = User::factory()->create(['is_default_password' => false, 'status' => 'active']);
        $user->update(['password' => bcrypt('a-new-secret-1')]);
        $airport->delete();

        $log = AuditLog::orderBy('id')->get()->map(fn ($l) => $l->action.' | '.$l->description);

        $this->assertTrue($log->contains(fn ($l) => str_starts_with($l, 'Tambah Airport') && str_contains($l, 'ZZZ')));
        $this->assertTrue($log->contains(fn ($l) => str_starts_with($l, 'Ubah Airport') && str_contains($l, 'Aktif -> Tidak Aktif')));
        $this->assertTrue($log->contains(fn ($l) => str_starts_with($l, 'Hapus Airport')));
        $this->assertTrue($log->contains(fn ($l) => str_starts_with($l, 'Ubah User') && str_contains($l, 'password diubah')));
        $this->assertFalse($log->contains(fn ($l) => str_contains($l, 'a-new-secret-1') || str_contains($l, '$2y$')), 'a password is never written to the log');
        $this->assertSame($admin->id, AuditLog::first()->user_id);
    }

    public function test_work_done_by_the_scheduler_without_a_user_is_not_attributed_to_anybody(): void
    {
        Airport::create(['kode' => 'QQQ', 'nama' => 'Console', 'kota' => 'X', 'status' => 'Aktif']);

        $this->assertSame(0, AuditLog::count());
    }
}
