<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    /**
     * Send a Telegram message.
     *
     * @param  string|null  $chatId  Target Chat ID or Channel ID
     * @param  string  $message  Message content
     * @param  string  $parseMode  Markdown or HTML
     * @return bool True on success, false on failure
     */
    public function sendMessage(?string $chatId = null, string $message = '', string $parseMode = 'Markdown'): bool
    {
        $botToken = config('services.telegram.bot_token');
        $targetChatId = $chatId ?: config('services.telegram.chat_id');

        if (! $botToken) {
            Log::warning('TelegramService: TELEGRAM_BOT_TOKEN is not configured.');

            return false;
        }

        if (! $targetChatId) {
            Log::warning('TelegramService: TELEGRAM_CHAT_ID is not configured and no chatId provided.');

            return false;
        }

        try {
            $payload = [
                'chat_id' => $targetChatId,
                'text' => $message,
            ];
            if ($parseMode) {
                $payload['parse_mode'] = $parseMode;
            }

            $response = Http::timeout(10)->post("https://api.telegram.org/bot{$botToken}/sendMessage", $payload);

            if ($response->successful()) {
                Log::info("Telegram message successfully sent to chat {$targetChatId}");

                return true;
            }

            // Fallback retry without parse_mode if Markdown parsing failed
            if ($parseMode && $response->status() === 400) {
                unset($payload['parse_mode']);
                $retry = Http::timeout(10)->post("https://api.telegram.org/bot{$botToken}/sendMessage", $payload);
                if ($retry->successful()) {
                    Log::info("Telegram message sent via plain text fallback to chat {$targetChatId}");

                    return true;
                }
            }

            Log::error('Telegram API Error: '.$response->body());

            return false;
        } catch (\Exception $e) {
            Log::error('Telegram Connection Error: '.$e->getMessage());

            return false;
        }
    }
}
