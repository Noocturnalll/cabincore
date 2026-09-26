<?php

namespace App\Services;

use App\Models\CmlLog;
use App\Models\DailyJobAssignment;
use App\Models\DmiLog;
use App\Models\NsrdiLog;
use App\Models\WoLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DailyReportService
{
    /**
     * Get aggregated daily report grouped by station for a specific date.
     *
     * @param  string  $date  (Y-m-d)
     * @return Collection
     */
    public function getSummaryByDate($date)
    {
        // Get all unique stations that have activities on this date
        $stations = DB::table('stations')->pluck('code');
        if ($stations->isEmpty()) {
            // fallback if stations table is empty
            $stations = collect(['CGK', 'HLP', 'SUB', 'KNO', 'UPG', 'MDC', 'BPN', 'AMQ', 'SRG', 'PLM', 'DPS', 'KOE', 'LOP', 'SOC', 'PDG', 'BTH', 'KUL']);
        }

        $report = [];

        foreach ($stations as $station) {
            // 1. DJA R01 (Work Orders that are Planned)
            // Deploy = total DJA with job_type 'R01/WO' for this station
            $djaR01Deploy = DailyJobAssignment::where('date', $date)->where('station', $station)->where('job_type', 'R01/WO')->count();
            // Closed / Open count from the corresponding WoLogs that belong to these DJAs
            $djaR01Closed = WoLog::whereHas('dailyJobAssignment', function ($q) use ($date, $station) {
                $q->where('date', $date)->where('station', $station)->where('job_type', 'R01/WO');
            })->where('status', 'Closed')->count();

            $djaR01Open = $djaR01Deploy - $djaR01Closed; // Or query Open status specifically

            // 2. DJA AOC (NSRDI R01)
            $djaAocDeploy = DailyJobAssignment::where('date', $date)->where('station', $station)->where('job_type', 'AOC/NSRDI')->count();
            $djaAocClosed = NsrdiLog::whereHas('dailyJobAssignment', function ($q) use ($date, $station) {
                $q->where('date', $date)->where('station', $station)->where('job_type', 'AOC/NSRDI');
            })->where('status', 'Closed')->count();
            $djaAocOpen = $djaAocDeploy - $djaAocClosed;

            // 3. DMI (Planned)
            $dmiDeploy = DailyJobAssignment::where('date', $date)->where('station', $station)->where('job_type', 'DMI')->count();
            $dmiClosed = DmiLog::whereHas('dailyJobAssignment', function ($q) use ($date, $station) {
                $q->where('date', $date)->where('station', $station)->where('job_type', 'DMI');
            })->where('status', 'Closed')->count();
            $dmiOpen = $dmiDeploy - $dmiClosed;

            // 4. UNPLANNED WO CLOSED (No DJA ID, created on this operational date, status closed)
            $unplannedWoClosed = WoLog::whereNull('dja_id')
                ->where('act_station', $station)
                ->where('date', $date)
                ->where('status', 'Closed')->count();

            // 5. UNPLANNED NSRDIL CLOSED
            $unplannedNsrdiClosed = NsrdiLog::whereNull('dja_id')
                ->where('act_station', $station)
                ->where('date', $date)
                ->where('status', 'Closed')->count();

            // 6. UNPLANNED DMI CLOSED
            $unplannedDmiClosed = DmiLog::whereNull('dja_id')
                ->where('act_station', $station)
                ->where('date', $date)
                ->where('status', 'Closed')->count();

            // 7. CML CLOSED (Created on this date, regardless of DJA, status closed)
            $cmlClosed = CmlLog::where('station', $station)
                ->whereDate('created_at', $date)
                ->where('status', 'Closed')->count();

            // Calculate Totals
            $closedTotal = $djaR01Closed + $djaAocClosed + $dmiClosed + $unplannedWoClosed + $unplannedNsrdiClosed + $unplannedDmiClosed + $cmlClosed;

            // Only add station to report if there's any activity or deploy
            $totalActivity = $djaR01Deploy + $djaAocDeploy + $dmiDeploy + $closedTotal;

            // We want to show the station anyway for reporting purposes, even if 0, matching the Excel template
            $report[] = [
                'station' => $station,
                'dja_r01' => [
                    'deploy' => $djaR01Deploy,
                    'closed' => $djaR01Closed,
                    'open' => $djaR01Open,
                ],
                'dja_aoc' => [
                    'deploy' => $djaAocDeploy,
                    'closed' => $djaAocClosed,
                    'open' => $djaAocOpen,
                ],
                'dmi' => [
                    'deploy' => $dmiDeploy,
                    'closed' => $dmiClosed,
                    'open' => $dmiOpen,
                ],
                'all_finding' => 0,
                'unplanned_wo_closed' => $unplannedWoClosed,
                'unplanned_nsrdi_closed' => $unplannedNsrdiClosed,
                'unplanned_dmi_closed' => $unplannedDmiClosed,
                'cml_closed' => $cmlClosed,
                'closed_total' => $closedTotal,
            ];
        }

        return collect($report);
    }
}
