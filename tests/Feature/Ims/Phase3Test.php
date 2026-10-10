<?php

namespace Tests\Feature\Ims;

use App\Helpers\RoleHelper;
use App\Livewire\Dashboard;
use App\Livewire\Modules\Ims\Approval\Index as ApprovalPage;
use App\Livewire\Modules\Ims\Repair\Index as RepairPage;
use App\Models\Ims\Category;
use App\Models\Ims\Item;
use App\Models\Ims\Location;
use App\Models\Ims\RepairCompleted;
use App\Models\Ims\RepairInProgress;
use App\Models\Ims\RepairLog;
use App\Models\Ims\RepairWaiting;
use App\Models\Ims\StockMovement;
use App\Models\Ims\Transaction;
use App\Models\Ims\TransactionItem;
use App\Models\Ims\Unit;
use App\Models\User;
use App\Services\Ims\RepairService;
use App\Services\Ims\StockService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Repair rack, hand-over, loan return, overdue reminders and the dashboard figures built on them. */
class Phase3Test extends TestCase
{
    use RefreshDatabase;

    private Item $item;

    private Location $shelf;

    private User $requester;

    private User $storekeeper;

    protected function setUp(): void
    {
        parent::setUp();

        $cat = Category::create(['code' => 'C1', 'name' => 'Cat']);
        $unit = Unit::create(['code' => 'PCS', 'name' => 'Pieces']);
        $this->shelf = Location::create(['code' => 'L1', 'name' => 'Rak A', 'type' => 'warehouse']);
        $this->item = Item::create(['part_number' => 'PN-1', 'name' => 'Oxygen Mask', 'description' => 'd', 'category_id' => $cat->id, 'unit_id' => $unit->id, 'tracking_type' => 'quantity']);

        foreach (['ims.approval.act', 'ims.approval.view', 'ims.stock.handover', 'ims.repair.request', 'ims.repair.manage', 'ims.repair.back_stage'] as $perm) {
            Permission::findOrCreate($perm, 'web');
        }
        $this->requester = User::factory()->create(['is_default_password' => false, 'status' => 'active']);
        $this->requester->givePermissionTo('ims.approval.view');
        $this->storekeeper = User::factory()->create(['is_default_password' => false, 'status' => 'active']);
        $this->storekeeper->givePermissionTo(['ims.approval.act', 'ims.approval.view', 'ims.stock.handover', 'ims.repair.request', 'ims.repair.manage', 'ims.repair.back_stage']);
    }

    // ── Repair rack ──────────────────────────────────────────────────────

    public function test_repair_actions_are_forbidden_without_the_permissions(): void
    {
        Livewire::actingAs($this->requester)->test(RepairPage::class)->assertForbidden();
        $this->assertSame(0, RepairWaiting::count());
    }

    public function test_receiving_a_faulty_part_needs_a_real_description(): void
    {
        Livewire::actingAs($this->storekeeper)->test(RepairPage::class)
            ->call('openReceive')
            ->set('itemId', $this->item->id)->set('qty', 1)->set('location_id', $this->shelf->id)->set('fault_description', 'bad')
            ->call('receive')->assertHasErrors('fault_description')
            ->set('fault_description', '')->call('receive')->assertHasErrors('fault_description');

        $this->assertSame(0, RepairWaiting::count());
    }

    public function test_the_whole_repair_flow_from_intake_to_stock(): void
    {
        $stock = app(StockService::class);
        $stock->receive($this->item->id, $this->shelf->id, 5);

        $page = Livewire::actingAs($this->storekeeper)->test(RepairPage::class)
            ->call('openReceive')
            ->set('itemId', $this->item->id)->set('qty', 2)->set('location_id', $this->shelf->id)->set('fault_description', 'Reservoir cracked')->set('aircraft_registration', 'pk-abc')
            ->call('receive')->assertHasNoErrors();

        $waiting = RepairWaiting::firstOrFail();
        $this->assertMatchesRegularExpression('/^RPR-\d{6}-0001$/', $waiting->repair_code);
        $this->assertSame('PK-ABC', $waiting->aircraft_registration);
        $this->assertSame(5, (int) $this->item->stocks()->first()->qty_on_hand, 'Intake does not touch serviceable stock.');

        $this->assertNull($waiting->accepted_at, 'Filed by COD, not yet accepted.');
        try {
            $page->call('openStart', $waiting->repair_code);
            $this->fail('A part that is not accepted yet cannot be started.');
        } catch (ModelNotFoundException) {
            $this->addToAssertionCount(1);
        }
        $page = Livewire::actingAs($this->storekeeper)->test(RepairPage::class);
        $page->call('accept', $waiting->repair_code);
        $this->assertNotNull($waiting->fresh()->accepted_at);

        $page->call('openStart', $waiting->repair_code)->set('work_order_no', 'WO-77')->call('start')->assertHasNoErrors();
        $this->assertSame(0, RepairWaiting::count());
        $this->assertSame(1, RepairWaiting::onlyTrashed()->count(), 'History is kept.');
        $progress = RepairInProgress::firstOrFail();
        $this->assertSame('WO-77', $progress->work_order_no);
        $this->assertSame('Reservoir cracked', $progress->fault_description);

        // serviceable needs a corrective action
        $page->call('openComplete', $waiting->repair_code)->set('result', 'serviceable')->set('findings', 'Crack at seam')->set('repaired_by_name', 'Budi')->call('complete')
            ->assertHasErrors('action_taken');
        $page->set('action_taken', 'Replaced reservoir')->call('complete')->assertHasNoErrors();
        $completed = RepairCompleted::firstOrFail();
        $this->assertSame('serviceable', $completed->result);

        $page->call('openReturn', $waiting->repair_code)->set('return_location_id', $this->shelf->id)->call('returnToStock')->assertHasNoErrors();
        $this->assertSame(7, (int) $this->item->stocks()->first()->qty_on_hand);
        $movement = StockMovement::where('movement_type', 'repair_return')->firstOrFail();
        $this->assertSame($waiting->repair_code, $movement->repair_code);
        $this->assertNotNull($completed->fresh()->returned_to_stock_at);

        // cannot be returned twice
        $this->expectException(ModelNotFoundException::class);
        $page->call('openReturn', $waiting->repair_code);
    }

