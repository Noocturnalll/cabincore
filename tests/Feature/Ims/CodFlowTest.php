<?php

namespace Tests\Feature\Ims;

use App\Helpers\RoleHelper;
use App\Livewire\Modules\Ims\Approval\Index as ApprovalPage;
use App\Livewire\Modules\Ims\Repair\Index as RepairPage;
use App\Models\Ims\Category;
use App\Models\Ims\Item;
use App\Models\Ims\Location;
use App\Models\Ims\RepairWaiting;
use App\Models\Ims\Transaction;
use App\Models\Ims\TransactionItem;
use App\Models\Ims\Unit;
use App\Models\User;
use App\Services\Ims\StockService;
use Database\Seeders\RegistryPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** The COD desk: files faulty parts for repair, asks for parts for an aircraft, and gets told what happened. */
class CodFlowTest extends TestCase
{
    use RefreshDatabase;

    private Item $item;

    private Location $shelf;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RegistryPermissionSeeder::class);

        $this->shelf = Location::create(['code' => 'G1', 'name' => 'Rak Gudang A1', 'type' => 'warehouse']);
        $this->item = Item::create([
            'part_number' => '23842488-4', 'name' => 'Arm Assy', 'description' => 'd',
            'category_id' => Category::create(['code' => 'C1', 'name' => 'Cat'])->id,
            'unit_id' => Unit::create(['code' => 'PCS', 'name' => 'Pieces'])->id, 'tracking_type' => 'quantity',
        ]);
    }

    private function user(string $role): User
    {
        $u = User::factory()->create(['is_default_password' => false, 'status' => 'active']);
        $u->assignRole($role);

        return $u;
    }

    public function test_cod_files_the_form_and_the_repair_team_accepts_it(): void
    {
        $cod = $this->user(RoleHelper::COD);
        $store = $this->user(RoleHelper::PIC_SUPPORTING);

        Livewire::actingAs($cod)->test(RepairPage::class)
            ->call('openReceive')
            ->set('itemId', $this->item->id)->set('qty', 1)->set('location_id', $this->shelf->id)->set('fault_description', 'Arm cracked at the hinge')
            ->call('receive')->assertHasNoErrors();
        $code = RepairWaiting::firstOrFail()->repair_code;

        $this->assertSame(1, $store->notifications()->count(), 'The repair team is told a part is waiting for ACC.');
        $this->assertSame(0, $cod->notifications()->count());

        // COD files, it does not accept or work the part
        Livewire::actingAs($cod)->test(RepairPage::class)->call('accept', $code)->assertForbidden();

        Livewire::actingAs($store)->test(RepairPage::class)->call('accept', $code)->assertHasNoErrors();
        $this->assertNotNull(RepairWaiting::firstOrFail()->accepted_at);
        $this->assertSame(1, $cod->notifications()->count(), 'COD hears that the part was accepted.');
    }

    public function test_only_cod_and_the_store_can_file_a_faulty_part(): void
    {
        foreach ([RoleHelper::PIC_PAINTING, RoleHelper::PIC_AIEC, RoleHelper::PIC_FINISHING, RoleHelper::MANAGER] as $role) {
            Livewire::actingAs($this->user($role))->test(RepairPage::class)->call('openReceive')->assertForbidden();
        }
        foreach (RoleHelper::COD_DESK as $role) {
            Livewire::actingAs($this->user($role))->test(RepairPage::class)->call('openReceive')->assertOk();
        }
    }

    public function test_a_request_for_an_aircraft_notifies_approvers_and_stock_drops_on_approval(): void
    {
        $cod = $this->user(RoleHelper::COD);
        $manager = $this->user(RoleHelper::MANAGER);
        $stock = app(StockService::class);
        $stock->receive($this->item->id, $this->shelf->id, 4);
        $stock->reserve($this->item->id, $this->shelf->id, 1);

        $request = Transaction::create([
            'code' => 'OUT-1', 'type' => 'out', 'status' => 'pending_approval', 'usage_type' => 'consume',
            'purpose_description' => 'Replace arm assy on PK-LQA', 'aircraft_registration' => 'PK-LQA',
            'requested_by' => $cod->id, 'requested_at' => now(), 'submitted_at' => now(),
        ]);
        TransactionItem::create(['transaction_id' => $request->id, 'item_id' => $this->item->id, 'location_id' => $this->shelf->id, 'qty' => 1]);

        Livewire::actingAs($manager)->test(ApprovalPage::class)->call('approve', $request->id);

        $this->assertSame('approved', $request->fresh()->status);
        $this->assertSame(3, (int) $this->item->stocks()->first()->qty_on_hand, '4 on the shelf, 1 leaves.');
        $this->assertSame(1, $cod->notifications()->count());
        $this->assertStringContainsString('disetujui', $cod->notifications()->first()->data['title']);
    }

    public function test_a_rejection_tells_the_requester_why(): void
    {
        $cod = $this->user(RoleHelper::COD);
        $manager = $this->user(RoleHelper::MANAGER);
        app(StockService::class)->receive($this->item->id, $this->shelf->id, 4);
        app(StockService::class)->reserve($this->item->id, $this->shelf->id, 1);

        $request = Transaction::create([
            'code' => 'OUT-2', 'type' => 'out', 'status' => 'pending_approval', 'usage_type' => 'consume',
            'purpose_description' => 'Replace arm assy on PK-LQA', 'requested_by' => $cod->id, 'requested_at' => now(), 'submitted_at' => now(),
        ]);
        TransactionItem::create(['transaction_id' => $request->id, 'item_id' => $this->item->id, 'location_id' => $this->shelf->id, 'qty' => 1]);

        Livewire::actingAs($manager)->test(ApprovalPage::class)
            ->call('confirmReject', $request->id)->set('rejectReason', 'Pakai part dari pesawat lain')->call('reject');

        $this->assertStringContainsString('Pakai part dari pesawat lain', $cod->notifications()->first()->data['message']);
    }
}
