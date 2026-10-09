<?php

namespace App\Livewire\Modules\Capacity;

use App\Models\CapacityReport;
use App\Models\CapacityStation;
use App\Models\CapacityTarget;
use App\Models\NsrdiLog;
use Carbon\Carbon;
use Livewire\Component;

class Index extends Component
{
    public $activeDate;

    /** Same defaults as Master > Capacity Config, used when no target row is saved yet. */
    private const DEFAULT_TARGETS = ['JT' => 15, 'IU' => 18, 'ID' => 15];

    public function mount()
    {
        // Same "active date" rule as the dashboard: the day rolls over at 18:00
        $this->activeDate = (now()->hour >= 18 ? now() : now()->subDay())->format('Y-m-d');
    }

    public function shiftDay(int $days): void
    {
        $this->activeDate = $this->date()->addDays($days)->format('Y-m-d');
    }

    public function goToActiveDate(): void
    {
        $this->mount();
    }

    /** Valid Carbon date for the bound input; falls back to the active date on garbage input. */
    private function date(): Carbon
    {
        try {
            return Carbon::createFromFormat('Y-m-d', (string) $this->activeDate)->startOfDay();
        } catch (\Throwable $e) {
            return (now()->hour >= 18 ? now() : now()->subDay())->startOfDay();
        }
    }

    public function render()
    {
        // Fetch Targets from DB
        $targetRows = CapacityTarget::all();
        $targets = self::DEFAULT_TARGETS;
        foreach ($targetRows as $t) {
            $targets[$t->aoc] = $t->target_nsrdi;
        }

        // Fetch Stations from DB (from Report if exists for activeDate, else from Master)
        $day = $this->date()->format('Y-m-d');
        $hasReport = CapacityReport::whereDate('report_date', $day)->exists();
        if ($hasReport) {
            $stations = CapacityReport::whereDate('report_date', $day)->orderBy('order_no')->get();
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
            ->whereDate('close_date', $day)
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

        // Merge NSRDI counts into the station rows and total every column
        $totals = [
            'day' => 0, 'night' => 0,
            'ron' => ['JT' => 0, 'IW' => 0, 'ID' => 0, 'IU' => 0, 'SL' => 0, 'OD' => 0],
            'nsrdi' => ['JT' => 0, 'IW' => 0, 'ID' => 0, 'IU' => 0, 'TOTAL' => 0],
        ];
        $listed = [];
        foreach ($capacityData as $kh => $rows) {
            foreach ($rows as $i => $row) {
                $sta = strtoupper((string) $row['sta']);
                $listed[$sta] = true;
                $row['nsrdi'] = $nsrdiData[$sta] ?? ['JT' => 0, 'IW' => 0, 'ID' => 0, 'IU' => 0, 'TOTAL' => 0];
                $capacityData[$kh][$i] = $row;

                $totals['day'] += (int) $row['day'];
                $totals['night'] += (int) $row['night'];
                foreach ($row['ron'] as $k => $v) {
                    $totals['ron'][$k] += (int) $v;
                }
                foreach (['JT', 'IW', 'ID', 'IU', 'TOTAL'] as $k) {
                    $totals['nsrdi'][$k] += $row['nsrdi'][$k];
                }
            }
        }

        // Closed NSRDI at stations that are not in the capacity list: counted in the target cards,
        // not in the table, so they are reported separately instead of silently dropped.
        $unlisted = array_diff_key($nsrdiData, $listed);
        $unlistedTotal = array_sum(array_column($unlisted, 'TOTAL'));

        return view('livewire.modules.capacity.index', [
            'capacityData' => $capacityData,
            'nsrdiTotals' => $nsrdiTotals,
            'targets' => $targets,
            'hasReport' => $hasReport,
            'totals' => $totals,
            'unlisted' => $unlisted,
            'unlistedTotal' => $unlistedTotal,
            'day' => $this->date(),
            'stationCount' => collect($capacityData)->flatten(1)->count(),
        ])->layout('components.layouts.app', ['title' => 'Capacity Management']);
    }
}
