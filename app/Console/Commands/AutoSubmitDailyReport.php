<?php

namespace App\Console\Commands;

use App\Models\DmiLog;
use App\Models\NsrdiLog;
use App\Models\WoLog;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('dailyreport:auto-submit')]
#[Description('Auto-submits DJA logs to Daily Report if they have reasons and are past cutoff')]
class AutoSubmitDailyReport extends Command
{
    public function handle()
    {
        $activeDate = now()->hour >= 18 ? now()->format('Y-m-d') : now()->subDays(1)->format('Y-m-d');

        // Update WOs: must be older than activeDate (meaning 18:00 has passed for them), and either Closed or have reason
        $updatedWo = WoLog::where('is_submitted', false)
            ->whereDate('date', '<', $activeDate)
            ->where(function ($q) {
                $q->where('status', 'Closed')
                    ->orWhere(function ($sub) {
                        $sub->whereNotNull('hold_remarks')->where('hold_remarks', '!=', '')
                            ->orWhereNotNull('reason_open')->where('reason_open', '!=', '');
                    });
            })
            ->update(['is_submitted' => true]);

        // Update DMIs
        $updatedDmi = DmiLog::where('is_submitted', false)
            ->whereDate('date', '<', $activeDate)
            ->where(function ($q) {
                $q->where('status', 'Closed')
                    ->orWhere(function ($sub) {
                        $sub->whereNotNull('remarks')->where('remarks', '!=', '');
                    });
            })
            ->update(['is_submitted' => true]);

        // Update NSRDIs
        $updatedNsrdi = NsrdiLog::where('is_submitted', false)
            ->whereDate('report_date', '<', $activeDate)
            ->where(function ($q) {
                $q->where('status', 'Closed')
                    ->orWhere(function ($sub) {
                        $sub->whereNotNull('hold_remarks')->where('hold_remarks', '!=', '')
                            ->orWhereNotNull('reason_open')->where('reason_open', '!=', '');
                    });
            })
            ->update(['is_submitted' => true]);

        $this->info("Auto-submitted WOs: $updatedWo, DMIs: $updatedDmi, NSRDIs: $updatedNsrdi");
    }
}
