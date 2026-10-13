<?php

namespace App\Livewire\Modules\Painting;

use App\Exports\PaintingDailyReportExport;
use App\Livewire\Traits\WithLogTable;
use App\Models\NsrdiLog;
use App\Services\Reports\ProductionExecutiveService;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class DailyReport extends Component
{
    use WithLogTable, WithPagination;

    public $activeTab = 'dja-painting';

    public $search = '';

    public $dateFilter = '';

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedDateFilter(): void
    {
        $this->resetPage();
    }

    public function clearSearch(): void
    {
        $this->search = '';
        $this->resetPage();
    }

    public function openWaModal(): void
    {
        $date = $this->dateFilter ?: now()->toDateString();
        $this->dispatch('open-wa-report-modal',
            module: 'painting',
            startDate: $date,
            endDate: $date,
            station: null
        );
    }

    public function exportExcel()
    {
        return Excel::download(
            new PaintingDailyReportExport($this->search, $this->dateFilter, $this->activeTab),
            'DailyReport-AircraftPainting-'.date('Y-m-d').'.xlsx'
        );
    }

    public function render()
    {
        $activeDate = now()->hour >= 18 ? now()->format('Y-m-d') : now()->subDays(1)->format('Y-m-d');
        $logs = [];
        $summaryData = [];

        if ($this->activeTab === 'dja-painting') {
            $query = NsrdiLog::where('category', 'PAINTING')
                ->where('is_submitted', true)
                ->where(function ($q) use ($activeDate) {
                    $q->where(function ($x) use ($activeDate) {
                        $x->whereNotNull('dja_id')->whereHas('dailyJobAssignment', function ($d) use ($activeDate) {
                            $d->whereDate('date', '<', $activeDate);
                        });
                    })->orWhere(function ($x) use ($activeDate) {
                        $x->whereNull('dja_id')->where('import_source', 'dja')->whereDate('plan_date', '<', $activeDate);
                    });
                });

            if ($this->search) {
                $query->where(function ($q) {
                    $q->where('aircraft_registration', 'like', '%'.$this->search.'%')
                        ->orWhere('nsrdi_number', 'like', '%'.$this->search.'%')
                        ->orWhere('plan_station', 'like', '%'.$this->search.'%')
                        ->orWhere('act_station', 'like', '%'.$this->search.'%')
                        ->orWhere('description', 'like', '%'.$this->search.'%')
                        ->orWhere('aoc', 'like', '%'.$this->search.'%');
                });
            }

            if ($this->dateFilter) {
                $query->where(function ($q) {
                    $q->whereHas('dailyJobAssignment', function ($d) {
                        $d->whereDate('date', $this->dateFilter);
                    })->orWhere(function ($x) {
                        $x->whereNull('dja_id')->where('import_source', 'dja')->whereDate('plan_date', $this->dateFilter);
                    });
                });
            }

            $logs = $query->orderByRaw('COALESCE(plan_date, created_at) DESC')->orderByDesc('id')->paginate($this->perPage);
        } elseif ($this->activeTab === 'unplanned-painting') {
            $query = NsrdiLog::where('category', 'PAINTING')
                ->whereNull('dja_id')
                ->where('is_submitted', true)
                ->where(function ($q) {
                    $q->whereNull('import_source')->orWhere('import_source', 'unplanned');
                })
                ->whereRaw('DATE(COALESCE(plan_date, report_date)) < ?', [$activeDate]);

            if ($this->search) {
                $query->where(function ($q) {
                    $q->where('aircraft_registration', 'like', '%'.$this->search.'%')
                        ->orWhere('nsrdi_number', 'like', '%'.$this->search.'%')
                        ->orWhere('plan_station', 'like', '%'.$this->search.'%')
                        ->orWhere('act_station', 'like', '%'.$this->search.'%')
                        ->orWhere('description', 'like', '%'.$this->search.'%')
                        ->orWhere('aoc', 'like', '%'.$this->search.'%');
                });
            }

            if ($this->dateFilter) {
                $query->whereRaw('DATE(COALESCE(plan_date, report_date)) = ?', [$this->dateFilter]);
            }

            $logs = $query->orderByRaw('COALESCE(plan_date, report_date) DESC')->orderByDesc('id')->paginate($this->perPage);
        } elseif ($this->activeTab === 'summary') {
            $summaryQuery = NsrdiLog::where('category', 'PAINTING')
                ->where('is_submitted', true);

            if ($this->dateFilter) {
                $summaryQuery->where(function ($q) {
                    $q->whereDate('plan_date', $this->dateFilter)
                        ->orWhereDate('report_date', $this->dateFilter)
                        ->orWhereHas('dailyJobAssignment', fn ($d) => $d->whereDate('date', $this->dateFilter));
                });
            } else {
                $summaryQuery->whereRaw('DATE(COALESCE(plan_date, report_date)) < ?', [$activeDate]);
            }

            if ($this->search) {
                $summaryQuery->where(function ($q) {
                    $q->where('aircraft_registration', 'like', '%'.$this->search.'%')
                        ->orWhere('nsrdi_number', 'like', '%'.$this->search.'%')
                        ->orWhere('plan_station', 'like', '%'.$this->search.'%')
                        ->orWhere('act_station', 'like', '%'.$this->search.'%')
                        ->orWhere('description', 'like', '%'.$this->search.'%')
                        ->orWhere('aoc', 'like', '%'.$this->search.'%');
                });
            }

            $items = $summaryQuery->get();
            $total = $items->count();
            $closed = $items->where('status', 'Closed')->count();
            $open = $total - $closed;
            $rate = $total > 0 ? round(($closed / $total) * 100, 1) : 0;

            $plannedCount = $items->whereNotNull('dja_id')->count();
            $unplannedCount = $items->whereNull('dja_id')->count();

            $jobTypes = ['Paint Peel Off (PPO)', 'Livery & Exterior Marking', 'Placards & Signage', 'Paint Touch Up', 'Interior Painting', 'Repaint / General Paint'];

            // Matrix Station vs Job Type
            $stationMatrix = [];
            foreach ($items as $item) {
                $sta = strtoupper(trim($item->plan_station ?: ($item->act_station ?: 'N/A')));
                $type = ProductionExecutiveService::categorizePaintingJob($item->description ?? '');

                if (! isset($stationMatrix[$sta])) {
                    $stationMatrix[$sta] = [
                        'station' => $sta,
                        'types' => array_fill_keys($jobTypes, 0),
                        'total' => 0,
                        'closed' => 0,
                        'open' => 0,
                    ];
                }

                $stationMatrix[$sta]['types'][$type]++;
                $stationMatrix[$sta]['total']++;
                if (($item->status ?? '') === 'Closed') {
                    $stationMatrix[$sta]['closed']++;
                } else {
                    $stationMatrix[$sta]['open']++;
                }
            }
            uasort($stationMatrix, fn ($a, $b) => $b['total'] <=> $a['total']);

            // Group by station
            $byStation = $items->groupBy(fn ($i) => $i->plan_station ?: ($i->act_station ?: 'N/A'))->map(function ($group, $sta) {
                $tot = $group->count();
                $cls = $group->where('status', 'Closed')->count();

                return [
                    'station' => $sta,
                    'total' => $tot,
                    'closed' => $cls,
                    'open' => $tot - $cls,
                    'rate' => $tot > 0 ? round(($cls / $tot) * 100, 1) : 0,
                ];
            })->sortByDesc('total')->values()->all();

            // Group by AOC
            $byAoc = $items->groupBy(fn ($i) => $i->aoc ?: 'N/A')->map(function ($group, $aoc) {
                $tot = $group->count();
                $cls = $group->where('status', 'Closed')->count();

                return [
                    'aoc' => $aoc,
                    'total' => $tot,
                    'closed' => $cls,
                    'open' => $tot - $cls,
                    'rate' => $tot > 0 ? round(($cls / $tot) * 100, 1) : 0,
                ];
            })->sortByDesc('total')->values()->all();

            $summaryData = [
                'total' => $total,
                'closed' => $closed,
                'open' => $open,
                'rate' => $rate,
                'planned' => $plannedCount,
                'unplanned' => $unplannedCount,
                'jobTypes' => $jobTypes,
                'stationMatrix' => $stationMatrix,
                'byStation' => $byStation,
                'byAoc' => $byAoc,
            ];
        }

        // Held painting logs that are past cutoff but not submitted
        $heldCount = NsrdiLog::where('category', 'PAINTING')
            ->where('status', '!=', 'Closed')
            ->where('is_submitted', false)
            ->whereRaw('DATE(COALESCE(plan_date, report_date)) < ?', [$activeDate])
            ->count();

        return view('livewire.modules.painting.daily-report', [
            'logs' => $logs,
            'summaryData' => $summaryData,
            'cutoffDate' => $activeDate,
            'heldCount' => $heldCount,
        ])->layout('components.layouts.app', ['title' => 'Daily Report - Aircraft Painting']);
    }
}