    public function test_every_stage_change_is_logged_and_codes_do_not_repeat(): void
    {
        $page = Livewire::actingAs($this->storekeeper)->test(RepairPage::class);
        foreach ([1, 2] as $_) {
            $page->call('openReceive')->set('itemId', $this->item->id)->set('qty', 1)->set('location_id', $this->shelf->id)->set('fault_description', 'Handle broken')->call('receive');
        }
        $codes = RepairWaiting::pluck('repair_code')->all();
        $this->assertCount(2, array_unique($codes));

        $page->call('accept', $codes[0])->call('openStart', $codes[0])->call('start');
        $this->assertSame(['-> intake', '-> intake', 'intake -> waiting', 'waiting -> in_progress'], RepairLog::orderBy('id')->get()->map(fn ($l) => trim(($l->from_stage === '-' ? '' : $l->from_stage.' ').'-> '.$l->to_stage))->all());
    }

    public function test_an_unserviceable_result_cannot_go_back_to_stock(): void
    {
        $stock = app(StockService::class);
        $page = Livewire::actingAs($this->storekeeper)->test(RepairPage::class)
            ->call('openReceive')->set('itemId', $this->item->id)->set('qty', 1)->set('location_id', $this->shelf->id)->set('fault_description', 'Burnt out')->call('receive');
        $code = RepairWaiting::firstOrFail()->repair_code;
        $page->call('accept', $code)->call('openStart', $code)->call('start')
            ->call('openComplete', $code)->set('result', 'scrap')->set('findings', 'Beyond repair')->set('repaired_by_name', 'Budi')->call('complete')->assertHasNoErrors();

        $this->expectException(ModelNotFoundException::class);
        $page->call('openReturn', $code);
    }

    // ── Hand-over & loans ───────────────────────────────────────────────

    /** An approved loan of 3 pcs: stock 10, 3 left the warehouse. */
    private function approvedLoan(string $dueDate): Transaction
    {
        app(StockService::class)->receive($this->item->id, $this->shelf->id, 10);
        app(StockService::class)->reserve($this->item->id, $this->shelf->id, 3);

        $loan = Transaction::create([
            'code' => 'OUT-'.uniqid(), 'type' => 'out', 'status' => 'pending_approval', 'usage_type' => 'loan',
            'expected_return_date' => $dueDate, 'purpose_description' => 'Borrowed for PK-ABC',
            'requested_by' => $this->requester->id, 'requested_at' => now(), 'submitted_at' => now(),
        ]);
        TransactionItem::create(['transaction_id' => $loan->id, 'item_id' => $this->item->id, 'location_id' => $this->shelf->id, 'qty' => 3]);

        Livewire::actingAs($this->storekeeper)->test(ApprovalPage::class)->call('approve', $loan->id);
        $this->assertSame('approved', $loan->fresh()->status);

        return $loan->fresh();
    }

    public function test_handover_is_recorded_once_by_someone_allowed_to(): void
    {
        $loan = $this->approvedLoan(now()->addDays(5)->toDateString());

        Livewire::actingAs($this->requester)->test(ApprovalPage::class)->call('openHandover', $loan->id)->assertForbidden();

        $page = Livewire::actingAs($this->storekeeper)->test(ApprovalPage::class)->call('openHandover', $loan->id);
        $page->set('picked_up_by_name', '')->call('saveHandover')->assertHasErrors('picked_up_by_name');
        $page->set('picked_up_by_name', 'Budi Santoso')->set('handover_note', 'dengan tas')->call('saveHandover')->assertHasNoErrors();

        $loan->refresh();
        $this->assertSame('Budi Santoso', $loan->picked_up_by_name);
        $this->assertNotNull($loan->picked_up_at);
        $this->assertSame($this->storekeeper->id, (int) $loan->handed_over_by);

        $this->expectException(ModelNotFoundException::class);
        $page->call('openHandover', $loan->id); // already handed over
    }

