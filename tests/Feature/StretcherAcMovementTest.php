<?php

namespace Tests\Feature;

use App\Helpers\RoleHelper;
use App\Livewire\Modules\AcMovement\Index as AcMovementIndex;
use App\Livewire\Modules\Stretcher\Index as StretcherIndex;
use App\Models\AcRon;
use App\Models\Stretcher;
use App\Models\StretcherTransaction;
use App\Models\TerminalMovement;
use App\Models\User;
use Database\Seeders\RegistryPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StretcherAcMovementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RegistryPermissionSeeder::class);
    }

    private function adminUser(): User
    {
        $user = User::factory()->create([
            'is_default_password' => false,
            'status' => 'active',
            'station' => 'CGK',
        ]);
        $user->assignRole(RoleHelper::SUPER_ADMIN);

        return $user;
    }

    public function test_can_create_stretcher_transaction_with_requested_status(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user);

        $stretcher = Stretcher::create([
            'code' => 'STR-99',
            'name' => 'Stretcher B737 Test',
            'aircraft_type' => 'B737',
            'current_station' => 'CGK',
            'status' => 'AVAILABLE',
            'condition' => 'GOOD',
        ]);

        Livewire::test(StretcherIndex::class)
            ->call('openCreateTransaction', $stretcher->id)
            ->set('aircraft_registration', 'PK-LQZ')
            ->set('flight_number', 'JT-380')
            ->set('origin_station', 'CGK')
            ->set('destination_station', 'KNO')
            ->set('passenger_name', 'Bpk. Ahmad Fauzi')
            ->set('medical_notes', 'Patah tulang - butuh oksigen 2L')
            ->set('status', 'REQUESTED')
            ->call('saveTransaction')
            ->assertDispatched('notify');

        $this->assertDatabaseHas('stretcher_transactions', [
            'aircraft_registration' => 'PK-LQZ',
            'flight_number' => 'JT-380',
            'passenger_name' => 'Bpk. Ahmad Fauzi',
            'status' => 'REQUESTED',
        ]);

        $stretcher->refresh();
        $this->assertSame('IN_USE', $stretcher->status);
    }

    public function test_ac_movement_detects_stretcher_transaction_by_registration_and_flight(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user);

        $stretcher = Stretcher::create([
            'code' => 'STR-01',
            'name' => 'Stretcher Unit 1',
            'current_station' => 'CGK',
            'status' => 'IN_USE',
            'condition' => 'GOOD',
        ]);

        $trx = StretcherTransaction::create([
            'stretcher_id' => $stretcher->id,
            'date' => now()->toDateString(),
            'aircraft_registration' => 'PK-LQZ',
            'flight_number' => 'JT-380',
            'origin_station' => 'CGK',
            'destination_station' => 'SUB',
            'route' => 'CGK - SUB',
            'passenger_name' => 'Siti Nurhaliza',
            'medical_notes' => 'Pasien pasca operasi',
            'status' => 'REQUESTED',
        ]);

        // Create terminal movement with matching registration PK-LQZ
        TerminalMovement::create([
            'terminal_name' => 'TERMINAL 1',
            'no_seq' => 1,
            'flight_date' => now(),
            'registration' => 'PK-LQZ',
            'flight_no_in' => 'JT-380',
            'flight_no_out' => 'JT-381',
        ]);

        // Create RON with matching flight
        AcRon::create([
            'no_seq' => 1,
            'ron_date' => now(),
            'reg_flt' => 'PK-LQZ',
            'ex_flt' => 'JT-380',
            'flt_no' => 'JT-381',
            'stand' => 'A1',
            'route' => 'CGK-SUB',
        ]);

        $component = Livewire::test(AcMovementIndex::class);

        // Test matcher directly
        $matchByReg = $component->instance()->findStretcherMatch('PK-LQZ');
        $this->assertNotNull($matchByReg);
        $this->assertSame($trx->id, $matchByReg->id);

        $matchByFlt = $component->instance()->findStretcherMatch(null, 'JT-380');
        $this->assertNotNull($matchByFlt);
        $this->assertSame($trx->id, $matchByFlt->id);

        // Test view rendering with badge
        $component->assertSee('Req Stretcher')
            ->assertSee('PK-LQZ');

        // Test opening modal
        $component->call('showStretcherDetail', $trx->id)
            ->assertSet('isStretcherModalOpen', true)
            ->assertSee('Detail Permintaan Stretcher')
            ->assertSee('Siti Nurhaliza')
            ->assertSee('Pasien pasca operasi')
            ->call('closeStretcherModal')
            ->assertSet('isStretcherModalOpen', false);
    }
}
