<?php

namespace Tests\Feature;

use App\Helpers\RoleHelper;
use App\Livewire\Modules\Assets\Index;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\Division;
use App\Models\Employee;
use App\Models\MasterEntry;
use App\Models\User;
use App\Services\Master\MasterSettings;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RegistryPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AssetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RegistryPermissionSeeder::class);
        $this->seed(MasterDataSeeder::class);
        MasterSettings::flush();
    }

    private function user(string $role, array $attrs = []): User
    {
        $u = User::factory()->create(['is_default_password' => false, 'status' => 'active'] + $attrs);
        $u->assignRole($role);

        return $u;
    }

    private function asset(array $attrs = []): Asset
    {
        return Asset::create($attrs + ['code' => 'STR-01', 'name' => 'Stretcher Boeing', 'category' => 'STRETCHER', 'condition' => 'BAIK', 'station' => 'CGK', 'qty_total' => 5, 'unit' => 'unit']);
    }

    private function employee(string $nik = '111', string $name = 'BUDI'): Employee
    {
        return Employee::create(['nik' => $nik, 'name' => $name, 'contract_type' => 'PKWTT', 'status' => 'Aktif', 'station' => 'CGK']);
    }

    public function test_availability_is_total_minus_units_out_and_zero_when_not_lendable(): void
    {
        $asset = $this->asset();
        $budi = $this->employee();
        AssetAssignment::create(['asset_id' => $asset->id, 'holder_type' => 'employee', 'employee_id' => $budi->id, 'holder_name' => 'BUDI', 'qty' => 2, 'assigned_at' => now()]);
        AssetAssignment::create(['asset_id' => $asset->id, 'holder_type' => 'station', 'holder_name' => 'SUB', 'qty' => 1, 'assigned_at' => now()->subWeek(), 'returned_at' => now()]);   // already back

        $this->assertSame(2, $asset->unitsInUse());
        $this->assertSame(3, $asset->unitsAvailable());

        $asset->update(['condition' => 'RUSAK']);
        $this->assertSame(0, $asset->fresh()->unitsAvailable(), 'a damaged asset cannot be lent');

        // making "Rusak" lendable in the master changes the answer: the master drives it
        MasterEntry::where('code', 'RUSAK')->update(['attrs' => ['available' => 'Ya']]);
        MasterSettings::flush();
        $this->assertSame(3, $asset->fresh()->unitsAvailable());
    }

    public function test_lend_and_give_back_through_the_page_with_history(): void
    {
        $asset = $this->asset();
        $budi = $this->employee();
        $pic = $this->user(RoleHelper::MANAGER);

        $c = Livewire::actingAs($pic)->test(Index::class)
            ->call('openLend', $asset->id)->assertSet('lendOpen', true)
            ->set('employeeSearch', 'BUD')->assertSee('BUDI')
            ->call('chooseEmployee', $budi->id)
            ->set('lendQty', 2)->set('dueBack', now()->addWeek()->toDateString())->call('lend')->assertHasNoErrors();

        $this->assertSame(3, $asset->fresh()->unitsAvailable());
        $a = AssetAssignment::first();
        $this->assertSame($budi->id, $a->employee_id);
        $this->assertSame('BUDI', $a->holder_name);

        $c->assertSee('Riwayat Stretcher Boeing')->assertSee('BUDI')->assertSee('Dipakai');   // lending opens the history
        $c->call('giveBack', $a->id);
        $this->assertSame(5, $asset->fresh()->unitsAvailable());
        $c->assertSee('Kembali');
    }

    public function test_cannot_lend_more_than_is_available_or_to_a_nobody(): void
    {
        $asset = $this->asset(['qty_total' => 2]);
        $budi = $this->employee();
        $manager = $this->user(RoleHelper::MANAGER);

        Livewire::actingAs($manager)->test(Index::class)->call('openLend', $asset->id)
            ->set('lendQty', 1)->call('lend')->assertHasErrors('employeeId')
            ->call('chooseEmployee', $budi->id)->set('lendQty', 3)->call('lend')->assertHasErrors('lendQty');

        $this->assertSame(0, AssetAssignment::count());
    }

    public function test_register_changes_respect_what_is_still_out_and_the_permission(): void
    {
        $asset = $this->asset(['qty_total' => 5]);
        AssetAssignment::create(['asset_id' => $asset->id, 'holder_type' => 'station', 'holder_name' => 'CGK', 'qty' => 3, 'assigned_at' => now()]);
        $manager = $this->user(RoleHelper::MANAGER);

        Livewire::actingAs($manager)->test(Index::class)->call('edit', $asset->id)
            ->set('qty_total', 2)->call('save')->assertHasErrors('qty_total')       // 3 are out
            ->set('qty_total', 4)->call('save')->assertHasNoErrors();
        $this->assertSame(4, $asset->fresh()->qty_total);

        Livewire::actingAs($manager)->test(Index::class)->call('delete', $asset->id);
        $this->assertNotNull(Asset::find($asset->id), 'an asset that is still out cannot be deleted');

        $viewer = User::factory()->create(['is_default_password' => false, 'status' => 'active']);
        $viewer->givePermissionTo('asset.view');
        Livewire::actingAs($viewer)->test(Index::class)->call('create')->assertForbidden();
        Livewire::actingAs($viewer)->test(Index::class)->call('openLend', $asset->id)->assertForbidden();
    }

    public function test_new_asset_uses_master_categories_and_a_unique_code(): void
    {
        $this->asset();
        $manager = $this->user(RoleHelper::MANAGER);

        Livewire::actingAs($manager)->test(Index::class)->call('create')
            ->set('code', 'str-01')->set('name', 'Dobel')->set('asset_category', 'STRETCHER')->call('save')->assertHasErrors('code')
            ->set('code', 'GSE-9')->set('asset_category', 'TIDAK ADA')->call('save')->assertHasErrors('asset_category')
            ->set('asset_category', 'GSE')->set('qty_total', 3)->call('save')->assertHasNoErrors();

        $this->assertSame('GSE-9', Asset::where('name', 'Dobel')->value('code'));
    }

    public function test_a_pic_sees_only_assets_of_their_division_or_station(): void
    {
        $cabin = Division::where('name', 'Cabin')->value('id');
        $painting = Division::where('name', 'Painting')->value('id');
        $this->asset(['code' => 'A1', 'name' => 'Milik Cabin', 'division_id' => $cabin, 'station' => null]);
        $this->asset(['code' => 'A2', 'name' => 'Milik Painting', 'division_id' => $painting, 'station' => null]);
        $this->asset(['code' => 'A3', 'name' => 'Di station SUB', 'division_id' => null, 'station' => 'SUB']);

        $pic = $this->user(RoleHelper::PIC_CABIN, ['division_id' => $cabin, 'station' => 'SUB']);
        Livewire::actingAs($pic)->test(Index::class)->assertSee('Milik Cabin')->assertSee('Di station SUB')->assertDontSee('Milik Painting');

        $manager = $this->user(RoleHelper::MANAGER);
        Livewire::actingAs($manager)->test(Index::class)->assertSee('Milik Painting');

        $nobody = User::factory()->create(['is_default_password' => false, 'status' => 'active']);
        $this->actingAs($nobody)->get('/assets')->assertForbidden();
    }
}
