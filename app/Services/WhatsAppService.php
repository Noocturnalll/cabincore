<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Send a WhatsApp message to a specific number using the local Node.js server.
     *
     * @param  string  $number  Phone number (e.g., '08123456789' or '628123456789')
     * @param  string  $message  The message content
     * @return bool True if successful, false otherwise
     */
    public function sendMessage(string $number, string $message): bool
    {
        try {
            $url = config('services.whatsapp.url', 'http://127.0.0.1:3001/send-message');

            $response = Http::timeout(10)->post($url, [
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
