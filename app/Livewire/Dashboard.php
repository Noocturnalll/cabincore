<?php

namespace App\Livewire;

use App\Helpers\RoleHelper;
use App\Models\IctFinding;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Dashboard extends Component
{
    private function getDashboardStats(): array
    {
        // "Active date": before 18:00 WIB → show yesterday; at/after 18:00 → show today
        $activeCarbon = now()->hour >= 18 ? now() : now()->subDays(1);
        $targetDate = $activeCarbon->format('Y-m-d');

        // ─── 1. DJA (Planned = dja_id IS NOT NULL) ──────────────────────────
        // WO: filter by wo_logs.date
        $wo_dja_total = DB::table('wo_logs')->whereDate('date', $targetDate)->whereNotNull('dja_id')->count();
        $wo_dja_open = DB::table('wo_logs')->whereDate('date', $targetDate)->whereNotNull('dja_id')->where('status', 'Open')->count();
        $wo_dja_closed = DB::table('wo_logs')->whereDate('date', $targetDate)->whereNotNull('dja_id')->where('status', 'Closed')->count();

        // DMI: filter by dmi_logs.date
        $dmi_dja_total = DB::table('dmi_logs')->whereDate('date', $targetDate)->whereNotNull('dja_id')->count();
        $dmi_dja_open = DB::table('dmi_logs')->whereDate('date', $targetDate)->whereNotNull('dja_id')->where('status', 'Open')->count();
        $dmi_dja_closed = DB::table('dmi_logs')->whereDate('date', $targetDate)->whereNotNull('dja_id')->where('status', 'Closed')->count();

        // NSRDI (planned) uses plan_date
        $nsrdi_dja_total = DB::table('nsrdi_logs')->whereDate('plan_date', $targetDate)->whereNotNull('dja_id')->count();
        $nsrdi_dja_open = DB::table('nsrdi_logs')->whereDate('plan_date', $targetDate)->whereNotNull('dja_id')->where('status', 'Open')->count();
        $nsrdi_dja_closed = DB::table('nsrdi_logs')->whereDate('plan_date', $targetDate)->whereNotNull('dja_id')->where('status', 'Closed')->count();

        // ─── 2. Unplanned (dja_id IS NULL) ──────────────────────────────────
        $wo_unplanned_total = DB::table('wo_logs')->whereDate('date', $targetDate)->whereNull('dja_id')->count();
        $wo_unplanned_closed = DB::table('wo_logs')->whereDate('date', $targetDate)->whereNull('dja_id')->where('status', 'Closed')->count();
        $wo_unplanned_open = DB::table('wo_logs')->whereDate('date', $targetDate)->whereNull('dja_id')->where('status', 'Open')->count();

        $dmi_unplanned_total = DB::table('dmi_logs')->whereDate('date', $targetDate)->whereNull('dja_id')->count();
        $dmi_unplanned_closed = DB::table('dmi_logs')->whereDate('date', $targetDate)->whereNull('dja_id')->where('status', 'Closed')->count();
        $dmi_unplanned_open = DB::table('dmi_logs')->whereDate('date', $targetDate)->whereNull('dja_id')->where('status', 'Open')->count();

        // NSRDI (unplanned) uses report_date
        $nsrdi_unplanned_total = DB::table('nsrdi_logs')->whereDate('report_date', $targetDate)->whereNull('dja_id')->count();
        $nsrdi_unplanned_closed = DB::table('nsrdi_logs')->whereDate('report_date', $targetDate)->whereNull('dja_id')->where('status', 'Closed')->count();
        $nsrdi_unplanned_open = DB::table('nsrdi_logs')->whereDate('report_date', $targetDate)->whereNull('dja_id')->where('status', 'Open')->count();

        // ─── 3. CML ─────────────────────────────────────────────────────────
        $cml_total = DB::table('cml_logs')->whereDate('date', $targetDate)->count();
        $cml_closed = DB::table('cml_logs')->whereDate('date', $targetDate)->where('status', 'Closed')->count();
        $cml_open = DB::table('cml_logs')->whereDate('date', $targetDate)->where('status', 'Open')->count();

        // ─── 4. ICT Findings ────────────────────────────────────────────────
        $ict_total = DB::table('ict_findings')->whereDate('date', $targetDate)->count();
        $ict_closed = DB::table('ict_findings')->whereDate('date', $targetDate)->where('status', 'Closed')->count();
        $ict_open = DB::table('ict_findings')->whereDate('date', $targetDate)->where('status', 'Open')->count();

        // ICT breakdown by operator (daily)
        $ict_breakdown_raw = DB::table('ict_findings')
            ->select(
                'operator',
                DB::raw("SUM(CASE WHEN status = 'Open' THEN 1 ELSE 0 END) as open_count"),
                DB::raw("SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) as closed_count"),
                DB::raw('COUNT(*) as total_count')
            )
            ->whereDate('date', $targetDate)
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
                ->whereDate("{$table}.{$dateCol}", $targetDate)
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
        $acStationData = DB::table('aircraft_cleanings')
            ->select(
                'station',
                'type',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) as closed_count"),
                DB::raw("SUM(CASE WHEN status = 'Open' THEN 1 ELSE 0 END) as open_count")
            )
            ->whereDate('date', $targetDate)
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
        $trendCmlClosed = array_fill(0, 7, 0);
        $trendAcTotal = array_fill(0, 7, 0);
        $trendDja = array_fill(0, 7, 0);
        $trendUnplanned = array_fill(0, 7, 0);

        $targetDates = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = $activeCarbon->copy()->subDays($i);
            $trendLabels[] = $d->translatedFormat('d M');
            $targetDates[] = $d->format('Y-m-d');
        }

        $getDayIndex = function (string $dateStr) use ($targetDates): int {
            $idx = array_search($dateStr, $targetDates);

            return $idx !== false ? $idx : -1;
        };

        $startDateStr = $targetDates[0];
        $endDateStr = $targetDates[6];

        // CML closed trend
        $cmlTrend = DB::table('cml_logs')
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
        $acTrend = DB::table('aircraft_cleanings')
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
        $nsrdiLogs = DB::table('nsrdi_logs')
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
        $ac_raw = DB::table('aircraft_cleanings')
            ->whereDate('date', $targetDate)
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

        $ac_total = DB::table('aircraft_cleanings')->whereDate('date', $targetDate)->count();
        $ac_closed = DB::table('aircraft_cleanings')->whereDate('date', $targetDate)->where('status', 'Closed')->count();
        $ac_open = DB::table('aircraft_cleanings')->whereDate('date', $targetDate)->where('status', 'Open')->count();

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
        $acMonthlyByOperator = DB::table('aircraft_cleanings')
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
        $man_hours = DB::table('wo_logs')->whereDate('date', $targetDate)->sum('man_hour') ?: 0;

        // ─── 10. NSRDI Overdue (Open & past due_date) ────────────────────────
        $nsrdiOverdueMap = [
            'Batik Air' => 0,
            'Lion Air' => 0,
            'Super Air Jet' => 0,
            'Wings Air' => 0,
        ];

        $nsrdiOverdueRaw = DB::table('nsrdi_logs')
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
            'wo' => DB::table('wo_logs')->count(),
            'cml' => DB::table('cml_logs')->count(),
            'dmi' => DB::table('dmi_logs')->count(),
            'nsrdi' => DB::table('nsrdi_logs')->count(),
            'ict' => DB::table('ict_findings')->count(),
            'ict_open' => DB::table('ict_findings')->where('status', 'Open')->count(),
            'ac' => DB::table('aircraft_cleanings')->count(),
        ];

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
            'openIctFindings' => $openIctFindings,
        ])->layout('components.layouts.app', ['title' => 'Dashboard']);
    }
}
