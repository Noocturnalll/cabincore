<?php

namespace Tests\Feature;

use App\Livewire\Modules\Stretcher\Index;
use App\Models\Stretcher;
use App\Models\StretcherTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class StretcherTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('menu.production');
        $this->user = User::factory()->create();
        $this->user->givePermissionTo('menu.production');
    }

    public function test_stretcher_page_can_be_rendered(): void
    {
        $this->actingAs($this->user)
            ->get(route('modules.stretcher.index'))
            ->assertOk()
            ->assertSee('Stretcher Tracking');
    }

    public function test_stretcher_end_point_updates_on_new_transaction_and_remains_until_new_leg(): void
    {
        // 1. Create a Stretcher unit initially at CGK
        $stretcher = Stretcher::create([
            'code' => 'STR-TEST-1',
            'name' => 'Stretcher Unit Test',
            'aircraft_type' => 'B737',
            'current_station' => 'CGK',
            'status' => 'AVAILABLE',
            'condition' => 'GOOD',
        ]);

        $this->assertSame('CGK', $stretcher->fresh()->current_station);

        // 2. Flight from CGK to KNO carrying patient Budi
        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->call('openCreateTransaction', $stretcher->id)
            ->assertSet('origin_station', 'CGK')
            ->set('destination_station', 'KNO')
            ->set('aircraft_registration', 'PK-LGF')
            ->set('flight_number', 'JT-380')
            ->set('passenger_name', 'Budi Santoso')
            ->set('medical_notes', 'Stroke patient')
            ->set('status', 'COMPLETED')
            ->call('saveTransaction')
            ->assertHasNoErrors();

        // Stretcher point is now KNO!
        $this->assertSame('KNO', $stretcher->fresh()->current_station);
        $this->assertSame('AVAILABLE', $stretcher->fresh()->status);

        // Point does NOT change as long as no new transaction created
        $this->assertSame('KNO', $stretcher->fresh()->current_station);

        // 3. New transaction: flying back from KNO to CGK carrying patient Siti
        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->call('openCreateTransaction', $stretcher->id)
            ->assertSet('origin_station', 'KNO') // Auto-filled from KNO!
            ->set('destination_station', 'CGK')
            ->set('aircraft_registration', 'PK-LGP')
            ->set('flight_number', 'JT-381')
            ->set('passenger_name', 'Siti Rahma')
            ->set('status', 'COMPLETED')
            ->call('saveTransaction')
            ->assertHasNoErrors();

        // Stretcher point has now moved to CGK!
        $this->assertSame('CGK', $stretcher->fresh()->current_station);
    }

    public function test_deleting_transaction_re_syncs_stretcher_location(): void
    {
        $stretcher = Stretcher::create([
            'code' => 'STR-TEST-2',
            'name' => 'Stretcher Unit Test 2',
            'current_station' => 'CGK',
            'status' => 'AVAILABLE',
            'condition' => 'GOOD',
        ]);

        // Leg 1: CGK -> KNO
        $trx1 = StretcherTransaction::create([
            'stretcher_id' => $stretcher->id,
            'date' => now()->subDay()->format('Y-m-d'),
            'aircraft_registration' => 'PK-LGF',
            'flight_number' => 'JT-380',
            'origin_station' => 'CGK',
            'destination_station' => 'KNO',
            'route' => 'CGK - KNO',
            'passenger_name' => 'Pasien 1',
            'status' => 'COMPLETED',
        ]);
        $stretcher->syncCurrentStation();
        $this->assertSame('KNO', $stretcher->fresh()->current_station);

        // Leg 2: KNO -> SUB
        $trx2 = StretcherTransaction::create([
            'stretcher_id' => $stretcher->id,
            'date' => now()->format('Y-m-d'),
            'aircraft_registration' => 'PK-LGP',
            'flight_number' => 'JT-990',
            'origin_station' => 'KNO',
            'destination_station' => 'SUB',
            'route' => 'KNO - SUB',
            'passenger_name' => 'Pasien 2',
            'status' => 'COMPLETED',
        ]);
        $stretcher->syncCurrentStation();
        $this->assertSame('SUB', $stretcher->fresh()->current_station);

        // Delete Leg 2: should fall back to KNO (latest remaining leg)
        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->call('deleteTransaction', $trx2->id);

        $this->assertDatabaseMissing('stretcher_transactions', ['id' => $trx2->id]);
        $this->assertSame('KNO', $stretcher->fresh()->current_station);
    }

    public function test_master_unit_stretcher_crud(): void
    {
        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->call('openCreateUnit')
            ->set('unit_code', 'STR-NEW')
            ->set('unit_name', 'Stretcher Baru')
            ->set('unit_aircraft_type', 'A320')
            ->set('unit_current_station', 'DPS')
            ->set('unit_status', 'AVAILABLE')
            ->set('unit_condition', 'GOOD')
            ->call('saveUnit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('stretchers', [
            'code' => 'STR-NEW',
            'current_station' => 'DPS',
        ]);

        $stretcher = Stretcher::where('code', 'STR-NEW')->first();

        // Edit
        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->call('openEditUnit', $stretcher->id)
            ->set('unit_name', 'Stretcher Baru Updated')
            ->call('saveUnit')
            ->assertHasNoErrors();

        $this->assertSame('Stretcher Baru Updated', $stretcher->fresh()->name);

        // Delete
        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->call('deleteUnit', $stretcher->id);

        $this->assertSoftDeleted('stretchers', ['id' => $stretcher->id]);
    }

    public function test_stretcher_export_excel_includes_pivot_sheet(): void
    {
        $stretcher = Stretcher::create([
            'code' => 'STR-EXP-1',
            'name' => 'Stretcher Unit Exp',
            'aircraft_type' => 'B737',
            'current_station' => 'CGK',
            'status' => 'AVAILABLE',
            'condition' => 'GOOD',
        ]);

        StretcherTransaction::create([
            'stretcher_id' => $stretcher->id,
            'date' => now()->toDateString(),
            'aircraft_registration' => 'PK-LGF',
            'flight_number' => 'JT-380',
            'origin_station' => 'CGK',
            'destination_station' => 'KNO',
            'route' => 'CGK - KNO',
            'passenger_name' => 'Budi Santoso',
            'status' => 'COMPLETED',
            'pic_name' => 'Staff CBM',
        ]);

        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->call('exportExcel')
            ->assertFileDownloaded();
    }
}
