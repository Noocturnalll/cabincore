<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramController extends Controller
{
    public function webhook(Request $request)
    {
        $data = $request->all();

        if (! isset($data['message'])) {
            return response()->json(['status' => 'ok']);
        }

        $chatId = $data['message']['chat']['id'] ?? null;
        $text = $data['message']['text'] ?? '';

        if (! $chatId || empty($text)) {
            return response()->json(['status' => 'ok']);
        }

        $botToken = config('services.telegram.bot_token');
        if (! $botToken) {
            Log::error('Telegram Bot Token is missing from config/services.php');

            return response()->json(['status' => 'error']);
        }

        // Process Commands
        if (str_starts_with($text, '/start')) {
            $this->sendMessage($chatId, "Halo! Saya adalah CBM Assistant Bot 🤖\n\nGunakan perintah berikut:\n/status - Lihat ringkasan hari ini\n/nsrdi - Lihat total NSRDI open\n/ping - Cek koneksi bot", $botToken);
        } elseif (str_starts_with($text, '/ping')) {
            $this->sendMessage($chatId, 'Pong! Bot sedang aktif dan berjalan lancar. ✅', $botToken);
        } elseif (str_starts_with($text, '/status')) {
            $this->sendStatusReport($chatId, $botToken);
        } elseif (str_starts_with($text, '/nsrdi')) {
            $nsrdiOpen = DB::table('nsrdi_logs')->where('status', 'Open')->count();
            $this->sendMessage($chatId, "⚠️ *NSRDI Terbuka saat ini:* {$nsrdiOpen} kasus.", $botToken);
        } else {
            // Echo or ignore unknown commands
            $this->sendMessage($chatId, 'Maaf, saya tidak mengerti perintah tersebut. Coba /start', $botToken);
        }

        return response()->json(['status' => 'ok']);
    }

    private function sendStatusReport($chatId, $botToken)
    {
        $targetDate = now()->format('Y-m-d');
        $woOpen = DB::table('wo_logs')->where('status', 'Open')->count();
        $woClosed = DB::table('wo_logs')->whereDate('date', $targetDate)->where('status', 'Closed')->count();

        $msg = "📊 *STATUS CBM HARI INI* ({$targetDate})\n\n";
        $msg .= "WO Terbuka: {$woOpen}\n";
        $msg .= "WO Selesai: {$woClosed}\n\n";
        $msg .= 'Ketik /nsrdi untuk info khusus NSRDI.';

        $this->sendMessage($chatId, $msg, $botToken);
    }

    private function sendMessage($chatId, $text, $botToken)
    {
        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
        Http::post($url, [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ]);
    }
}
