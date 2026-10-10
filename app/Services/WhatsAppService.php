<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Get base URL for whatsapp service.
     */
    private function getBaseUrl(): string
    {
        $url = config('services.whatsapp.url', 'http://127.0.0.1:3001');

        return rtrim(preg_replace('#/send-message/?$#', '', $url), '/');
    }

    /**
     * Check if WhatsApp service is running and ready.
     *
     * @return array{ready: bool, message: string}
     */
    public function getStatus(): array
    {
        try {
            $response = Http::timeout(5)->get($this->getBaseUrl().'/status');
            if ($response->successful()) {
                return [
                    'ready' => (bool) $response->json('ready'),
                    'message' => (string) $response->json('message'),
                ];
            }

            return ['ready' => false, 'message' => 'Layanan WhatsApp tidak merespons (HTTP '.$response->status().')'];
        } catch (\Exception $e) {
            return ['ready' => false, 'message' => 'Layanan WhatsApp offline: '.$e->getMessage()];
        }
    }

    /**
     * Fetch list of WhatsApp groups joined by the bot.
     *
     * @return array<int, array{id: string, name: string}>
     */
    public function getGroups(): array
    {
        try {
            $response = Http::timeout(8)->get($this->getBaseUrl().'/groups');
            if ($response->successful()) {
                return $response->json('groups') ?? [];
            }

            Log::warning('WhatsApp getGroups response error: '.$response->body());

            return [];
        } catch (\Exception $e) {
            Log::warning('WhatsApp getGroups connection error: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Send a WhatsApp message to a specific number or group JID.
     *
     * @param  string  $number  Phone number or group JID (e.g. '120363029182391238@g.us')
     * @param  string  $message  The message content
     * @return bool True if successful, false otherwise
     */
    public function sendMessage(string $number, string $message): bool
    {
        try {
            $url = $this->getBaseUrl().'/send-message';

            $response = Http::timeout(12)->post($url, [
                'number' => $number,
                'message' => $message,
            ]);

            if ($response->successful()) {
                Log::info("WhatsApp message sent to {$number}");

                return true;
            }

            Log::error('WhatsApp API Error: '.$response->body());

            return false;
        } catch (\Exception $e) {
            Log::error('WhatsApp Connection Error: '.$e->getMessage());

            return false;
        }
    }
}
