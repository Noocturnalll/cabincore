<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

#[Signature('telegram:set-webhook {url? : Base application URL (e.g., https://cbm.example.com)}') ]
#[Description('Register Telegram Bot webhook with secret token')]
class TelegramSetWebhookCommand extends Command
{
    public function handle()
    {
        $botToken = config('services.telegram.bot_token');
        $webhookSecret = config('services.telegram.webhook_secret');

        if (! $botToken) {
            $this->error('TELEGRAM_BOT_TOKEN is not configured in .env');

            return 1;
        }

        $baseUrl = $this->argument('url') ?: config('app.url');
        $baseUrl = rtrim($baseUrl, '/');

        if (! str_starts_with($baseUrl, 'https://')) {
            $this->warn('Notice: Telegram requires an HTTPS webhook URL in production.');
        }

        $webhookUrl = "{$baseUrl}/api/telegram/webhook";
        $this->info("Setting webhook to: {$webhookUrl}");

        $params = [
            'url' => $webhookUrl,
        ];

        if ($webhookSecret) {
            $params['secret_token'] = $webhookSecret;
            $this->info('Securing webhook with TELEGRAM_WEBHOOK_SECRET.');
        }

        $response = Http::post("https://api.telegram.org/bot{$botToken}/setWebhook", $params);

        if ($response->successful() && ($response->json('ok') ?? false)) {
            $this->info('✅ Telegram Webhook successfully configured!');
            $this->line('Response: '.$response->body());

            return 0;
        }

        $this->error('❌ Failed to set Telegram Webhook.');
        $this->line('Response: '.$response->body());

        return 1;
    }
}
