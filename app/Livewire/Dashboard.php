<?php

namespace App\Livewire;

use App\Helpers\RoleHelper;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Dashboard extends Component
{
    private function getDashboardStats()
    {
        $targetDate = now()->hour >= 18 ? now()->format('Y-m-d') : now()->subDays(1)->format('Y-m-d');

        // 1. DJA (Planned) - dja_id IS NOT NULL
        $wo_dja_total = DB::table('wo_logs')->whereDate('date', $targetDate)->whereNotNull('dja_id')->count();
        $wo_dja_open = DB::table('wo_logs')->whereDate('date', $targetDate)->whereNotNull('dja_id')->where('status', 'Open')->count();
        $wo_dja_closed = DB::table('wo_logs')->whereDate('date', $targetDate)->whereNotNull('dja_id')->where('status', 'Closed')->count();

        $dmi_dja_total = DB::table('dmi_logs')->whereDate('date', $targetDate)->whereNotNull('dja_id')->count();
        $dmi_dja_open = DB::table('dmi_logs')->whereDate('date', $targetDate)->whereNotNull('dja_id')->where('status', 'Open')->count();
        $dmi_dja_closed = DB::table('dmi_logs')->whereDate('date', $targetDate)->whereNotNull('dja_id')->where('status', 'Closed')->count();

        $nsrdi_dja_total = DB::table('nsrdi_logs')->whereDate('plan_date', $targetDate)->whereNotNull('dja_id')->count();
        $nsrdi_dja_open = DB::table('nsrdi_logs')->whereDate('plan_date', $targetDate)->whereNotNull('dja_id')->where('status', 'Open')->count();
        $nsrdi_dja_closed = DB::table('nsrdi_logs')->whereDate('plan_date', $targetDate)->whereNotNull('dja_id')->where('status', 'Closed')->count();

        // 2. Unplanned - dja_id IS NULL
        $wo_unplanned_total = DB::table('wo_logs')->whereDate('date', $targetDate)->whereNull('dja_id')->count();
        $wo_unplanned_closed = DB::table('wo_logs')->whereDate('date', $targetDate)->whereNull('dja_id')->where('status', 'Closed')->count();
        
        $dmi_unplanned_total = DB::table('dmi_logs')->whereDate('date', $targetDate)->whereNull('dja_id')->count();
        $dmi_unplanned_closed = DB::table('dmi_logs')->whereDate('date', $targetDate)->whereNull('dja_id')->where('status', 'Closed')->count();
        
        $nsrdi_unplanned_total = DB::table('nsrdi_logs')->whereDate('plan_date', $targetDate)->whereNull('dja_id')->count();
        $nsrdi_unplanned_closed = DB::table('nsrdi_logs')->whereDate('plan_date', $targetDate)->whereNull('dja_id')->where('status', 'Closed')->count();

        // 3. CML Closed
        $cml_closed = DB::table('cml_logs')->whereDate('date', $targetDate)->where('status', 'Closed')->count();

        // Calculations
        $dja_total_laporan = $wo_dja_total + $dmi_dja_total + $nsrdi_dja_total;
        $dja_total_closed = $wo_dja_closed + $dmi_dja_closed + $nsrdi_dja_closed;
        $dja_total_open = $wo_dja_open + $dmi_dja_open + $nsrdi_dja_open;

        $dja_close_rate = $dja_total_laporan > 0 ? round(($dja_total_closed / $dja_total_laporan) * 100) : 0;
        
        $unplanned_total = $wo_unplanned_total + $dmi_unplanned_total + $nsrdi_unplanned_total;
        $unplanned_closed = $wo_unplanned_closed + $dmi_unplanned_closed + $nsrdi_unplanned_closed;
        $unplanned_close_rate = $unplanned_total > 0 ? round(($unplanned_closed / $unplanned_total) * 100) : 0;

                        // Station Stats for Charts (DJA Only: wo, dmi, nsrdi)
        $stationsList = ['CGK', 'HLP', 'SUB', 'KNO', 'UPG', 'MDC', 'BPN', 'AMQ', 'SRG', 'PLM', 'DPS', 'KOE', 'LOP', 'SOC', 'PDG', 'BTH', 'PKU', 'YIA'];
        $stationStats = [];
        foreach ($stationsList as $s) {
            $stationStats[$s] = [
                'total' => 0, 'closed' => 0, 'open' => 0,
                'details' => [
                    'wo' => ['closed' => 0, 'open' => 0],
                    'dmi' => ['closed' => 0, 'open' => 0],
                    'nsrdi' => ['closed' => 0, 'open' => 0]
                ]
            ];
        }

        $tables = ['nsrdi_logs', 'dmi_logs', 'wo_logs'];
        foreach ($tables as $table) {
            $type = str_replace('_logs', '', $table);
            $dateColumn = ($table === 'nsrdi_logs') ? 'plan_date' : 'date';

            $stationData = DB::table($table)
                ->join('daily_job_assignments', "{$table}.dja_id", '=', 'daily_job_assignments.id')
                ->select('daily_job_assignments.station', DB::raw('COUNT(*) as total'), DB::raw("SUM(CASE WHEN {$table}.status = 'Closed' THEN 1 ELSE 0 END) as closed_count"), DB::raw("SUM(CASE WHEN {$table}.status = 'Open' THEN 1 ELSE 0 END) as open_count"))
                ->whereDate("{$table}.{$dateColumn}", $targetDate)
                ->groupBy('daily_job_assignments.station')
                ->get();

            foreach ($stationData as $row) {
                $station = $row->station ?: 'Unknown';
                if (!isset($stationStats[$station])) continue;

                $stationStats[$station]['total'] += $row->total;
                $stationStats[$station]['closed'] += $row->closed_count;
                $stationStats[$station]['open'] += $row->open_count;
                $stationStats[$station]['details'][$type]['closed'] += $row->closed_count;
                $stationStats[$station]['details'][$type]['open'] += $row->open_count;
            }
        }

                // 7-Day Trend Data (Grafik Penyelesaian)
        $trendLabels = [];
        $trendCmlClosed = array_fill(0, 7, 0);
        $trendDja = array_fill(0, 7, 0);
        $trendUnplanned = array_fill(0, 7, 0);

        $activeCarbon = now()->hour >= 18 ? now() : now()->subDays(1);
        $targetDates = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = $activeCarbon->copy()->subDays($i);
            $trendLabels[] = $d->translatedFormat('d M'); 
            $targetDates[] = $d->format('Y-m-d');
        }

        $getDayIndex = function($dateStr) use ($targetDates) {
            $idx = array_search($dateStr, $targetDates);
            return $idx !== false ? $idx : -1;
        };
        
        $startDateStr = $targetDates[0];
        $endDateStr = $targetDates[6];

        // CML Closed Trend
        $cmlTrend = DB::table('cml_logs')->where('status', 'Closed')->whereBetween('date', [$startDateStr, $endDateStr])->select('date')->get();
        foreach ($cmlTrend as $row) {
            $idx = $getDayIndex($row->date);
            if ($idx >= 0) $trendCmlClosed[$idx]++;
        }

        // DJA and Unplanned Trend
        foreach (['wo_logs', 'dmi_logs', 'nsrdi_logs'] as $t) {
            $dateColumn = ($t === 'nsrdi_logs') ? 'report_date' : 'date';
            $logs = DB::table($t)->whereBetween($dateColumn, [$startDateStr, $endDateStr])->select($dateColumn, 'dja_id')->get();
            foreach ($logs as $row) {
                $idx = $getDayIndex($row->$dateColumn);
                if ($idx >= 0) {
                    if (!is_null($row->dja_id)) {
                        $trendDja[$idx]++;
                    } else {
                        $trendUnplanned[$idx]++;
                    }
                }
            }
        }
        return [
            'dja' => [
                'total' => $dja_total_laporan,
                'closed' => $dja_total_closed,
                'open' => $dja_total_open,
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
                'wo_total' => $wo_unplanned_total,
                'dmi_total' => $dmi_unplanned_total,
                'nsrdi_total' => $nsrdi_unplanned_total,
                'all_rate' => $unplanned_close_rate,
            ],
                        'cml_closed' => $cml_closed,
            'dja_close_rate' => $dja_close_rate,
            'stationStats' => $stationStats,
            'trendLabels' => $trendLabels,
            'trendCmlClosed' => $trendCmlClosed,
            'trendDja' => $trendDja,
            'trendUnplanned' => $trendUnplanned,
            // also we need totalOpen and totalClosed for charts if they use it.
            'totalOpen' => $dja_total_open + ($unplanned_total - $unplanned_closed), // rough estimate for charts
            'totalClosed' => $dja_total_closed + $unplanned_closed + $cml_closed,
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

        $openIctFindings = \App\Models\IctFinding::where('status', 'Open')->orderBy('date', 'desc')->get();

        return view($view, [
            'stats' => $this->getDashboardStats(),
            'openIctFindings' => $openIctFindings,
        ])->layout('components.layouts.app', ['title' => 'Dashboard']);
    }
}
