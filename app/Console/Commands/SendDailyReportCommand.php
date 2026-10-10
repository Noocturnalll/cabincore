<?php

namespace App\Console\Commands;

use App\Services\WhatsAppService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('report:daily {number?}')]
#[Description('Send daily CBM report via WhatsApp')]
class SendDailyReportCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(WhatsAppService $waService)
    {
        $targetDate = now()->format('Y-m-d');

        $woOpen = DB::table('wo_logs')->where('status', 'Open')->count();
        $woClosed = DB::table('wo_logs')->whereDate('date', $targetDate)->where('status', 'Closed')->count();
        $nsrdiOpen = DB::table('nsrdi_logs')->where('status', 'Open')->count();

        $message = "📊 *LAPORAN HARIAN CBM* 📊\n";
        $message .= "Tanggal: {$targetDate}\n\n";

        $message .= "🔧 *Status Work Order*\n";
        $message .= "▫️ Terbuka (Total): {$woOpen}\n";
        $message .= "▫️ Selesai (Hari ini): {$woClosed}\n\n";

        $message .= "⚠️ *Status NSRDI*\n";
        $message .= "▫️ Aktif / Open: {$nsrdiOpen}\n\n";

        $message .= 'Semangat bertugas! ✈️';

        $number = $this->argument('number') ?? config('services.whatsapp.report_group', '081234567890');

        $this->info("Sending report to {$number}...");

        $result = $waService->sendMessage($number, $message);

        if ($result) {
            $this->info('✅ Report sent successfully!');
        } else {
            $this->error('❌ Failed to send report.');
        }
    }
}
