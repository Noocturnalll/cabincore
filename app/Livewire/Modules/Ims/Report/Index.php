<?php

namespace App\Livewire\Modules\Ims\Report;

use App\Models\Ims\Item;
use App\Models\Ims\Location;
use App\Models\Ims\StockMovement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

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

    public function updatingActiveTab()
    {
        $this->resetPage();
    }

    public function updatingSearchPivot()
    {
        $this->resetPage();
    }

    public function updatingType()
    {
        $this->resetPage();
    }

    public function updatingStartDate()
    {
        $this->resetPage();
    }

    public function updatingEndDate()
    {
        $this->resetPage();
    }

    public function exportMutasi()
    {
        abort_unless(auth()->user()?->can('ims.report.export'), 403, 'Anda tidak memiliki akses untuk mengekspor laporan.');

        $query = StockMovement::with(['item', 'location'])->orderBy('created_at', 'desc');
        if ($this->type) {
            $query->where('movement_type', $this->type);
        }
        if ($this->startDate) {
            $query->whereDate('created_at', '>=', $this->startDate);
        }
        if ($this->endDate) {
            $query->whereDate('created_at', '<=', $this->endDate);
        }

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM so Excel reads UTF-8
            fputcsv($out, ['Tanggal', 'Part Number', 'Barang', 'Lokasi', 'Tipe', 'Sebelum', 'Sesudah', 'Perubahan']);
            $query->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $m) {
                    fputcsv($out, [
                        $m->created_at?->format('Y-m-d H:i'), $m->item->part_number ?? '', $m->item->name ?? '',
                        $m->location->name ?? '', $m->movement_type, $m->balance_before, $m->balance_after, $m->qty_change,
                    ]);
                }
            });
            fclose($out);
        }, 'mutasi-stok-'.date('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render()
    {
        $movements = collect();
        $pivotStockItems = collect();
        $pivotTrxItems = collect();
        $locations = collect();
        $months = [];

        if ($this->activeTab == 'mutasi') {
            $query = StockMovement::with(['item', 'location'])->orderBy('created_at', 'desc');
            if ($this->type) {
                $query->where('movement_type', $this->type);
            }
            if ($this->startDate) {
                $query->whereDate('created_at', '>=', $this->startDate);
            }
            if ($this->endDate) {
                $query->whereDate('created_at', '<=', $this->endDate);
            }
            $movements = $query->paginate(15);
        } elseif ($this->activeTab == 'pivot_stock') {
            $locations = Location::where('is_active', true)->orderBy('name')->get();
            $query = Item::with('stocks.location')->where('is_active', true);
            if ($this->searchPivot) {
                $query->where(fn ($q) => $q->where('name', 'like', '%'.$this->searchPivot.'%')
                    ->orWhere('part_number', 'like', '%'.$this->searchPivot.'%'));
            }
            $pivotStockItems = $query->orderBy('name')->paginate(15);
        } elseif ($this->activeTab == 'pivot_trx') {
            // Get last 6 months
            for ($i = 5; $i >= 0; $i--) {
                $months[] = Carbon::now()->subMonths($i)->format('Y-m');
            }

            $query = Item::where('is_active', true);
            if ($this->searchPivot) {
                $query->where(fn ($q) => $q->where('name', 'like', '%'.$this->searchPivot.'%')
                    ->orWhere('part_number', 'like', '%'.$this->searchPivot.'%'));
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
                    DB::raw('SUM(CASE WHEN qty_change > 0 THEN qty_change ELSE 0 END) as qty_in'),
                    DB::raw('SUM(CASE WHEN qty_change < 0 THEN ABS(qty_change) ELSE 0 END) as qty_out')
                )
                ->whereIn('item_id', $itemIds)
                ->where('created_at', '>=', $startDate)
                ->groupBy('item_id', 'month')
                ->get();

            // Build item => month => in/out in plain arrays (Eloquent attributes cannot be modified in place)
            $blank = [];
            foreach ($months as $m) {
                $blank[$m] = ['in' => 0, 'out' => 0];
            }
            $matrix = [];
            foreach ($movementsData as $row) {
                $matrix[$row->item_id][$row->month] = ['in' => (int) $row->qty_in, 'out' => (int) $row->qty_out];
            }

            foreach ($pivotTrxItems as $item) {
                $item->setAttribute('trx_data', array_replace($blank, $matrix[$item->id] ?? []));
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
