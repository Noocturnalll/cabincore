<?php

namespace App\Console\Commands;

use App\Services\Dja\CodReportService;
use App\Services\TelegramService;
use App\Services\WhatsAppService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('report:daily {date_or_number?} {--channel=all : Target channel: all, wa, telegram} {--chat-id= : Optional Telegram Chat ID} {--wa-number= : Optional WA Number}')]
#[Description('Send daily CBM COD report via WhatsApp and/or Telegram')]
class SendDailyReportCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(WhatsAppService $waService, TelegramService $telegramService, CodReportService $codService)
    {
        $input = $this->argument('date_or_number');
        $targetDate = ($input && preg_match('/^\d{4}-\d{2}-\d{2}$/', $input)) ? $input : now()->format('Y-m-d');
        $channel = strtolower($this->option('channel') ?? 'all');

        $message = $codService->generateReportText($targetDate);

        $this->line($message);
        $this->newLine();

        // 1. Send via WhatsApp
        if (in_array($channel, ['all', 'wa', 'whatsapp'])) {
            $number = $this->option('wa-number') ?: (($input && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $input)) ? $input : config('services.whatsapp.report_group', '081234567890'));
            $this->info("Sending WhatsApp COD report to {$number}...");
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
            $this->info('Sending Telegram COD report...');
            $teleResult = $telegramService->sendMessage($chatId, $message);
            if ($teleResult) {
                $this->info('✅ Telegram report sent successfully!');
            } else {
                $this->warn('⚠️ Failed to send Telegram report (check TELEGRAM_BOT_TOKEN and TELEGRAM_CHAT_ID in .env).');
            }
        }
    }
}
