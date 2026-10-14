<?php

namespace Tests\Feature;

use App\Helpers\RoleHelper;
use App\Livewire\Modules\NsrdiOverdue\Index as NsrdiOverdueIndex;
use App\Models\Ims\Category;
use App\Models\Ims\Item;
use App\Models\Ims\Location;
use App\Models\Ims\Stock;
use App\Models\Ims\Unit;
use App\Models\NsrdiOverdue;
use App\Models\User;
use Database\Seeders\RegistryPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NsrdiOverdueImsTest extends TestCase
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

    private function createImsMasterData(): array
    {
        $cat = Category::create([
            'code' => 'CAT-TEST',
            'name' => 'Cabin Interior',
            'is_active' => true,
        ]);

        $unit = Unit::create([
            'code' => 'EA',
            'name' => 'EA',
            'is_active' => true,
        ]);

        $loc = Location::create([
            'code' => 'WH-TEST',
            'name' => 'Main Warehouse',
            'type' => 'warehouse',
            'is_active' => true,
        ]);

        return [$cat, $unit, $loc];
    }

    public function test_displays_ims_stock_ready_badge_when_stock_available(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user);

        [$cat, $unit, $loc] = $this->createImsMasterData();

        $item = Item::create([
            'part_number' => 'PN-12345',
            'name' => 'Recline Button Mechanism',
            'description' => 'Test mechanism',
            'category_id' => $cat->id,
            'unit_id' => $unit->id,
            'default_location_id' => $loc->id,
            'is_active' => true,
        ]);

        Stock::create([
            'item_id' => $item->id,
            'location_id' => $loc->id,
            'qty_on_hand' => 15,
            'qty_reserved' => 2,
        ]);

        NsrdiOverdue::create([
            'operator' => 'JT',
            'aircraft_registration' => 'PK-LQF',
            'nsrdi_number' => 'DEF-1001',
            'status' => 'Open',
            'part_number' => 'PN-12345',
            'description' => 'Seat 14A recline button stuck',
        ]);

        Livewire::test(NsrdiOverdueIndex::class)
            ->assertSee('PN-12345')
            ->assertSee('📦 IMS: 13 Ready')
            ->call('showImsDetail', 'PN-12345')
            ->assertSet('isImsModalOpen', true)
            ->assertSee('Detail Stok IMS')
            ->assertSee('Recline Button Mechanism')
            ->assertSee('Main Warehouse')
            ->call('closeImsModal')
            ->assertSet('isImsModalOpen', false);
    }

    public function test_displays_ims_zero_stock_badge_when_stock_empty(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user);

        [$cat, $unit, $loc] = $this->createImsMasterData();

        Item::create([
            'part_number' => 'PN-EMPTY-01',
            'name' => 'Armrest Cap',
            'description' => 'Test cap',
            'category_id' => $cat->id,
            'unit_id' => $unit->id,
            'default_location_id' => $loc->id,
            'is_active' => true,
        ]);

        NsrdiOverdue::create([
            'operator' => 'JT',
            'aircraft_registration' => 'PK-LQF',
            'nsrdi_number' => 'DEF-1002',
            'status' => 'Open',
            'part_number' => 'PN-EMPTY-01',
            'description' => 'Armrest cap missing',
        ]);

        Livewire::test(NsrdiOverdueIndex::class)
            ->assertSee('PN-EMPTY-01')
            ->assertSee('📦 IMS: Stok 0');
    }

    public function test_displays_register_to_ims_button_when_part_not_in_ims(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user);

        [$cat, $unit, $loc] = $this->createImsMasterData();

        $overdue = NsrdiOverdue::create([
            'operator' => 'JT',
            'aircraft_registration' => 'PK-LQZ',
            'nsrdi_number' => 'DEF-9999',
            'status' => 'Open',
            'part_number' => 'PN-NEW-999',
            'description' => 'Overhead bin latch broken',
        ]);

        Livewire::test(NsrdiOverdueIndex::class)
            ->assertSee('PN-NEW-999')
            ->assertSee('+ Daftarkan ke IMS')
            ->call('openQuickRegisterIms', 'PN-NEW-999', $overdue->id)
            ->assertSet('isQuickRegisterModalOpen', true)
            ->assertSet('newPartNumber', 'PN-NEW-999')
            ->set('newName', 'Overhead Bin Latch Unit')
            ->set('newCategoryId', $cat->id)
            ->set('newUnitId', $unit->id)
            ->set('newLocationId', $loc->id)
            ->set('newInitialStock', 8)
            ->call('saveQuickRegisterIms')
            ->assertDispatched('notify');

        $this->assertDatabaseHas('ims_items', [
            'part_number' => 'PN-NEW-999',
            'name' => 'Overhead Bin Latch Unit',
        ]);

        $this->assertDatabaseHas('ims_stocks', [
            'location_id' => $loc->id,
            'qty_on_hand' => 8,
        ]);
    }
}
