<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use App\Services\WhatsAppService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('bot:test {--channel=all : Target channel: all, wa, tele} {--wa-number= : Specific WA number} {--tele-chat= : Specific Telegram chat ID}')]
#[Description('Test WhatsApp and Telegram bot connectivity')]
class BotTestCommand extends Command
{
    public function handle(WhatsAppService $waService, TelegramService $teleService)
    {
        $channel = strtolower($this->option('channel') ?? 'all');
        $this->info('🚀 Testing Bot Integrations for CBM...');

        // WhatsApp Test
        if (in_array($channel, ['all', 'wa', 'whatsapp'])) {
            $number = $this->option('wa-number') ?? config('services.whatsapp.report_group', '081234567890');
            $this->line("Testing WhatsApp via {$number}...");
            $waOk = $waService->sendMessage($number, "🔔 *CBM Bot Test Notification*\n\nWhatsApp service is operational! ✅\nTimestamp: ".now()->toDateTimeString());

            if ($waOk) {
                $this->info('  [WhatsApp] Connected & Message Sent! ✅');
            } else {
                $this->warn('  [WhatsApp] Could not send message. Ensure whatsapp-service is running on port 3001. ⚠️');
            }
        }

        // Telegram Test
        if (in_array($channel, ['all', 'tele', 'telegram'])) {
            $chatId = $this->option('tele-chat') ?: config('services.telegram.chat_id');
            $this->line('Testing Telegram...');
            $teleOk = $teleService->sendMessage($chatId, "🔔 *CBM Bot Test Notification*\n\nTelegram bot is operational! ✅\nTimestamp: ".now()->toDateTimeString());

            if ($teleOk) {
                $this->info('  [Telegram] Connected & Message Sent! ✅');
            } else {
                $this->warn('  [Telegram] Could not send message. Ensure TELEGRAM_BOT_TOKEN and TELEGRAM_CHAT_ID are set in .env. ⚠️');
            }
        }

        return 0;
    }
}
