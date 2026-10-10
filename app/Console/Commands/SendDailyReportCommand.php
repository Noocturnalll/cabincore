<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use App\Services\WhatsAppService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('report:daily {number?} {--channel=all : Target channel: all, wa, telegram} {--chat-id= : Optional Telegram Chat ID}')]
#[Description('Send daily CBM report via WhatsApp and/or Telegram')]
class SendDailyReportCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(WhatsAppService $waService, TelegramService $telegramService)
    {
        $targetDate = now()->format('Y-m-d');
        $channel = strtolower($this->option('channel') ?? 'all');

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

        // 1. Send via WhatsApp
        if (in_array($channel, ['all', 'wa', 'whatsapp'])) {
            $number = $this->argument('number') ?? config('services.whatsapp.report_group', '081234567890');
            $this->info("Sending WhatsApp report to {$number}...");
            $waResult = $waService->sendMessage($number, $message);
            if ($waResult) {
                $this->info('✅ WhatsApp report sent successfully!');
            } else {
                $this->warn('⚠️ Failed to send WhatsApp report (make sure whatsapp-service is running).');
            }
        }

        // 2. Send via Telegram
        if (in_array($channel, ['all', 'tele', 'telegram'])) {
            $chatId = $this->option('chat-id') ?: config('services.telegram.chat_id');
            $this->info('Sending Telegram report...');
            $teleResult = $telegramService->sendMessage($chatId, $message);
            if ($teleResult) {
                $this->info('✅ Telegram report sent successfully!');
            } else {
                $this->warn('⚠️ Failed to send Telegram report (check TELEGRAM_BOT_TOKEN and TELEGRAM_CHAT_ID in .env).');
            }
        }
    }
}
