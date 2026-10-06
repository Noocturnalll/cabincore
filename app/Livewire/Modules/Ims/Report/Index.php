<?php

namespace App\Livewire\Modules\Ims\Report;

use Livewire\Component;
use App\Models\Ims\StockMovement;
use App\Models\Ims\Item;
use App\Models\Ims\Location;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Index extends Component
{
    use WithPagination;

    public $activeTab = 'mutasi'; // mutasi, pivot_stock, pivot_trx

    // mutasi filters
    public $type = '';
    public $startDate = '';
    public $endDate = '';
    
    // pivot filters
    public $searchPivot = '';

    public function updatingActiveTab() { $this->resetPage(); }
    public function updatingSearchPivot() { $this->resetPage(); }
    public function updatingType() { $this->resetPage(); }
    public function updatingStartDate() { $this->resetPage(); }
    public function updatingEndDate() { $this->resetPage(); }

    public function render()
    {
        $movements = collect();
        $pivotStockItems = collect();
        $pivotTrxItems = collect();
        $locations = collect();
        $months = [];

        if ($this->activeTab == 'mutasi') {
            $query = StockMovement::with(['item', 'location'])->orderBy('created_at', 'desc');
            if ($this->type) $query->where('movement_type', $this->type);
            if ($this->startDate) $query->whereDate('created_at', '>=', $this->startDate);
            if ($this->endDate) $query->whereDate('created_at', '<=', $this->endDate);
            $movements = $query->paginate(15);
        }
        elseif ($this->activeTab == 'pivot_stock') {
            $locations = Location::where('is_active', true)->orderBy('name')->get();
            $query = Item::with('stocks.location')->where('is_active', true);
            if ($this->searchPivot) {
                $query->where('name', 'like', '%'.$this->searchPivot.'%')
                      ->orWhere('part_number', 'like', '%'.$this->searchPivot.'%');
            }
            $pivotStockItems = $query->orderBy('name')->paginate(15);
        }
        elseif ($this->activeTab == 'pivot_trx') {
            // Get last 6 months
            for ($i = 5; $i >= 0; $i--) {
                $months[] = Carbon::now()->subMonths($i)->format('Y-m');
            }
            
            $query = Item::where('is_active', true);
            if ($this->searchPivot) {
                $query->where('name', 'like', '%'.$this->searchPivot.'%')
                      ->orWhere('part_number', 'like', '%'.$this->searchPivot.'%');
            }
            
            $pivotTrxItems = $query->orderBy('name')->paginate(15);
            
            // Get movements for these items within last 6 months
            $itemIds = $pivotTrxItems->pluck('id')->toArray();
            $startDate = Carbon::now()->subMonths(5)->startOfMonth();
            
            // DB driver aware month formatting
            $driver = DB::connection()->getDriverName();
            $monthSelect = $driver === 'sqlite' 
                ? "strftime('%Y-%m', created_at) as month" 
                : "DATE_FORMAT(created_at, '%Y-%m') as month";

            // Raw query to sum qty_change per item per month
            $movementsData = DB::table('ims_stock_movements')
                ->select(
                    'item_id',
                    DB::raw($monthSelect),
                    DB::raw("SUM(CASE WHEN qty_change > 0 THEN qty_change ELSE 0 END) as qty_in"),
                    DB::raw("SUM(CASE WHEN qty_change < 0 THEN ABS(qty_change) ELSE 0 END) as qty_out")
                )
                ->whereIn('item_id', $itemIds)
                ->where('created_at', '>=', $startDate)
                ->groupBy('item_id', 'month')
                ->get();
                
            // Attach to items
            foreach ($pivotTrxItems as $item) {
                $item->trx_data = [];
                foreach ($months as $m) {
                    $item->trx_data[$m] = ['in' => 0, 'out' => 0];
                }
            }
            
            foreach ($movementsData as $row) {
                $item = $pivotTrxItems->firstWhere('id', $row->item_id);
                if ($item && isset($item->trx_data[$row->month])) {
                    $item->trx_data[$row->month] = [
                        'in' => $row->qty_in,
                        'out' => $row->qty_out
                    ];
                }
            }
        }

        return view('livewire.modules.ims.report.index', [
            'movements' => $movements,
            'pivotStockItems' => $pivotStockItems,
            'pivotTrxItems' => $pivotTrxItems,
            'locations' => $locations,
            'months' => $months,
        ])->layout('components.layouts.app', ['title' => 'Laporan Mutasi Stok - IMS']);
    }
}
