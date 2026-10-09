<?php

namespace Tests\Feature;

use App\Helpers\RoleHelper;
use App\Livewire\Modules\AircraftCleaning\Hub;
use App\Livewire\Modules\Hr\Employees;
use App\Livewire\Modules\Lgt\Index;
use App\Models\Aircraft;
use App\Models\Aoc;
use App\Models\Division;
use App\Models\Employee;
use App\Models\LgtRecord;
use App\Models\RegistryRecord;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RegistryPermissionSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LgtPlanAndHubTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-08 10:00:00');
        $this->seed(RegistryPermissionSeeder::class);
        $this->seed(MasterDataSeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function user(string $role, array $attrs = []): User
    {
        $u = User::factory()->create(['is_default_password' => false, 'status' => 'active'] + $attrs);
        $u->assignRole($role);

        return $u;
    }

    public function test_ground_time_is_the_gap_between_sta_and_std_even_past_midnight(): void
    {
        $this->assertSame(150, Index::groundMinutes('08:00', '10:30'));
        $this->assertSame(180, Index::groundMinutes('22:00', '01:00'));
        $this->assertNull(Index::groundMinutes('08:00', null));
    }

    public function test_plan_an_lgt_with_jobs_for_both_teams_and_close_them(): void
    {
        $aoc = Aoc::create(['code' => 'ID', 'name' => 'Batik Air', 'aliases' => [], 'include_in_report' => true, 'sort_order' => 2]);
        Aircraft::create(['registration' => 'PK-LBW', 'tipe' => 'B737', 'maskapai' => 'Batik Air', 'status' => 'Aktif', 'aoc_id' => $aoc->id]);
        $manager = $this->user(RoleHelper::MANAGER);

        $c = Livewire::actingAs($manager)->test(Index::class)->set('date', '2026-10-08')
            ->call('create')
            ->set('form_station', 'cgk')->set('aircraft_registration', 'pk-lbw')->set('sta_time', '09:00')->set('std_time', '14:30')
            ->set('cbm_action', 'REPLACE LAMP LAV')->set('aiec_action', 'GENERAL CLEANING')
            ->call('save')->assertHasNoErrors();

        $r = LgtRecord::first();
        $this->assertSame('CGK', $r->station);
        $this->assertSame('PK-LBW', $r->aircraft_registration);
        $this->assertSame('Batik Air', $r->aoc, 'the airline comes from the aircraft master');
        $this->assertSame('OPEN', $r->cbm_status);
        $this->assertSame('OPEN', $r->aiec_status);

        $c->assertSee('PK-LBW')->assertSee('5j 30m');
        $c->call('close_', $r->id, 'cbm');
        $this->assertSame('CLOSED', $r->fresh()->cbm_status);
        $this->assertSame('OPEN', $r->fresh()->aiec_status);

        // another job on the same aircraft keeps the aircraft and its times
        $c->call('create', $r->id)->assertSet('aircraft_registration', 'PK-LBW')->assertSet('sta_time', '09:00');
    }

    public function test_validation_a_job_is_required_and_a_cancel_needs_a_reason(): void
    {
        $manager = $this->user(RoleHelper::MANAGER);

        Livewire::actingAs($manager)->test(Index::class)->call('create')
            ->set('form_station', 'CGK')->set('aircraft_registration', 'PK-AAA')->call('save')->assertHasErrors('cbm_action')
            ->set('aiec_action', 'DCI')->set('aiec_status', 'CANCEL')->call('save')->assertHasErrors('reason')
            ->set('reason', 'AC ROTATION CHANGED')->call('save')->assertHasNoErrors();

        $this->assertSame('CANCEL', LgtRecord::first()->aiec_status);
        $this->assertNull(LgtRecord::first()->cbm_status, 'no CBM job, no CBM status');
    }

    public function test_a_pic_works_only_in_their_own_station_and_a_viewer_cannot_change_anything(): void
    {
        LgtRecord::create(['work_date' => '2026-10-08', 'station' => 'CGK', 'aircraft_registration' => 'PK-CGK', 'cbm_action' => 'X', 'cbm_status' => 'OPEN']);
        LgtRecord::create(['work_date' => '2026-10-08', 'station' => 'SUB', 'aircraft_registration' => 'PK-SUB', 'cbm_action' => 'Y', 'cbm_status' => 'OPEN']);

        $pic = $this->user(RoleHelper::PIC_CABIN, ['station' => 'SUB']);
        $c = Livewire::actingAs($pic)->test(Index::class)->set('date', '2026-10-08');
        $c->assertSee('PK-SUB')->assertDontSee('PK-CGK');

        $c->call('create')->set('form_station', 'CGK')->set('aircraft_registration', 'PK-NEW')->set('cbm_action', 'Z')->call('save')->assertHasErrors('form_station');
        $this->assertSame(0, LgtRecord::where('aircraft_registration', 'PK-NEW')->count());

        $cgk = LgtRecord::where('station', 'CGK')->first();
        try {
            $c->call('edit', $cgk->id);
            $this->fail('A record of another station must not open.');
        } catch (ModelNotFoundException) {
            $this->addToAssertionCount(1);
        }

        $viewer = User::factory()->create(['is_default_password' => false, 'status' => 'active']);
        $viewer->givePermissionTo('lgt.view');
        Livewire::actingAs($viewer)->test(Index::class)->call('create')->assertForbidden();

        $nobody = User::factory()->create(['is_default_password' => false, 'status' => 'active']);
        $this->actingAs($nobody)->get('/modules/lgt')->assertForbidden();
    }

    public function test_the_cleaning_hub_switches_type_without_a_menu_item_per_type(): void
    {
        $user = $this->user(RoleHelper::MANAGER);

        $this->actingAs($user)->get('/modules/aircraft-cleaning')->assertOk()->assertSee('General Cleaning');

        Livewire::actingAs($user)->test(Hub::class)->assertSet('type', 'general')
            ->call('setType', 'lgt')->assertSet('type', 'lgt')->assertSee('Long Ground Time (LGT)')
            ->call('setType', 'dbi')->assertSee('Daily Beautification (DBI)')
            ->call('setType', 'transit')->assertSee('Transit Cleaning')
            ->call('setType', 'nonsense')->assertSet('type', 'general');

        // the old menu had a link per type; the sidebar now has the hub only
        $sidebar = file_get_contents(resource_path('views/components/layouts/partials/sidebar.blade.php'));
        $this->assertStringNotContainsString("route('modules.cleaning.interior') }}\" wire:navigate", $sidebar);
        $this->assertStringNotContainsString('Team Painting', $sidebar);
        $this->assertStringContainsString("route('modules.cleaning.hub')", $sidebar);
    }

    public function test_employees_have_a_tab_per_division_and_show_the_airport_pass(): void
    {
        $cabin = Division::where('name', 'Cabin')->value('id');
        $aiec = Division::where('name', 'AIEC')->value('id');
        Employee::create(['nik' => '111', 'name' => 'BUDI', 'division_id' => $cabin, 'contract_type' => 'PKWTT', 'status' => 'Aktif']);
        Employee::create(['nik' => '222', 'name' => 'SARI', 'division_id' => $aiec, 'contract_type' => 'PKWTT', 'status' => 'Aktif']);
        Employee::create(['nik' => '333', 'name' => 'ANDI', 'division_id' => $aiec, 'contract_type' => 'PKWTT', 'status' => 'Aktif']);
        RegistryRecord::create(['module' => 'pas', 'division_id' => $cabin, 'title' => 'BUDI', 'due_date' => now()->addDays(20), 'data' => ['holder' => 'BUDI', 'nik' => '111', 'codes' => 'AD, P', 'airport' => 'CGK']]);

        $manager = $this->user(RoleHelper::MANAGER);
        $c = Livewire::actingAs($manager)->test(Employees::class)
            ->assertSee('BUDI')->assertSee('SARI')->assertSee('AD, P')->assertSee('Belum ada');

        $c->call('setDivision', (string) $aiec)->assertSee('SARI')->assertSee('ANDI')->assertDontSee('BUDI');
        $c->call('setDivision', (string) $cabin)->assertSee('BUDI')->assertDontSee('SARI');
        $c->call('setDivision', '')->assertSee('SARI');
    }
}
