<?php

namespace Tests\Feature\Ims;

use App\Livewire\Modules\Ims\Katalog\Index as KatalogIndex;
use App\Livewire\Modules\Ims\Peminjaman\Index;
use App\Models\Ims\Category;
use App\Models\Ims\Item;
use App\Models\Ims\Location;
use App\Models\Ims\Transaction;
use App\Models\Ims\Unit;
use App\Models\User;
use App\Services\Ims\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class Phase1Test extends TestCase
{
    use RefreshDatabase;

    protected $item;

    protected $location;

    protected $user;

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

        // Add 10 stock
        $stockService = app(StockService::class);
        $stockService->receive($this->item->id, $this->location->id, 10);

        // Dummy user
        $this->user = User::factory()->create();
        Permission::findOrCreate('ims.catalog.view');
        Permission::findOrCreate('ims.request.create');
        $this->user->givePermissionTo(['ims.catalog.view', 'ims.request.create']);
    }

    public function test_katalog_shows_available_stock()
    {
        Livewire::actingAs($this->user)->test(KatalogIndex::class)
            ->assertSee('PN-123')
            ->assertSee('10') // Available stock
            ->assertSee('Tersedia');
    }

    public function test_submit_request_reserves_stock()
    {
        session()->put('ims_picklist', [
            $this->item->id => [
                'id' => $this->item->id,
                'name' => $this->item->name,
                'part_number' => $this->item->part_number,
                'qty' => 3,
                'location_id' => $this->location->id,
            ],
        ]);

        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->set('purpose_description', 'For maintenance of aircraft PK-ABC')
            ->set('usage_type', 'consume')
            ->call('submitRequest')
            ->assertHasNoErrors()
            ->tap(function () {
                if (session()->has('error')) {
                    dump(session('error'));
                }
            });

        $transaction = Transaction::where('type', 'out')->first();
        $this->assertNotNull($transaction);
        $this->assertEquals('pending_approval', $transaction->status);
        $this->assertEquals(3, $transaction->items()->first()->qty);

        // Check if reserved
        $stock = $this->item->stocks()->first();
        $this->assertEquals(10, $stock->qty_on_hand);
        $this->assertEquals(3, $stock->qty_reserved);
    }

    public function test_cancel_request_releases_stock()
    {
        $this->test_submit_request_reserves_stock(); // setup the request

        $transaction = Transaction::first();

        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->call('cancelRequest', $transaction->id)
            ->assertHasNoErrors();

        $transaction->refresh();
        $this->assertEquals('cancelled', $transaction->status);

        // Check if reserved released
        $stock = $this->item->stocks()->first();
        $this->assertEquals(10, $stock->qty_on_hand);
        $this->assertEquals(0, $stock->qty_reserved);
    }
}
