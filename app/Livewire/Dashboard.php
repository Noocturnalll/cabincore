<?php

namespace App\Livewire;

use App\Helpers\RoleHelper;
use App\Models\Airport;
use App\Models\IctFinding;
use App\Models\User;
use App\Support\DashboardScope;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class Dashboard extends Component
{
    public string $period = 'daily';

    private function scope(): DashboardScope
    {
        return DashboardScope::for(auth()->user());
    }

    private function range(): array
    {
        $period = $this->effectivePeriod();

        $active = now()->hour >= 18 ? now() : now()->subDay();

        [$from, $to] = match ($period) {
            'weekly' => [$active->copy()->startOfWeek(), $active->copy()],
            'monthly' => [$active->copy()->startOfMonth(), $active->copy()],
            default => [$active->copy(), $active->copy()],
        };

        return [$from->format('Y-m-d'), $to->format('Y-m-d')];
    }

    /** Range as full timestamps, for datetime columns such as created_at. */
    private function rangeTimestamps(): array
    {
        [$from, $to] = $this->range();

        return [$from.' 00:00:00', $to.' 23:59:59'];
    }

    /** Period actually used, limited to what the user's role may select. */
    public function effectivePeriod(): string
    {
        return in_array($this->period, $this->scope()->periods, true) ? $this->period : 'daily';
    }

    private function has(string $module): bool
    {
        return in_array($module, $this->scope()->modules, true);
    }

    public function updatedPeriod()
    {
        $this->dispatch('dashboard-updated');
    }

    private function applyStationFilter($query, string $table, ?string $djaJoinTable = null)
    {
        $scope = $this->scope();
        if ($scope->stations === null) {
            return $query;
        }

        if ($djaJoinTable) {
            $query->whereIn("{$djaJoinTable}.station", $scope->stations);
        } elseif ($table === 'nsrdi_logs') {
            $query->where(fn ($q) => $q->whereIn('nsrdi_logs.act_station', $scope->stations)
                ->orWhereIn('nsrdi_logs.plan_station', $scope->stations));
        } else {
            $column = match ($table) {
                'wo_logs', 'dmi_logs' => 'act_station',
                'cml_logs', 'aircraft_cleanings' => 'station',
                default => null,
            };
            if ($column) {
                $query->whereIn("{$table}.{$column}", $scope->stations);
            }
        }

        return $query;
    }

    private function getDashboardStats(): array
    {
        $scope = $this->scope();
        [$from, $to] = $this->range();
        $activeCarbon = now()->hour >= 18 ? now() : now()->subDays(1);
        $targetDate = $activeCarbon->format('Y-m-d');

        // ─── 1. WO (Planned & Unplanned) ────────────────────────────────────
        $woAgg = $this->applyStationFilter(DB::table('wo_logs'), 'wo_logs')
            ->whereBetween('date', [$from, $to])
            ->selectRaw("
                SUM(CASE WHEN dja_id IS NOT NULL THEN 1 ELSE 0 END) as dja_total,
                SUM(CASE WHEN dja_id IS NOT NULL AND status = 'Open' THEN 1 ELSE 0 END) as dja_open,
                SUM(CASE WHEN dja_id IS NOT NULL AND status = 'Closed' THEN 1 ELSE 0 END) as dja_closed,
                SUM(CASE WHEN dja_id IS NULL THEN 1 ELSE 0 END) as unplanned_total,
                SUM(CASE WHEN dja_id IS NULL AND status = 'Open' THEN 1 ELSE 0 END) as unplanned_open,
                SUM(CASE WHEN dja_id IS NULL AND status = 'Closed' THEN 1 ELSE 0 END) as unplanned_closed
            ")->first();

        $wo_dja_total = (int) ($woAgg->dja_total ?? 0);
        $wo_dja_open = (int) ($woAgg->dja_open ?? 0);
        $wo_dja_closed = (int) ($woAgg->dja_closed ?? 0);
        $wo_unplanned_total = (int) ($woAgg->unplanned_total ?? 0);
        $wo_unplanned_open = (int) ($woAgg->unplanned_open ?? 0);
        $wo_unplanned_closed = (int) ($woAgg->unplanned_closed ?? 0);

        // ─── 2. DMI (Planned & Unplanned) ───────────────────────────────────
        $dmiAgg = $this->applyStationFilter(DB::table('dmi_logs'), 'dmi_logs')
            ->whereBetween('date', [$from, $to])
            ->selectRaw("
                SUM(CASE WHEN dja_id IS NOT NULL THEN 1 ELSE 0 END) as dja_total,
                SUM(CASE WHEN dja_id IS NOT NULL AND status = 'Open' THEN 1 ELSE 0 END) as dja_open,
                SUM(CASE WHEN dja_id IS NOT NULL AND status = 'Closed' THEN 1 ELSE 0 END) as dja_closed,
                SUM(CASE WHEN dja_id IS NULL THEN 1 ELSE 0 END) as unplanned_total,
                SUM(CASE WHEN dja_id IS NULL AND status = 'Open' THEN 1 ELSE 0 END) as unplanned_open,
                SUM(CASE WHEN dja_id IS NULL AND status = 'Closed' THEN 1 ELSE 0 END) as unplanned_closed
            ")->first();

        $dmi_dja_total = (int) ($dmiAgg->dja_total ?? 0);
        $dmi_dja_open = (int) ($dmiAgg->dja_open ?? 0);
        $dmi_dja_closed = (int) ($dmiAgg->dja_closed ?? 0);
        $dmi_unplanned_total = (int) ($dmiAgg->unplanned_total ?? 0);
        $dmi_unplanned_open = (int) ($dmiAgg->unplanned_open ?? 0);
        $dmi_unplanned_closed = (int) ($dmiAgg->unplanned_closed ?? 0);

        // ─── 3. NSRDI Planned (plan_date) & Unplanned (report_date) ─────────
        $nsrdiDjaAgg = $this->applyStationFilter(DB::table('nsrdi_logs'), 'nsrdi_logs')
            ->whereBetween('plan_date', [$from, $to])
            ->whereNotNull('dja_id')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'Open' THEN 1 ELSE 0 END) as open_count,
                SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) as closed_count
            ")->first();

        $nsrdi_dja_total = (int) ($nsrdiDjaAgg->total ?? 0);
        $nsrdi_dja_open = (int) ($nsrdiDjaAgg->open_count ?? 0);
        $nsrdi_dja_closed = (int) ($nsrdiDjaAgg->closed_count ?? 0);

        $nsrdiUnplannedAgg = $this->applyStationFilter(DB::table('nsrdi_logs'), 'nsrdi_logs')
            ->whereBetween('report_date', [$from, $to])
            ->whereNull('dja_id')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'Open' THEN 1 ELSE 0 END) as open_count,
                SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) as closed_count
            ")->first();

        $nsrdi_unplanned_total = (int) ($nsrdiUnplannedAgg->total ?? 0);
        $nsrdi_unplanned_open = (int) ($nsrdiUnplannedAgg->open_count ?? 0);
        $nsrdi_unplanned_closed = (int) ($nsrdiUnplannedAgg->closed_count ?? 0);

        // ─── 4. CML ─────────────────────────────────────────────────────────
        $cmlAgg = $this->applyStationFilter(DB::table('cml_logs'), 'cml_logs')
            ->whereBetween('date', [$from, $to])
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'Open' THEN 1 ELSE 0 END) as open_count,
                SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) as closed_count
            ")->first();

        $cml_total = (int) ($cmlAgg->total ?? 0);
        $cml_open = (int) ($cmlAgg->open_count ?? 0);
        $cml_closed = (int) ($cmlAgg->closed_count ?? 0);

        // ─── 5. ICT Findings ────────────────────────────────────────────────
        $hasIct = $this->has('ict');
        $ictAgg = $hasIct ? DB::table('ict_findings')
            ->whereBetween('date', [$from, $to])
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'Open' THEN 1 ELSE 0 END) as open_count,
                SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) as closed_count
            ")->first() : null;

        $ict_total = (int) ($ictAgg->total ?? 0);
        $ict_open = (int) ($ictAgg->open_count ?? 0);
        $ict_closed = (int) ($ictAgg->closed_count ?? 0);

        // ICT breakdown by operator (daily)
        $ict_breakdown_raw = DB::table('ict_findings')
            ->select(
                'operator',
                DB::raw("SUM(CASE WHEN status = 'Open' THEN 1 ELSE 0 END) as open_count"),
                DB::raw("SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) as closed_count"),
                DB::raw('COUNT(*) as total_count')
            )
            ->whereBetween('date', [$from, $to])
            ->when(! $hasIct, fn ($q) => $q->whereRaw('1 = 0'))
            ->groupBy('operator')
            ->get();

        $ict_breakdown = [];
        foreach ($ict_breakdown_raw as $item) {
            $op = $item->operator ?: 'Unknown';
            $ict_breakdown[$op] = [
                'open' => $item->open_count,
                'closed' => $item->closed_count,
                'total' => $item->total_count,
            ];
        }

        // ICT monthly chart
        $monthStart = $activeCarbon->copy()->startOfMonth()->format('Y-m-d');
        $monthEnd = $activeCarbon->copy()->endOfMonth()->format('Y-m-d');

        $ict_monthly_raw = DB::table('ict_findings')
            ->select(
                'operator',
                DB::raw("SUM(CASE WHEN status = 'Open' THEN 1 ELSE 0 END) as open_count"),
                DB::raw("SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) as closed_count")
            )
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->when(! $hasIct, fn ($q) => $q->whereRaw('1 = 0'))
            ->groupBy('operator')
            ->get();

        $ict_charts = [
            'daily' => ['labels' => [], 'open' => [], 'closed' => []],
            'monthly' => ['labels' => [], 'open' => [], 'closed' => []],
        ];

        foreach ($ict_breakdown as $op => $data) {
            $ict_charts['daily']['labels'][] = $op;
            $ict_charts['daily']['open'][] = $data['open'];
            $ict_charts['daily']['closed'][] = $data['closed'];
        }

        foreach ($ict_monthly_raw as $item) {
            $op = $item->operator ?: 'Unknown';
            $ict_charts['monthly']['labels'][] = $op;
            $ict_charts['monthly']['open'][] = $item->open_count;
            $ict_charts['monthly']['closed'][] = $item->closed_count;
        }

        // ─── 4b. IMS & Repair ───────────────────────────────────────────────
        [$tsFrom, $tsTo] = $this->rangeTimestamps();
        $hasIms = $this->has('ims');
        // Stock is warehouse-wide (locations have no station), so IMS figures are not station-filtered.
        // Source of truth is the stock movement log: it covers requests, direct receipts, transfers, adjustments, loan
        // returns and repair returns alike.
        $imsMoves = fn () => DB::table('ims_stock_movements')->whereBetween('created_at', [$tsFrom, $tsTo]);

        $ims_transactions = $hasIms ? $imsMoves()->count() : 0;
        $ims_transactions_in = $hasIms ? $imsMoves()->whereIn('movement_type', ['in', 'loan_return', 'repair_return'])->count() : 0;
        $ims_transactions_out = $hasIms ? $imsMoves()->where('movement_type', 'out')->count() : 0;

        $repair_waiting = $hasIms ? DB::table('ims_repair_waiting')->whereNull('deleted_at')->count() : 0;
        $repair_progress = $hasIms ? DB::table('ims_repair_in_progress')->whereNull('deleted_at')->count() : 0;
        $repair_completed_today = $hasIms ? DB::table('ims_repair_completed')->whereBetween('completed_at', [$tsFrom, $tsTo])->count() : 0;
        $repair_completed_all = $hasIms ? DB::table('ims_repair_completed')->count() : 0;

        // ─── 5. Aggregations ────────────────────────────────────────────────
        $dja_total_laporan = $wo_dja_total + $dmi_dja_total + $nsrdi_dja_total;
        $dja_total_closed = $wo_dja_closed + $dmi_dja_closed + $nsrdi_dja_closed;
        $dja_total_open = $wo_dja_open + $dmi_dja_open + $nsrdi_dja_open;
        $dja_close_rate = $dja_total_laporan > 0
            ? round(($dja_total_closed / $dja_total_laporan) * 100)
            : 0;

        $unplanned_total = $wo_unplanned_total + $dmi_unplanned_total + $nsrdi_unplanned_total;
        $unplanned_closed = $wo_unplanned_closed + $dmi_unplanned_closed + $nsrdi_unplanned_closed;
        $unplanned_open = $wo_unplanned_open + $dmi_unplanned_open + $nsrdi_unplanned_open;
        $unplanned_close_rate = $unplanned_total > 0
            ? round(($unplanned_closed / $unplanned_total) * 100)
            : 0;

        // ─── 6. Station Stats ────────────────────────────────────────────────
        $stationsList = [
            'CGK', 'HLP', 'SUB', 'KNO', 'UPG', 'MDC', 'BPN',
            'AMQ', 'SRG', 'PLM', 'DPS', 'KOE', 'LOP', 'SOC',
            'PDG', 'BTH', 'PKU', 'YIA',
        ];

        $stationStats = [];
        foreach ($stationsList as $s) {
            $stationStats[$s] = [
                'total' => 0,
                'closed' => 0,
                'open' => 0,
                'details' => [
                    'wo' => ['closed' => 0, 'open' => 0],
                    'dmi' => ['closed' => 0, 'open' => 0],
                    'nsrdi' => ['closed' => 0, 'open' => 0],
                    'ac_gc' => ['closed' => 0, 'open' => 0],
                    'ac_dci' => ['closed' => 0, 'open' => 0],
                    'ac_dce' => ['closed' => 0, 'open' => 0],
                    'ac_tc' => ['closed' => 0, 'open' => 0],
                ],
            ];
        }

        // DJA-linked logs station stats (join to get DJA station)
        $djaLogTables = [
            'wo_logs' => 'date',
            'dmi_logs' => 'date',
            'nsrdi_logs' => 'plan_date',
        ];

        foreach ($djaLogTables as $table => $dateCol) {
            $type = str_replace('_logs', '', $table);

            $stationData = DB::table($table)
                ->join('daily_job_assignments', "{$table}.dja_id", '=', 'daily_job_assignments.id')
                ->select(
                    'daily_job_assignments.station',
                    DB::raw('COUNT(*) as total'),
                    DB::raw("SUM(CASE WHEN {$table}.status = 'Closed' THEN 1 ELSE 0 END) as closed_count"),
                    DB::raw("SUM(CASE WHEN {$table}.status = 'Open' THEN 1 ELSE 0 END) as open_count")
                )
                ->whereBetween("{$table}.{$dateCol}", [$from, $to])
                ->when($scope->stations, fn ($q) => $q->whereIn('daily_job_assignments.station', $scope->stations))
                ->groupBy('daily_job_assignments.station')
                ->get();

            foreach ($stationData as $row) {
                $station = $row->station ?: 'Unknown';
                if (! isset($stationStats[$station])) {
                    continue;
                }
                $stationStats[$station]['total'] += $row->total;
                $stationStats[$station]['closed'] += $row->closed_count;
                $stationStats[$station]['open'] += $row->open_count;
                $stationStats[$station]['details'][$type]['closed'] += $row->closed_count;
                $stationStats[$station]['details'][$type]['open'] += $row->open_count;
            }
        }

        // Aircraft Cleaning station stats (uses own station column)
        $acStationData = $this->applyStationFilter(DB::table('aircraft_cleanings'), 'aircraft_cleanings')
            ->select(
                'station',
                'type',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) as closed_count"),
                DB::raw("SUM(CASE WHEN status = 'Open' THEN 1 ELSE 0 END) as open_count")
            )
            ->whereBetween('date', [$from, $to])
            ->groupBy('station', 'type')
            ->get();

        $acTypeMap = ['General' => 'ac_gc', 'DCI' => 'ac_dci', 'DCE' => 'ac_dce', 'Transit' => 'ac_tc'];

        foreach ($acStationData as $row) {
            $station = $row->station ?: 'Unknown';
            if (! isset($stationStats[$station])) {
                continue;
            }
            $acType = $acTypeMap[$row->type] ?? null;

            $stationStats[$station]['total'] += $row->total;
            $stationStats[$station]['closed'] += $row->closed_count;
            $stationStats[$station]['open'] += $row->open_count;

            if ($acType) {
                $stationStats[$station]['details'][$acType]['closed'] += $row->closed_count;
                $stationStats[$station]['details'][$acType]['open'] += $row->open_count;
            }
        }

        // ─── 7. 7-Day Trend ─────────────────────────────────────────────────
        $trendLabels = [];

        $targetDates = [];
        $periodMode = $this->effectivePeriod();
        $loopCount = match ($periodMode) {
            'weekly' => 8, 'monthly' => 6, default => 7
        };

        $trendCmlClosed = array_fill(0, $loopCount, 0);
        $trendAcTotal = array_fill(0, $loopCount, 0);
        $trendDja = array_fill(0, $loopCount, 0);
        $trendUnplanned = array_fill(0, $loopCount, 0);

        for ($i = $loopCount - 1; $i >= 0; $i--) {
            if ($periodMode === 'weekly') {
                $d = $activeCarbon->copy()->subWeeks($i);
                $trendLabels[] = 'W'.$d->weekOfYear;
                $targetDates[] = [$d->copy()->startOfWeek()->format('Y-m-d'), $d->copy()->endOfWeek()->format('Y-m-d')];
            } elseif ($periodMode === 'monthly') {
                $d = $activeCarbon->copy()->subMonths($i);
                $trendLabels[] = $d->translatedFormat('M Y');
                $targetDates[] = [$d->copy()->startOfMonth()->format('Y-m-d'), $d->copy()->endOfMonth()->format('Y-m-d')];
            } else {
                $d = $activeCarbon->copy()->subDays($i);
                $trendLabels[] = $d->translatedFormat('d M');
                $targetDates[] = [$d->format('Y-m-d'), $d->format('Y-m-d')];
            }
        }

        $getDayIndex = function (string $dateStr) use ($targetDates): int {
            $dateStr = substr($dateStr, 0, 10);
            foreach ($targetDates as $idx => $range) {
                if ($dateStr >= $range[0] && $dateStr <= $range[1]) {
                    return $idx;
                }
            }

            return -1;
        };

        $startDateStr = $targetDates[0][0];
        $endDateStr = $targetDates[$loopCount - 1][1];

        // CML closed trend
        $cmlTrend = $this->applyStationFilter(DB::table('cml_logs'), 'cml_logs')
            ->where('status', 'Closed')
            ->whereBetween('date', [$startDateStr, $endDateStr])
            ->select('date', DB::raw('COUNT(*) as total'))
            ->groupBy('date')
            ->pluck('total', 'date');

        foreach ($cmlTrend as $dateVal => $count) {
            $idx = $getDayIndex((string) $dateVal);
            if ($idx >= 0) {
                $trendCmlClosed[$idx] += (int) $count;
            }
        }

        // Aircraft Cleaning trend
        $acTrend = $this->applyStationFilter(DB::table('aircraft_cleanings'), 'aircraft_cleanings')
            ->whereBetween('date', [$startDateStr, $endDateStr])
            ->select('date', DB::raw('COUNT(*) as total'))
            ->groupBy('date')
            ->pluck('total', 'date');

        foreach ($acTrend as $dateVal => $count) {
            $idx = $getDayIndex((string) $dateVal);
            if ($idx >= 0) {
                $trendAcTotal[$idx] += (int) $count;
            }
        }

        // DJA and Unplanned trend
        foreach (['wo_logs', 'dmi_logs'] as $t) {
            $logs = $this->applyStationFilter(DB::table($t), $t)
                ->whereBetween('date', [$startDateStr, $endDateStr])
                ->select(
                    'date as the_date',
                    DB::raw('SUM(CASE WHEN dja_id IS NOT NULL THEN 1 ELSE 0 END) as dja_count'),
                    DB::raw('SUM(CASE WHEN dja_id IS NULL THEN 1 ELSE 0 END) as unplanned_count')
                )
                ->groupBy('the_date')
                ->get();

            foreach ($logs as $row) {
                $idx = $getDayIndex((string) $row->the_date);
                if ($idx >= 0) {
                    $trendDja[$idx] += (int) $row->dja_count;
                    $trendUnplanned[$idx] += (int) $row->unplanned_count;
                }
            }
        }

        // NSRDI trend: DJA uses plan_date; unplanned uses report_date
        $nsrdiDja = $this->applyStationFilter(DB::table('nsrdi_logs'), 'nsrdi_logs')
            ->whereNotNull('dja_id')
            ->whereBetween('plan_date', [$startDateStr, $endDateStr])
            ->select('plan_date as the_date', DB::raw('COUNT(*) as total'))
            ->groupBy('plan_date')
            ->pluck('total', 'the_date');

        foreach ($nsrdiDja as $dateVal => $count) {
            $idx = $getDayIndex((string) $dateVal);
            if ($idx >= 0) {
                $trendDja[$idx] += (int) $count;
            }
        }

        $nsrdiUnplanned = $this->applyStationFilter(DB::table('nsrdi_logs'), 'nsrdi_logs')
            ->whereNull('dja_id')
            ->whereBetween('report_date', [$startDateStr, $endDateStr])
            ->select('report_date as the_date', DB::raw('COUNT(*) as total'))
            ->groupBy('report_date')
            ->pluck('total', 'the_date');

        foreach ($nsrdiUnplanned as $dateVal => $count) {
            $idx = $getDayIndex((string) $dateVal);
            if ($idx >= 0) {
                $trendUnplanned[$idx] += (int) $count;
            }
        }

        // ─── 8. Aircraft Cleaning Detail (today) ────────────────────────────
        $ac_raw = $this->applyStationFilter(DB::table('aircraft_cleanings'), 'aircraft_cleanings')
            ->whereBetween('date', [$from, $to])
            ->select(
                'type',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) as closed_count"),
                DB::raw("SUM(CASE WHEN status = 'Open' THEN 1 ELSE 0 END) as open_count")
            )
            ->groupBy('type')
            ->get()
            ->keyBy('type');

        $acGet = fn ($type, $key) => $ac_raw->get($type)?->{$key} ?? 0;

        $ac_total = $this->applyStationFilter(DB::table('aircraft_cleanings'), 'aircraft_cleanings')->whereBetween('date', [$from, $to])->count();
        $ac_closed = $this->applyStationFilter(DB::table('aircraft_cleanings'), 'aircraft_cleanings')->whereBetween('date', [$from, $to])->where('status', 'Closed')->count();
        $ac_open = $this->applyStationFilter(DB::table('aircraft_cleanings'), 'aircraft_cleanings')->whereBetween('date', [$from, $to])->where('status', 'Open')->count();

        $ac_gc_total = $acGet('General', 'total');
        $ac_gc_closed = $acGet('General', 'closed_count');
        $ac_gc_open = $acGet('General', 'open_count');

        $ac_dci_total = $acGet('DCI', 'total');
        $ac_dci_closed = $acGet('DCI', 'closed_count');
        $ac_dci_open = $acGet('DCI', 'open_count');

        $ac_dce_total = $acGet('DCE', 'total');
        $ac_dce_closed = $acGet('DCE', 'closed_count');
        $ac_dce_open = $acGet('DCE', 'open_count');

        $ac_tc_total = $acGet('Transit', 'total');
        $ac_tc_closed = $acGet('Transit', 'closed_count');
        $ac_tc_open = $acGet('Transit', 'open_count');

        // AC close rates
        $ac_close_rate = $ac_total > 0 ? round(($ac_closed / $ac_total) * 100) : 0;
        $ac_gc_close_rate = $ac_gc_total > 0 ? round(($ac_gc_closed / $ac_gc_total) * 100) : 0;
        $ac_dci_close_rate = $ac_dci_total > 0 ? round(($ac_dci_closed / $ac_dci_total) * 100) : 0;
        $ac_dce_close_rate = $ac_dce_total > 0 ? round(($ac_dce_closed / $ac_dce_total) * 100) : 0;
        $ac_tc_close_rate = $ac_tc_total > 0 ? round(($ac_tc_closed / $ac_tc_total) * 100) : 0;

        // AC per operator (monthly)
        $acMonthlyByOperator = $this->applyStationFilter(DB::table('aircraft_cleanings'), 'aircraft_cleanings')
            ->select(
                'operator',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) as closed_count")
            )
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->groupBy('operator')
            ->get();

        $acOperatorChart = ['labels' => [], 'total' => [], 'closed' => []];
        foreach ($acMonthlyByOperator as $row) {
            $acOperatorChart['labels'][] = $row->operator ?: 'Unknown';
            $acOperatorChart['total'][] = $row->total;
            $acOperatorChart['closed'][] = $row->closed_count;
        }

        // ─── 9. Man Power & Man Hours ────────────────────────────────────────
        $man_power = User::role(RoleHelper::ALL_PIC)
            ->when($scope->stations, fn ($q) => $q->whereIn('station', $scope->stations))
            ->count();
        $man_hours = $this->applyStationFilter(DB::table('wo_logs'), 'wo_logs')->whereBetween('date', [$from, $to])->sum('man_hour') ?: 0;

        // ─── 10. NSRDI Overdue (Open & past due_date) ────────────────────────
        $nsrdiOverdueMap = [
            'Batik Air' => 0,
            'Lion Air' => 0,
            'Super Air Jet' => 0,
            'Wings Air' => 0,
        ];

        $nsrdiOverdueRaw = $this->applyStationFilter(DB::table('nsrdi_logs'), 'nsrdi_logs')
            ->select('aoc', DB::raw('COUNT(*) as overdue_count'))
            ->where('status', 'Open')
            ->whereDate('due_date', '<', now()->format('Y-m-d'))
            ->whereNotNull('aoc')
            ->groupBy('aoc')
            ->get();

        foreach ($nsrdiOverdueRaw as $row) {
            $aocStr = strtolower(trim($row->aoc));
            if (str_contains($aocStr, 'batik')) {
                $nsrdiOverdueMap['Batik Air'] += $row->overdue_count;
            } elseif (str_contains($aocStr, 'lion')) {
                $nsrdiOverdueMap['Lion Air'] += $row->overdue_count;
            } elseif (str_contains($aocStr, 'saj') || str_contains($aocStr, 'super')) {
                $nsrdiOverdueMap['Super Air Jet'] += $row->overdue_count;
            } elseif (str_contains($aocStr, 'wings')) {
                $nsrdiOverdueMap['Wings Air'] += $row->overdue_count;
            }
        }

        // ─── 11. Overall / All-time summary ──────────────────────────────────
        $overall = [
            'wo' => $this->applyStationFilter(DB::table('wo_logs'), 'wo_logs')->count(),
            'cml' => $this->applyStationFilter(DB::table('cml_logs'), 'cml_logs')->count(),
            'dmi' => $this->applyStationFilter(DB::table('dmi_logs'), 'dmi_logs')->count(),
            'nsrdi' => $this->applyStationFilter(DB::table('nsrdi_logs'), 'nsrdi_logs')->count(),
            'ict' => $hasIct ? DB::table('ict_findings')->count() : 0,
            'ict_open' => $hasIct ? DB::table('ict_findings')->where('status', 'Open')->count() : 0,
            'ac' => $this->applyStationFilter(DB::table('aircraft_cleanings'), 'aircraft_cleanings')->count(),
            'ims_transactions' => $hasIms ? DB::table('ims_stock_movements')->count() : 0,
            'repair_total' => $repair_waiting + $repair_progress + $repair_completed_all,
        ];

        // ─── 12. Recurring "No Spare" NSRDI ─────────────────────────────────
        $thirtyDaysAgo = $activeCarbon->copy()->subDays(30)->format('Y-m-d');

        $activeNsrdisToday = $this->applyStationFilter(DB::table('nsrdi_logs'), 'nsrdi_logs')
            ->where(function ($q) use ($targetDate, $from, $to) {
                $q->whereBetween('plan_date', [$from, $to])
                    ->orWhereDate('report_date', $targetDate)
                    ->orWhere('status', 'Open');
            })
            ->get();

        $recurringNs = [];
        $seenNs = [];

        $nsrdiNumbers = $activeNsrdisToday->pluck('nsrdi_number')->filter()->unique()->values()->all();

        $pastNsGrouped = collect();
        if (! empty($nsrdiNumbers)) {
            $maxId = $activeNsrdisToday->max('id');
            $pastNsGrouped = $this->applyStationFilter(DB::table('nsrdi_logs'), 'nsrdi_logs')
                ->whereIn('nsrdi_number', $nsrdiNumbers)
                ->where('id', '<', $maxId)
                ->where(function ($q) {
                    $q->where('hold_reason_category', 'like', '%NS%')
                        ->orWhere('code_open', 'like', '%NS%')
                        ->orWhere('reason_open', 'like', '%NO SPARE%')
                        ->orWhere('reason_open', 'like', '%NS%');
                })
                ->where(function ($q) use ($thirtyDaysAgo, $targetDate) {
                    $q->whereBetween('plan_date', [$thirtyDaysAgo, $targetDate])
                        ->orWhereBetween('report_date', [$thirtyDaysAgo, $targetDate]);
                })
                ->orderBy('id', 'desc')
                ->get()
                ->groupBy('nsrdi_number');
        }

        foreach ($activeNsrdisToday as $log) {
            if (! $log->nsrdi_number || in_array($log->nsrdi_number, $seenNs)) {
                continue;
            }

            $candidates = $pastNsGrouped->get($log->nsrdi_number);
            $pastNs = $candidates ? $candidates->firstWhere('id', '<', $log->id) : null;

            if ($pastNs) {
                $log->past_ns_date = $pastNs->plan_date ?? $pastNs->report_date;
                $log->past_ns_reason = $pastNs->reason_open ?? $pastNs->code_open;
                $recurringNs[] = $log;
                $seenNs[] = $log->nsrdi_number;
            }
        }

        return [
            'target_date' => $targetDate,
            'range' => [$from, $to],

            'dja' => [
                'total' => $dja_total_laporan,
                'closed' => $dja_total_closed,
                'open' => $dja_total_open,
                'close_rate' => $dja_close_rate,
                'wo_total' => $wo_dja_total,
                'wo_closed' => $wo_dja_closed,
                'wo_open' => $wo_dja_open,
                'dmi_total' => $dmi_dja_total,
                'dmi_closed' => $dmi_dja_closed,
                'dmi_open' => $dmi_dja_open,
                'nsrdi_total' => $nsrdi_dja_total,
                'nsrdi_closed' => $nsrdi_dja_closed,
                'nsrdi_open' => $nsrdi_dja_open,
            ],

            'unplanned' => [
                'total' => $unplanned_total,
                'closed' => $unplanned_closed,
                'open' => $unplanned_open,
                'close_rate' => $unplanned_close_rate,
                'wo_total' => $wo_unplanned_total,
                'wo_closed' => $wo_unplanned_closed,
                'wo_open' => $wo_unplanned_open,
                'dmi_total' => $dmi_unplanned_total,
                'dmi_closed' => $dmi_unplanned_closed,
                'dmi_open' => $dmi_unplanned_open,
                'nsrdi_total' => $nsrdi_unplanned_total,
                'nsrdi_closed' => $nsrdi_unplanned_closed,
                'nsrdi_open' => $nsrdi_unplanned_open,
                'all_rate' => $unplanned_close_rate,
            ],

            'cml' => [
                'total' => $cml_total,
                'closed' => $cml_closed,
                'open' => $cml_open,
            ],

            'ict' => [
                'total' => $ict_total,
                'closed' => $ict_closed,
                'open' => $ict_open,
                'breakdown' => $ict_breakdown,
                'charts' => $ict_charts,
            ],

            'ac' => [
                'total' => $ac_total,
                'closed' => $ac_closed,
                'open' => $ac_open,
                'close_rate' => $ac_close_rate,
                'gc_total' => $ac_gc_total,
                'gc_closed' => $ac_gc_closed,
                'gc_open' => $ac_gc_open,
                'gc_close_rate' => $ac_gc_close_rate,
                'dci_total' => $ac_dci_total,
                'dci_closed' => $ac_dci_closed,
                'dci_open' => $ac_dci_open,
                'dci_close_rate' => $ac_dci_close_rate,
                'dce_total' => $ac_dce_total,
                'dce_closed' => $ac_dce_closed,
                'dce_open' => $ac_dce_open,
                'dce_close_rate' => $ac_dce_close_rate,
                'tc_total' => $ac_tc_total,
                'tc_closed' => $ac_tc_closed,
                'tc_open' => $ac_tc_open,
                'tc_close_rate' => $ac_tc_close_rate,
                'operator_chart' => $acOperatorChart,
            ],

            'ims' => [
                'transactions' => $ims_transactions,
                'in' => $ims_transactions_in,
                'out' => $ims_transactions_out,
                'repair_waiting' => $repair_waiting,
                'repair_progress' => $repair_progress,
                'repair_completed' => $repair_completed_today,
            ],

            'cml_closed' => $cml_closed,
            'dja_close_rate' => $dja_close_rate,
            'stationStats' => $stationStats,
            'trendLabels' => $trendLabels,
            'trendCmlClosed' => $trendCmlClosed,
            'trendAcTotal' => $trendAcTotal,
            'trendDja' => $trendDja,
            'trendUnplanned' => $trendUnplanned,
            'totalOpen' => $dja_total_open + $unplanned_open,
            'totalClosed' => $dja_total_closed + $unplanned_closed + $cml_closed,
            'overall' => $overall,
            'nsrdi_overdue' => $nsrdiOverdueMap,
            'man_power' => $man_power,
            'man_hours' => $man_hours,
            'recurring_ns' => $recurringNs,
        ];
    }

    /**
     * Build KPI chart datasets for the last 30 days (ending on the active date).
     *
     * @return array<string, mixed>
     */
    private function getKpiStats(): array
    {
        $activeCarbon = now()->hour >= 18 ? now() : now()->subDays(1);
        $endDate = $activeCarbon->format('Y-m-d');
        $startDate = $activeCarbon->copy()->subDays(29)->format('Y-m-d');
        $monthStart = $startDate;
        $targetPercent = 90;

        $dates = [];
        $labels = [];
        for ($i = 29; $i >= 0; $i--) {
            $day = $activeCarbon->copy()->subDays($i);
            $dates[] = $day->format('Y-m-d');
            $labels[] = $day->translatedFormat('d M');
        }
        $dateIndex = array_flip($dates);

        $nsrdiDateExpr = 'DATE(CASE WHEN dja_id IS NOT NULL THEN plan_date ELSE report_date END)';
        $sources = [
            'WO' => ['table' => 'wo_logs', 'date' => 'DATE(date)'],
            'DMI' => ['table' => 'dmi_logs', 'date' => 'DATE(date)'],
            'NSRDI' => ['table' => 'nsrdi_logs', 'date' => $nsrdiDateExpr],
            'CML' => ['table' => 'cml_logs', 'date' => 'DATE(date)'],
            'AC' => ['table' => 'aircraft_cleanings', 'date' => 'DATE(date)'],
            'ICT' => ['table' => 'ict_findings', 'date' => 'DATE(date)'],
        ];

        $dailyBySource = [];
        $dailyTotal = array_fill(0, 30, 0);
        $dailyClosed = array_fill(0, 30, 0);
        $composition = [];
        $monthTotals = ['total' => 0, 'closed' => 0, 'open' => 0];

        foreach ($sources as $key => $source) {
            $rows = $this->applyStationFilter(DB::table($source['table']), $source['table'])
                ->selectRaw("{$source['date']} as d, COUNT(*) as total, SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) as closed_count")
                ->whereRaw("{$source['date']} BETWEEN ? AND ?", [$startDate, $endDate])
                ->groupBy('d')
                ->get();

            $series = array_fill(0, 30, 0);
            $composition[$key] = 0;

            foreach ($rows as $row) {
                if (! isset($dateIndex[$row->d])) {
                    continue;
                }
                $idx = $dateIndex[$row->d];
                $series[$idx] = (int) $row->total;
                $dailyTotal[$idx] += (int) $row->total;
                $dailyClosed[$idx] += (int) $row->closed_count;

                if ($row->d >= $monthStart) {
                    $composition[$key] += (int) $row->total;
                    $monthTotals['total'] += (int) $row->total;
                    $monthTotals['closed'] += (int) $row->closed_count;
                }
            }

            $dailyBySource[$key] = $series;
        }
        $monthTotals['open'] = $monthTotals['total'] - $monthTotals['closed'];

        // Line: daily close rate (%)
        $dailyRate = array_map(
            fn (int $total, int $closed): ?float => $total > 0 ? round($closed / $total * 100, 1) : null,
            $dailyTotal,
            $dailyClosed
        );

        // Area: cumulative volume per source
        $cumulative = [];
        foreach (['WO', 'DMI', 'NSRDI', 'CML'] as $key) {
            $running = 0;
            $cumulative[$key] = array_map(function (int $value) use (&$running): int {
                $running += $value;

                return $running;
            }, $dailyBySource[$key]);
        }

        // Station performance (month-to-date) from Airport master
        $kpiStations = $this->scope()->stations;
        $stationCodes = Airport::where('status', 'Aktif')
            ->when($kpiStations, fn ($q) => $q->whereIn('kode', $kpiStations))
            ->orderBy('kode')->pluck('kode')->all();
        $stationPerf = array_fill_keys($stationCodes, ['total' => 0, 'closed' => 0]);

        foreach (['wo_logs' => 'date', 'dmi_logs' => 'date', 'nsrdi_logs' => 'plan_date'] as $table => $dateCol) {
            $rows = DB::table($table)
                ->join('daily_job_assignments', "{$table}.dja_id", '=', 'daily_job_assignments.id')
                ->selectRaw("daily_job_assignments.station as station, COUNT(*) as total, SUM(CASE WHEN {$table}.status = 'Closed' THEN 1 ELSE 0 END) as closed_count")
                ->whereBetween("{$table}.{$dateCol}", [$monthStart, $endDate])
                ->when($kpiStations, fn ($q) => $q->whereIn('daily_job_assignments.station', $kpiStations))
                ->groupBy('daily_job_assignments.station')
                ->get();

            foreach ($rows as $row) {
                if (isset($stationPerf[$row->station])) {
                    $stationPerf[$row->station]['total'] += (int) $row->total;
                    $stationPerf[$row->station]['closed'] += (int) $row->closed_count;
                }
            }
        }

        $acRows = $this->applyStationFilter(DB::table('aircraft_cleanings'), 'aircraft_cleanings')
            ->selectRaw("station, COUNT(*) as total, SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) as closed_count")
            ->whereBetween('date', [$monthStart, $endDate])
            ->groupBy('station')
            ->get();

        foreach ($acRows as $row) {
            if (isset($stationPerf[$row->station])) {
                $stationPerf[$row->station]['total'] += (int) $row->total;
                $stationPerf[$row->station]['closed'] += (int) $row->closed_count;
            }
        }

        $stationBar = ['labels' => [], 'closed' => [], 'open' => []];
        $scatter = [];
        foreach ($stationPerf as $code => $perf) {
            $stationBar['labels'][] = $code;
            $stationBar['closed'][] = $perf['closed'];
            $stationBar['open'][] = $perf['total'] - $perf['closed'];

            if ($perf['total'] > 0) {
                $scatter[] = [
                    'x' => $perf['total'],
                    'y' => round($perf['closed'] / $perf['total'] * 100, 1),
                    'label' => $code,
                ];
            }
        }

        // NSRDI open aging buckets
        $aging = ['0-7 hari' => 0, '8-14 hari' => 0, '15-30 hari' => 0, '> 30 hari' => 0];
        $openNsrdi = $this->applyStationFilter(DB::table('nsrdi_logs'), 'nsrdi_logs')
            ->where('status', 'Open')
            ->selectRaw('COALESCE(report_date, plan_date) as start_date')
            ->get();

        foreach ($openNsrdi as $row) {
            if (! $row->start_date) {
                continue;
            }
            $days = (int) Carbon::parse($row->start_date)->diffInDays($activeCarbon, true);
            $bucket = match (true) {
                $days <= 7 => '0-7 hari',
                $days <= 14 => '8-14 hari',
                $days <= 30 => '15-30 hari',
                default => '> 30 hari',
            };
            $aging[$bucket]++;
        }

        // Gauges (month-to-date close rates per module)
        $rate = fn (int $total, int $closed): int => $total > 0 ? (int) round($closed / $total * 100) : 0;
        $gaugeSources = ['WO' => 'wo_logs', 'DMI' => 'dmi_logs', 'CML' => 'cml_logs', 'AC' => 'aircraft_cleanings'];
        $gauges = [];
        foreach ($gaugeSources as $key => $table) {
            $row = $this->applyStationFilter(DB::table($table), $table)
                ->selectRaw("COUNT(*) as total, SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) as closed_count")
                ->whereBetween('date', [$monthStart, $endDate])
                ->first();
            $gauges[$key] = $rate((int) $row->total, (int) $row->closed_count);
        }
        $gauges['Overall'] = $rate($monthTotals['total'], $monthTotals['closed']);

        $manHoursMonth = (float) $this->applyStationFilter(DB::table('wo_logs'), 'wo_logs')->whereBetween('date', [$monthStart, $endDate])->sum('man_hour');
        $busiestIdx = array_search(max($dailyTotal), $dailyTotal);
        $activeDays = count(array_filter($dailyTotal));

        return [
            'target' => $targetPercent,
            'period' => Carbon::parse($monthStart)->translatedFormat('d M').' – '.$activeCarbon->translatedFormat('d M Y'),
            'labels' => $labels,
            'daily_total' => $dailyTotal,
            'daily_closed' => $dailyClosed,
            'daily_rate' => $dailyRate,
            'cumulative' => $cumulative,
            'composition' => $composition,
            'station_bar' => $stationBar,
            'scatter' => $scatter,
            'aging' => $aging,
            'gauges' => $gauges,
            'summary' => [
                'month_total' => $monthTotals['total'],
                'month_closed' => $monthTotals['closed'],
                'month_open' => $monthTotals['open'],
                'close_rate' => $gauges['Overall'],
                'man_hours' => round($manHoursMonth, 1),
                'avg_daily' => $activeDays > 0 ? round(array_sum($dailyTotal) / $activeDays, 1) : 0,
                'busiest_day' => max($dailyTotal) > 0 ? $labels[$busiestIdx] : '-',
                'busiest_count' => max($dailyTotal),
                'nsrdi_open' => array_sum($aging),
                'nsrdi_critical' => $aging['> 30 hari'],
                'active_stations' => count($scatter),
                'total_stations' => count($stationCodes),
            ],
        ];
    }

    public function render()
    {
        $scope = $this->scope();
        $openIctFindings = in_array('ict', $scope->modules, true)
            ? IctFinding::where('status', 'Open')->orderBy('date', 'desc')->take(10)->get()
            : collect();

        return view('livewire.dashboard', [
            'stats' => $this->getDashboardStats(),
            'kpi' => $this->has('kpi') ? $this->getKpiStats() : [],
            'openIctFindings' => $openIctFindings,
            'scope' => $scope,
        ])->layout('components.layouts.app', ['title' => 'Dashboard']);
    }
}