    public function test_a_loan_must_be_received_back_and_then_stock_is_whole_again(): void
    {
        $loan = $this->approvedLoan(now()->addDays(2)->toDateString());
        $this->assertSame(7, (int) $this->item->stocks()->first()->qty_on_hand, 'The loaned pieces left at approval.');

        Livewire::actingAs($this->requester)->test(ApprovalPage::class)->call('receiveLoan', $loan->id)->assertForbidden();

        $page = Livewire::actingAs($this->storekeeper)->test(ApprovalPage::class);
        $this->assertSame(1, $page->viewData('counts')['loans']);

        $page->call('receiveLoan', $loan->id);

        $this->assertSame(10, (int) $this->item->stocks()->first()->qty_on_hand);
        $child = Transaction::where('parent_transaction_id', $loan->id)->firstOrFail();
        $this->assertSame('in', $child->type);
        $this->assertSame('loan_return', $child->usage_type);
        $this->assertSame(1, StockMovement::where('movement_type', 'loan_return')->count());
        $this->assertSame(0, $page->viewData('counts')['loans']);

        // a second click must not add the stock again
        $page->call('receiveLoan', $loan->id);
        $this->assertSame(10, (int) $this->item->stocks()->first()->qty_on_hand);
        $this->assertSame(1, Transaction::where('parent_transaction_id', $loan->id)->count());
    }

    public function test_a_consumed_item_cannot_be_received_back(): void
    {
        $loan = $this->approvedLoan(now()->addDay()->toDateString());
        $loan->update(['usage_type' => 'consume']);

        Livewire::actingAs($this->storekeeper)->test(ApprovalPage::class)->call('receiveLoan', $loan->id);

        $this->assertSame(7, (int) $this->item->stocks()->first()->qty_on_hand);
    }

    public function test_overdue_loans_are_counted_and_reminded_on_the_configured_days(): void
    {
        $dueToday = $this->approvedLoan(today()->toDateString());
        $late3 = $this->approvedLoan(today()->subDays(3)->toDateString());
        $late1 = $this->approvedLoan(today()->subDay()->toDateString());
        $future = $this->approvedLoan(today()->addDays(4)->toDateString());

        $this->assertSame(2, Livewire::actingAs($this->storekeeper)->test(ApprovalPage::class)->viewData('counts')['overdue']);

        $before = [$this->requester->notifications()->count(), $this->storekeeper->notifications()->count()];
        $this->artisan('ims:notify-overdue-loans')->assertSuccessful();

        // requester gets today + 3-days-late reminders (not 1 day late, not the future one); storekeeper too
        $this->assertSame($before[0] + 2, $this->requester->notifications()->count());
        $this->assertSame($before[1] + 2, $this->storekeeper->notifications()->count());
        $this->assertStringContainsString('jatuh tempo hari ini', $this->requester->notifications()->get()->pluck('data.message')->implode(' '));
    }

    // ── Dashboard figures ──────────────────────────────────────────────

    public function test_dashboard_counts_stock_in_out_from_movements_and_ignores_trashed_repairs(): void
    {
        Carbon::setTestNow('2026-10-08 19:00:00'); // operational day = 2026-10-08

        $stock = app(StockService::class);
        $stock->receive($this->item->id, $this->shelf->id, 10);          // in
        $stock->receive($this->item->id, $this->shelf->id, 1, null, 'loan_return');
        $stock->reserve($this->item->id, $this->shelf->id, 2);
        $stock->commitOut($this->item->id, $this->shelf->id, 2);          // out
        $stock->transfer($this->item->id, $this->shelf->id, Location::create(['code' => 'L2', 'name' => 'Rak B', 'type' => 'warehouse'])->id, 1);

        $repairs = app(RepairService::class);
        $this->actingAs($this->storekeeper);
        $repairs->receive(['item_id' => $this->item->id, 'qty' => 1, 'location_id' => $this->shelf->id, 'fault_description' => 'one']);
        $second = $repairs->receive(['item_id' => $this->item->id, 'qty' => 1, 'location_id' => $this->shelf->id, 'fault_description' => 'two']);
        $repairs->accept($second->repair_code);
        $repairs->start($second->repair_code);

        foreach (array_merge([RoleHelper::SUPER_ADMIN], RoleHelper::ALL_PIC) as $role) {
            Role::findOrCreate($role, 'web');
        }
        $admin = User::factory()->create(['is_default_password' => false, 'status' => 'active']);
        $admin->assignRole(RoleHelper::SUPER_ADMIN);

        $component = Livewire::actingAs($admin)->test(Dashboard::class)->instance();
        $method = new \ReflectionMethod($component, 'getDashboardStats');
        $ims = $method->invoke($component)['ims'];

        $this->assertSame(2, $ims['in'], 'receipt + loan return');
        $this->assertSame(1, $ims['out']);
        $this->assertSame(5, $ims['transactions'], '2 in, 1 out, 2 transfer legs');
        $this->assertSame(1, $ims['repair_waiting'], 'The moved one is trashed and must not count in the old stage.');
        $this->assertSame(1, $ims['repair_progress']);
    }
}
