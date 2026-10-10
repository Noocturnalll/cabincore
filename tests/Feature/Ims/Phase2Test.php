<?php

namespace Tests\Feature\Ims;

use App\Livewire\Modules\Ims\Approval\Index;
use App\Models\Ims\Category;
use App\Models\Ims\Item;
use App\Models\Ims\Location;
use App\Models\Ims\Transaction;
use App\Models\Ims\TransactionItem;
use App\Models\Ims\Unit;
use App\Models\User;
use App\Services\Ims\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class Phase2Test extends TestCase
{
    use RefreshDatabase;

    protected $item;

    protected $location;

    protected $user;

    protected $approver;

    protected $transaction;

    protected function setUp(): void
    {
        parent::setUp();

        $cat = Category::create(['code' => 'C1', 'name' => 'Cat 1']);
        $unit = Unit::create(['code' => 'PCS', 'name' => 'Pieces']);

        $this->location = Location::create([
            'code' => 'L1',
            'name' => 'Loc 1',
            'type' => 'warehouse',
        ]);

        $this->item = Item::create([
            'part_number' => 'PN-123',
            'name' => 'Test Item',
            'description' => 'Test',
            'category_id' => $cat->id,
            'unit_id' => $unit->id,
            'tracking_type' => 'quantity',
        ]);

        $stockService = app(StockService::class);
        $stockService->receive($this->item->id, $this->location->id, 10);
        $stockService->reserve($this->item->id, $this->location->id, 2);

        $this->user = User::factory()->create();
        Permission::findOrCreate('ims.approval.act');
        Permission::findOrCreate('ims.approval.view');
        $this->approver = User::factory()->create();
        $this->approver->givePermissionTo(['ims.approval.act', 'ims.approval.view']);

        $this->transaction = Transaction::create([
            'code' => 'OUT-TEST-001',
            'type' => 'out',
            'status' => 'pending_approval',
            'purpose_description' => 'Test',
            'requested_by' => $this->user->id,
            'requested_at' => now(),
            'submitted_at' => now(),
        ]);

        TransactionItem::create([
            'transaction_id' => $this->transaction->id,
            'item_id' => $this->item->id,
            'location_id' => $this->location->id,
            'qty' => 2,
        ]);
    }

    public function test_user_without_permission_cannot_approve_or_reject()
    {
        $outsider = User::factory()->create();
        $outsider->givePermissionTo('ims.approval.view');

        Livewire::actingAs($outsider)
            ->test(Index::class)
            ->call('approve', $this->transaction->id)
            ->assertForbidden();

        $this->assertEquals('pending_approval', $this->transaction->fresh()->status);
    }

    public function test_a_requester_cannot_approve_their_own_request(): void
    {
        $this->user->givePermissionTo(['ims.approval.act', 'ims.approval.view']);

        Livewire::actingAs($this->user)->test(Index::class)->call('approve', $this->transaction->id);

        $this->assertEquals('pending_approval', $this->transaction->fresh()->status);
        $this->assertEquals(2, $this->item->stocks()->first()->qty_reserved, 'Reservation is untouched.');
    }

    public function test_approve_transaction_deducts_stock()
    {
        Livewire::actingAs($this->approver)
            ->test(Index::class)
            ->call('approve', $this->transaction->id)
            ->assertHasNoErrors();

        $this->transaction->refresh();
        $this->assertEquals('approved', $this->transaction->status);

        $stock = $this->item->stocks()->first();
        $this->assertEquals(8, $stock->qty_on_hand);
        $this->assertEquals(0, $stock->qty_reserved); // reserved was 2, now 0
    }

    public function test_reject_transaction_releases_stock()
    {
        Livewire::actingAs($this->approver)
            ->test(Index::class)
            ->set('selectedTransactionId', $this->transaction->id)
            ->set('rejectReason', 'Part not needed anymore')
            ->call('reject')
            ->assertHasNoErrors();

        $this->transaction->refresh();
        $this->assertEquals('rejected', $this->transaction->status);
        $this->assertEquals('Part not needed anymore', $this->transaction->rejected_reason);

        $stock = $this->item->stocks()->first();
        $this->assertEquals(10, $stock->qty_on_hand);
        $this->assertEquals(0, $stock->qty_reserved); // released
    }
}
