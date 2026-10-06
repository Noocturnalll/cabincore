<?php

namespace Tests\Feature\Ims;

use App\Models\Ims\Item;
use App\Models\Ims\Location;
use App\Models\Ims\Category;
use App\Models\Ims\Unit;
use App\Models\Ims\Stock;
use App\Models\Ims\StockMovement;
use App\Services\Ims\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $stockService;
    protected $item;
    protected $location;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stockService = new StockService();

        $cat = Category::create(['code' => 'C1', 'name' => 'Cat 1']);
        $unit = Unit::create(['code' => 'PCS', 'name' => 'Pieces']);
        
        $this->location = Location::create([
            'code' => 'L1', 
            'name' => 'Loc 1', 
            'type' => 'warehouse'
        ]);

        $this->item = Item::create([
            'part_number' => 'PN-123',
            'name' => 'Test Item',
            'description' => 'Test',
            'category_id' => $cat->id,
            'unit_id' => $unit->id,
            'tracking_type' => 'quantity'
        ]);
    }

    public function test_receive_adds_stock()
    {
        $this->stockService->receive($this->item->id, $this->location->id, 10);
        
        $stock = Stock::where('item_id', $this->item->id)->where('location_id', $this->location->id)->first();
        $this->assertEquals(10, $stock->qty_on_hand);
        
        $movement = StockMovement::first();
        $this->assertEquals('in', $movement->movement_type);
        $this->assertEquals(10, $movement->qty_change);
    }

    public function test_reserve_stock()
    {
        $this->stockService->receive($this->item->id, $this->location->id, 10);
        $this->stockService->reserve($this->item->id, $this->location->id, 5);

        $stock = Stock::first();
        $this->assertEquals(10, $stock->qty_on_hand);
        $this->assertEquals(5, $stock->qty_reserved);
    }

    public function test_reserve_insufficient_stock_throws_exception()
    {
        $this->stockService->receive($this->item->id, $this->location->id, 10);
        
        $this->expectException(\Exception::class);
        $this->stockService->reserve($this->item->id, $this->location->id, 15);
    }

    public function test_commit_out_reduces_stock_and_reserved()
    {
        $this->stockService->receive($this->item->id, $this->location->id, 10);
        $this->stockService->reserve($this->item->id, $this->location->id, 5);
        
        $this->stockService->commitOut($this->item->id, $this->location->id, 5);

        $stock = Stock::first();
        $this->assertEquals(5, $stock->qty_on_hand);
        $this->assertEquals(0, $stock->qty_reserved);
        
        $movement = StockMovement::where('movement_type', 'out')->first();
        $this->assertEquals(-5, $movement->qty_change);
        $this->assertEquals(10, $movement->balance_before);
        $this->assertEquals(5, $movement->balance_after);
    }

    public function test_transfer_stock()
    {
        $loc2 = Location::create(['code' => 'L2', 'name' => 'Loc 2', 'type' => 'warehouse']);
        $this->stockService->receive($this->item->id, $this->location->id, 10);
        
        $this->stockService->transfer($this->item->id, $this->location->id, $loc2->id, 4);

        $stock1 = Stock::where('location_id', $this->location->id)->first();
        $stock2 = Stock::where('location_id', $loc2->id)->first();

        $this->assertEquals(6, $stock1->qty_on_hand);
        $this->assertEquals(4, $stock2->qty_on_hand);
    }
}
