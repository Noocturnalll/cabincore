<?php

namespace App\Livewire;

use App\Helpers\RoleHelper;
use App\Models\Airport;
use App\Models\IctFinding;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Support\DashboardScope;
use Livewire\Component;
use Livewire\Attributes\On;

class Dashboard extends Component
{
    public string $period = 'daily';

    private function scope(): DashboardScope
    {
        return DashboardScope::for(auth()->user());
    }

    private function range(): array
    {
        $scope = $this->scope();
        $period = in_array($this->period, $scope->periods, true) ? $this->period : 'daily';
        
        $active = now()->hour >= 18 ? now() : now()->subDay();

        [$from, $to] = match ($period) {
            'weekly'  => [$active->copy()->startOfWeek(), $active->copy()],
            'monthly' => [$active->copy()->startOfMonth(), $active->copy()],
            default   => [$active->copy(), $active->copy()],
        };

        return [$from->startOfDay()->toDateTimeString(), $to->endOfDay()->toDateTimeString()];
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
        } else {
            $column = match($table) {
                'wo_logs', 'dmi_logs', 'nsrdi_logs' => 'act_station',
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

        // ─── 1. DJA (Planned = dja_id IS NOT NULL) ──────────────────────────
        // WO: filter by wo_logs.date
        $wo_dja_total = $this->applyStationFilter(DB::table('wo_logs'), 'wo_logs')->whereBetween('date', [$from, $to])->whereNotNull('dja_id')->count();
        $wo_dja_open = $this->applyStationFilter(DB::table('wo_logs'), 'wo_logs')->whereBetween('date', [$from, $to])->whereNotNull('dja_id')->where('status', 'Open')->count();
        $wo_dja_closed = $this->applyStationFilter(DB::table('wo_logs'), 'wo_logs')->whereBetween('date', [$from, $to])->whereNotNull('dja_id')->where('status', 'Closed')->count();

        // DMI: filter by dmi_logs.date
        $dmi_dja_total = $this->applyStationFilter(DB::table('dmi_logs'), 'dmi_logs')->whereBetween('date', [$from, $to])->whereNotNull('dja_id')->count();
        $dmi_dja_open = $this->applyStationFilter(DB::table('dmi_logs'), 'dmi_logs')->whereBetween('date', [$from, $to])->whereNotNull('dja_id')->where('status', 'Open')->count();
        $dmi_dja_closed = $this->applyStationFilter(DB::table('dmi_logs'), 'dmi_logs')->whereBetween('date', [$from, $to])->whereNotNull('dja_id')->where('status', 'Closed')->count();

        // NSRDI (planned) uses plan_date
        $nsrdi_dja_total = $this->applyStationFilter(DB::table('nsrdi_logs'), 'nsrdi_logs')->whereBetween('plan_date', [$from, $to])->whereNotNull('dja_id')->count();
        $nsrdi_dja_open = $this->applyStationFilter(DB::table('nsrdi_logs'), 'nsrdi_logs')->whereBetween('plan_date', [$from, $to])->whereNotNull('dja_id')->where('status', 'Open')->count();
        $nsrdi_dja_closed = $this->applyStationFilter(DB::table('nsrdi_logs'), 'nsrdi_logs')->whereBetween('plan_date', [$from, $to])->whereNotNull('dja_id')->where('status', 'Closed')->count();

        // ─── 2. Unplanned (dja_id IS NULL) ──────────────────────────────────
        $wo_unplanned_total = $this->applyStationFilter(DB::table('wo_logs'), 'wo_logs')->whereBetween('date', [$from, $to])->whereNull('dja_id')->count();
        $wo_unplanned_closed = $this->applyStationFilter(DB::table('wo_logs'), 'wo_logs')->whereBetween('date', [$from, $to])->whereNull('dja_id')->where('status', 'Closed')->count();
        $wo_unplanned_open = $this->applyStationFilter(DB::table('wo_logs'), 'wo_logs')->whereBetween('date', [$from, $to])->whereNull('dja_id')->where('status', 'Open')->count();

        $dmi_unplanned_total = $this->applyStationFilter(DB::table('dmi_logs'), 'dmi_logs')->whereBetween('date', [$from, $to])->whereNull('dja_id')->count();
        $dmi_unplanned_closed = $this->applyStationFilter(DB::table('dmi_logs'), 'dmi_logs')->whereBetween('date', [$from, $to])->whereNull('dja_id')->where('status', 'Closed')->count();
        $dmi_unplanned_open = $this->applyStationFilter(DB::table('dmi_logs'), 'dmi_logs')->whereBetween('date', [$from, $to])->whereNull('dja_id')->where('status', 'Open')->count();

        // NSRDI (unplanned) uses report_date
        $nsrdi_unplanned_total = $this->applyStationFilter(DB::table('nsrdi_logs'), 'nsrdi_logs')->whereBetween('report_date', [$from, $to])->whereNull('dja_id')->count();
        $nsrdi_unplanned_closed = $this->applyStationFilter(DB::table('nsrdi_logs'), 'nsrdi_logs')->whereBetween('report_date', [$from, $to])->whereNull('dja_id')->where('status', 'Closed')->count();
        $nsrdi_unplanned_open = $this->applyStationFilter(DB::table('nsrdi_logs'), 'nsrdi_logs')->whereBetween('report_date', [$from, $to])->whereNull('dja_id')->where('status', 'Open')->count();

        // ─── 3. CML ─────────────────────────────────────────────────────────
        $cml_total = $this->applyStationFilter(DB::table('cml_logs'), 'cml_logs')->whereBetween('date', [$from, $to])->count();
        $cml_closed = $this->applyStationFilter(DB::table('cml_logs'), 'cml_logs')->whereBetween('date', [$from, $to])->where('status', 'Closed')->count();
        $cml_open = $this->applyStationFilter(DB::table('cml_logs'), 'cml_logs')->whereBetween('date', [$from, $to])->where('status', 'Open')->count();

        // ─── 4. ICT Findings ────────────────────────────────────────────────
        $ict_total = DB::table('ict_findings')->whereBetween('date', [$from, $to])->count();
        $ict_closed = DB::table('ict_findings')->whereBetween('date', [$from, $to])->where('status', 'Closed')->count();
        $ict_open = DB::table('ict_findings')->whereBetween('date', [$from, $to])->where('status', 'Open')->count();

        // ICT breakdown by operator (daily)
        $ict_breakdown_raw = DB::table('ict_findings')
            ->select(
                'operator',
                DB::raw("SUM(CASE WHEN status = 'Open' THEN 1 ELSE 0 END) as open_count"),
                DB::raw("SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) as closed_count"),
                DB::raw('COUNT(*) as total_count')
            )
            ->whereBetween('date', [$from, $to])
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
        $ims_transactions = DB::table('ims_transactions')->whereBetween('created_at', [$from, $to])->count();
        $ims_transactions_in = DB::table('ims_transactions')->whereBetween('created_at', [$from, $to])->where('type', 'IN')->count();
        $ims_transactions_out = DB::table('ims_transactions')->whereBetween('created_at', [$from, $to])->where('type', 'OUT')->count();
        
        $repair_waiting = DB::table('ims_repair_waiting')->count();
        $repair_progress = DB::table('ims_repair_in_progress')->count();
        $repair_completed_today = DB::table('ims_repair_completed')->whereBetween('created_at', [$from, $to])->count();


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
                ->when($scope->stations, fn($q) => $q->whereIn('daily_job_assignments.station', $scope->stations))
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
        $periodMode = in_array($this->period, $scope->periods, true) ? $this->period : 'daily';
        $loopCount = match($periodMode) { 'weekly' => 8, 'monthly' => 6, default => 7 };
        
        $trendCmlClosed = array_fill(0, $loopCount, 0);
        $trendAcTotal = array_fill(0, $loopCount, 0);
        $trendDja = array_fill(0, $loopCount, 0);
        $trendUnplanned = array_fill(0, $loopCount, 0);

        for ($i = $loopCount - 1; $i >= 0; $i--) {
            if ($periodMode === 'weekly') {
                $d = $activeCarbon->copy()->subWeeks($i);
                $trendLabels[] = 'W' . $d->weekOfYear;
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
        
        $getDayIndex = function (string $dateStr) use ($targetDates, $periodMode): int {
            $dateStr = substr($dateStr, 0, 10);
            foreach ($targetDates as $idx => $range) {
                if ($dateStr >= $range[0] && $dateStr <= $range[1]) return $idx;
            }
            return -1;
        };

        $startDateStr = $targetDates[0][0] . ' 00:00:00';
        $endDateStr = $targetDates[$loopCount - 1][1] . ' 23:59:59';

        

        // CML closed trend
        $cmlTrend = $this->applyStationFilter(DB::table('cml_logs'), 'cml_logs')
            ->where('status', 'Closed')
            ->whereBetween('date', [$startDateStr, $endDateStr])
            ->select('date')
            ->get();

        foreach ($cmlTrend as $row) {
            $idx = $getDayIndex($row->date);
            if ($idx >= 0) {
                $trendCmlClosed[$idx]++;
            }
        }

        // Aircraft Cleaning trend
        $acTrend = $this->applyStationFilter(DB::table('aircraft_cleanings'), 'aircraft_cleanings')
            ->whereBetween('date', [$startDateStr, $endDateStr])
            ->select('date')
            ->get();

        foreach ($acTrend as $row) {
            $idx = $getDayIndex($row->date);
            if ($idx >= 0) {
                $trendAcTotal[$idx]++;
            }
        }

        // DJA and Unplanned trend
        foreach (['wo_logs', 'dmi_logs'] as $t) {
            $logs = DB::table($t)
                ->whereBetween('date', [$startDateStr, $endDateStr])
                ->select('date as the_date', 'dja_id')
                ->get();

            foreach ($logs as $row) {
                $idx = $getDayIndex($row->the_date);
                if ($idx >= 0) {
                    if (! is_null($row->dja_id)) {
                        $trendDja[$idx]++;
                    } else {
                        $trendUnplanned[$idx]++;
                    }
                }
            }
        }

        // NSRDI trend: DJA uses plan_date; unplanned uses report_date
        $nsrdiLogs = $this->applyStationFilter(DB::table('nsrdi_logs'), 'nsrdi_logs')
            ->select('plan_date', 'report_date', 'dja_id')
            ->where(function ($q) use ($startDateStr, $endDateStr) {
                $q->whereBetween('plan_date', [$startDateStr, $endDateStr])
                    ->orWhereBetween('report_date', [$startDateStr, $endDateStr]);
            })
            ->get();

        foreach ($nsrdiLogs as $row) {
            $dateVal = $row->dja_id ? $row->plan_date : $row->report_date;
            if (! $dateVal) {
                continue;
            }
            $dateStr = substr($dateVal, 0, 10);
            $idx = $getDayIndex($dateStr);
            if ($idx >= 0) {
                if (! is_null($row->dja_id)) {
                    $trendDja[$idx]++;
                } else {
                    $trendUnplanned[$idx]++;
                }
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
        $man_power = User::role(RoleHelper::ALL_PIC)->count();
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
            'ict' => DB::table('ict_findings')->count(),
            'ict_open' => DB::table('ict_findings')->where('status', 'Open')->count(),
            'ac' => $this->applyStationFilter(DB::table('aircraft_cleanings'), 'aircraft_cleanings')->count(),
            'ims_transactions' => DB::table('ims_transactions')->count(),
            'repair_total' => $repair_waiting + $repair_progress + DB::table('ims_repair_completed')->count(),
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

        foreach ($activeNsrdisToday as $log) {
            if (! $log->nsrdi_number || in_array($log->nsrdi_number, $seenNs)) {
                continue;
            }

            $pastNs = $this->applyStationFilter(DB::table('nsrdi_logs'), 'nsrdi_logs')
                ->where('nsrdi_number', $log->nsrdi_number)
                ->where('id', '<', $log->id)
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
                ->first();

            if ($pastNs) {
                $log->past_ns_date = $pastNs->plan_date ?? $pastNs->report_date;
                $log->past_ns_reason = $pastNs->reason_open ?? $pastNs->code_open;
                $recurringNs[] = $log;
                $seenNs[] = $log->nsrdi_number;
            }
        }

        return [
            'target_date' => $targetDate,

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
            $rows = DB::table($source['table'])
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
        $stationCodes = Airport::where('status', 'Aktif')->orderBy('kode')->pluck('kode')->all();
        $stationPerf = array_fill_keys($stationCodes, ['total' => 0, 'closed' => 0]);

        foreach (['wo_logs' => 'date', 'dmi_logs' => 'date', 'nsrdi_logs' => 'plan_date'] as $table => $dateCol) {
            $rows = DB::table($table)
                ->join('daily_job_assignments', "{$table}.dja_id", '=', 'daily_job_assignments.id')
                ->selectRaw("daily_job_assignments.station as station, COUNT(*) as total, SUM(CASE WHEN {$table}.status = 'Closed' THEN 1 ELSE 0 END) as closed_count")
                ->whereRaw("DATE({$table}.{$dateCol}) BETWEEN ? AND ?", [$monthStart, $endDate])
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
            ->whereRaw('DATE(date) BETWEEN ? AND ?', [$monthStart, $endDate])
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
            $row = DB::table($table)
                ->selectRaw("COUNT(*) as total, SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) as closed_count")
                ->whereRaw('DATE(date) BETWEEN ? AND ?', [$monthStart, $endDate])
                ->first();
            $gauges[$key] = $rate((int) $row->total, (int) $row->closed_count);
        }
        $gauges['Overall'] = $rate($monthTotals['total'], $monthTotals['closed']);

        $manHoursMonth = (float) $this->applyStationFilter(DB::table('wo_logs'), 'wo_logs')->whereRaw('DATE(date) BETWEEN ? AND ?', [$monthStart, $endDate])->sum('man_hour');
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
        $user = auth()->user();
        $view = 'livewire.dashboard-pic'; // Default

        if ($user->hasRole(RoleHelper::SUPER_ADMIN)) {
            $view = 'livewire.dashboard-super-admin';
        } elseif ($user->hasRole(RoleHelper::MANAGER)) {
            $view = 'livewire.dashboard-manager';
        } elseif ($user->hasRole(RoleHelper::ADMIN_CGK)) {
            $view = 'livewire.dashboard-admin';
        }

        $openIctFindings = IctFinding::where('status', 'Open')
            ->orderBy('date', 'desc')
            ->take(10)
            ->get();

        return view($view, [
            'stats' => $this->getDashboardStats(),
            'kpi' => $this->getKpiStats(),
            'openIctFindings' => $openIctFindings,
        ])->layout('components.layouts.app', ['title' => 'Dashboard']);
    }
}
