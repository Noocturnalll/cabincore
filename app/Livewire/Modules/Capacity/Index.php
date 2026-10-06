<?php

namespace App\Livewire\Modules\Capacity;

use App\Models\CapacityReport;
use App\Models\CapacityStation;
use App\Models\CapacityTarget;
use App\Models\NsrdiLog;
use Livewire\Component;

class Index extends Component
{
    public $activeDate;

    public function mount()
    {
        $this->activeDate = now()->format('Y-m-d');
    }

    public function render()
    {
        // Fetch Targets from DB
        $targetRows = CapacityTarget::all();
        $targets = [];
        foreach ($targetRows as $t) {
            $targets[$t->aoc] = $t->target_nsrdi;
        }

        // Fetch Stations from DB (from Report if exists for activeDate, else from Master)
        $hasReport = CapacityReport::whereDate('report_date', $this->activeDate)->exists();
        if ($hasReport) {
            $stations = CapacityReport::whereDate('report_date', $this->activeDate)->orderBy('order_no')->get();
        } else {
            $stations = CapacityStation::orderBy('order_no')->get();
        }
        $capacityData = [];
        foreach ($stations as $station) {
            $kh = $station->kh_region;
            if (! isset($capacityData[$kh])) {
                $capacityData[$kh] = [];
            }
            $capacityData[$kh][] = [
                'no' => $station->order_no,
                'group' => $station->group_type,
                'sta' => $station->station_code,
                'code_store' => $station->code_store,
                'time' => $station->working_hours,
                'day' => $station->tech_day,
                'night' => $station->tech_night,
                'ron' => [
                    'JT' => $station->ron_jt,
                    'IW' => $station->ron_iw,
                    'ID' => $station->ron_id,
                    'IU' => $station->ron_iu,
                    'SL' => $station->ron_sl,
                    'OD' => $station->ron_od,
                ],
            ];
        }

        $nsrdisAll = NsrdiLog::where('status', 'Closed')
            ->whereDate('close_date', $this->activeDate)
            ->get();

        $nsrdiData = [];
        $nsrdiTotals = ['JT' => 0, 'IW' => 0, 'ID' => 0, 'IU' => 0, 'TOTAL' => 0];

        foreach ($nsrdisAll as $log) {
            $sta = strtoupper($log->act_station ?? $log->station ?? $log->plan_station ?? '');
            $aoc = strtoupper($log->operator ?? $log->aoc ?? '');
            $acReg = strtoupper($log->aircraft_registration ?? '');

            $aocKey = 'UNKNOWN';
            if (stripos($aoc, 'LION') !== false || stripos($acReg, 'PK-L') === 0) {
                $aocKey = 'JT';
            } elseif (stripos($aoc, 'BATIK') !== false || stripos($acReg, 'PK-B') === 0) {
                $aocKey = 'ID';
            } elseif (stripos($aoc, 'WINGS') !== false || stripos($acReg, 'PK-W') === 0) {
                $aocKey = 'IW';
            } elseif (stripos($aoc, 'SUPER AIR') !== false || stripos($acReg, 'PK-S') === 0) {
                $aocKey = 'IU';
            } elseif (stripos($aoc, 'THAI') !== false || stripos($acReg, 'HS-') === 0) {
                $aocKey = 'SL';
            } elseif (stripos($aoc, 'MALINDO') !== false || stripos($acReg, '9M-') === 0) {
                $aocKey = 'OD';
            }

            if ($sta && $aocKey !== 'UNKNOWN') {
                if (! isset($nsrdiData[$sta])) {
                    $nsrdiData[$sta] = ['JT' => 0, 'IW' => 0, 'ID' => 0, 'IU' => 0, 'TOTAL' => 0];
                }
                if (isset($nsrdiData[$sta][$aocKey])) {
                    $nsrdiData[$sta][$aocKey]++;
                }
                $nsrdiData[$sta]['TOTAL']++;

                if (isset($nsrdiTotals[$aocKey])) {
                    $nsrdiTotals[$aocKey]++;
                }
                $nsrdiTotals['TOTAL']++;
            }
        }

        // Merge DB data with the manual data structure
        foreach ($capacityData as $kh => &$rows) {
            foreach ($rows as &$row) {
                $sta = $row['sta'];
                $row['nsrdi'] = $nsrdiData[$sta] ?? ['JT' => 0, 'IW' => 0, 'ID' => 0, 'IU' => 0, 'TOTAL' => 0];
            }
        }

        return view('livewire.modules.capacity.index', [
            'capacityData' => $capacityData,
            'nsrdiTotals' => $nsrdiTotals,
            'targets' => $targets,
            'hasReport' => $hasReport,
        ])->layout('components.layouts.app', ['title' => 'Capacity Management']);
    }
}
