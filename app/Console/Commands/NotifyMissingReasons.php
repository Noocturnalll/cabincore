<?php

namespace App\Console\Commands;

use App\Models\DmiLog;
use App\Models\NsrdiLog;
use App\Models\User;
use App\Models\WoLog;
use App\Notifications\SystemNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('dja:notify-missing-reasons')]
#[Description('Send notifications for logs missing a reason starting from 6 AM to 6 PM')]
class NotifyMissingReasons extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $activeDate = now()->hour >= 18 ? now()->format('Y-m-d') : now()->subDays(1)->format('Y-m-d');

        // Find missing WOs
        $wos = WoLog::where('is_submitted', false)
            ->where(function ($q) {
                $q->whereNull('hold_remarks')->orWhere('hold_remarks', '')->orWhereNull('reason_open')->orWhere('reason_open', '');
            })
            ->where('status', '!=', 'Closed')
            ->whereDate('date', '<=', $activeDate)
            ->get();

        // Find missing DMIs
        $dmis = DmiLog::where('is_submitted', false)
            ->where(function ($q) {
                $q->whereNull('remarks')->orWhere('remarks', '');
            })
            ->where('status', '!=', 'Closed')
            ->whereDate('date', '<=', $activeDate)
            ->get();

        // Find missing NSRDIs
        $nsrdis = NsrdiLog::where('is_submitted', false)
            ->where(function ($q) {
                $q->whereNull('hold_remarks')->orWhere('hold_remarks', '')->orWhereNull('reason_open')->orWhere('reason_open', '');
            })
            ->where('status', '!=', 'Closed')
            ->whereDate('report_date', '<=', $activeDate)
            ->get();

        $allMissing = collect();
        foreach ($wos as $wo) {
            $allMissing->push(['type' => 'WO', 'number' => $wo->wo_number, 'station' => $wo->act_station ?? $wo->plan_station ?? 'UNKNOWN']);
        }
        foreach ($dmis as $dmi) {
            $allMissing->push(['type' => 'DMI', 'number' => $dmi->dmi_number, 'station' => $dmi->act_station ?? $dmi->plan_station ?? 'UNKNOWN']);
        }
        foreach ($nsrdis as $nsrdi) {
            $allMissing->push(['type' => 'NSRDI', 'number' => $nsrdi->nsrdi_number, 'station' => $nsrdi->act_station ?? $nsrdi->plan_station ?? 'UNKNOWN']);
        }

        $grouped = $allMissing->groupBy(function ($item) {
            return strtoupper(trim($item['station']));
        });

        $users = User::all();

        foreach ($grouped as $station => $items) {
            foreach ($items as $item) {
                $msg = 'yth team '.strtolower($station).' please give reason for '.$item['type'].' '.$item['number'].' with actual condition';

                // For simplicity, notify all users (or filter by station if users have station field)
                foreach ($users as $user) {
                    $user->notify(new SystemNotification([
                        'type' => 'warning',
                        'title' => 'Missing Reason: '.$station,
                        'message' => $msg,
                    ]));
                }
            }
        }

        $this->info('Notifications sent for '.$allMissing->count().' missing reasons.');
    }
}
