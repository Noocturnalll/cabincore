<?php

namespace App\Console\Commands;

use App\Models\CapacityReport;
use App\Models\CapacityStation;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:snapshot-capacity-data')]
#[Description('Command description')]
class SnapshotCapacityData extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = now()->format('Y-m-d');

        // Remove existing report for today to avoid duplicates if run multiple times
        CapacityReport::whereDate('report_date', $today)->delete();

        $stations = CapacityStation::orderBy('order_no')->get();

        foreach ($stations as $station) {
            CapacityReport::create([
                'report_date' => $today,
                'order_no' => $station->order_no,
                'kh_region' => $station->kh_region,
                'group_type' => $station->group_type,
                'station_code' => $station->station_code,
                'code_store' => $station->code_store,
                'working_hours' => $station->working_hours,
                'tech_day' => $station->tech_day,
                'tech_night' => $station->tech_night,
                'ron_jt' => $station->ron_jt,
                'ron_iw' => $station->ron_iw,
                'ron_id' => $station->ron_id,
                'ron_iu' => $station->ron_iu,
                'ron_sl' => $station->ron_sl,
                'ron_od' => $station->ron_od,
            ]);
        }

        $this->info("Capacity data for {$today} has been successfully snapshotted.");
    }
}
