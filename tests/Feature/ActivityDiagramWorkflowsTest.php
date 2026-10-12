<?php

namespace Tests\Feature;

use App\Helpers\RoleHelper;
use App\Livewire\Modules\DailyReport\Index as DailyReportIndex;
use App\Livewire\Modules\Stretcher\Index as StretcherIndex;
use App\Models\CmlLog;
use App\Models\DailyJobAssignment;
use App\Models\User;
use Database\Seeders\RegistryPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Tests\TestCase;

class ActivityDiagramWorkflowsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RegistryPermissionSeeder::class);
    }

    private function makeUser(string $role, string $station = 'CGK'): User
    {
        $u = User::factory()->create([
            'is_default_password' => false,
            'status' => 'active',
            'station' => $station,
        ]);
        $u->assignRole($role);

        return $u;
    }

    public function test_deputy_can_access_master_airports_and_aircraft(): void
    {
        $deputy = $this->makeUser(RoleHelper::DEPUTY);

        $this->actingAs($deputy)
            ->get(route('master.airports'))
            ->assertOk();

        $this->actingAs($deputy)
            ->get(route('master.aircraft'))
            ->assertOk();
    }

    public function test_stretcher_validation_triggers_sweetalert_data_wajib_diisi_semua(): void
    {
        $cod = $this->makeUser(RoleHelper::COD);

        Livewire::actingAs($cod)
            ->test(StretcherIndex::class)
            ->set('activeTab', 'transactions')
            ->set('stretcher_id', '')
            ->call('saveTransaction')
            ->assertDispatched('swal:error', [
                'title' => 'Validasi Gagal',
                'text' => 'data wajib diisi semua',
            ]);
    }

    public function test_daily_report_station_feedback_renders_checklist_and_silang(): void
    {
        $cod = $this->makeUser(RoleHelper::COD);

        Livewire::actingAs($cod)
            ->test(DailyReportIndex::class)
            ->set('activeTab', 'station-feedback')
            ->assertSee('Feedback Station (✓ / ✗)')
            ->assertSee('Tarik Data Real-time')
            ->call('refreshRealtime')
            ->assertDispatched('notify');
    }

    public function test_notify_missing_reasons_command_runs_successfully(): void
    {
        Carbon::setTestNow('2026-10-12 08:30:00');

        $dja = DailyJobAssignment::create([
            'task_id' => 'TSK-001',
            'job_type' => 'R01/WO',
            'aircraft_registration' => 'PK-GAA',
            'date' => '2026-10-12',
            'station' => 'CGK',
            'description' => 'Tray table broken',
        ]);

        CmlLog::create([
            'dja_id' => $dja->id,
            'source' => 'CML',
            'cml_number' => 'CML-999',
            'aircraft_registration' => 'PK-GAA',
            'station' => 'CGK',
            'date' => '2026-10-12',
            'description' => 'Tray table broken',
            'status' => 'Open',
            'reason_open' => null,
        ]);

        $exitCode = Artisan::call('dja:notify-missing-reasons');
        $this->assertSame(0, $exitCode);

        Carbon::setTestNow();
    }
}
