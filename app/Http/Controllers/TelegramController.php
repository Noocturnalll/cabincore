<?php

namespace App\Http\Controllers;

use App\Services\Dja\CodReportService;
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
            $this->sendMessage($chatId, "Halo! Saya adalah CBM Assistant Bot 🤖\n\nGunakan perintah berikut:\n/status atau /cod - Laporan resmi Cabin On-Duty Production\n/nsrdi - Status NSRDI open\n/dmi - Status DMI open\n/cml - Status CML open\n/wo - Status Work Order\n/ping - Cek koneksi bot", $botToken);
        } elseif (str_starts_with($text, '/ping')) {
            $this->sendMessage($chatId, 'Pong! Bot sedang aktif dan berjalan lancar. ✅', $botToken);
        } elseif (str_starts_with($text, '/status') || str_starts_with($text, '/cod')) {
            $this->sendStatusReport($chatId, $botToken);
        } elseif (str_starts_with($text, '/nsrdi')) {
            $nsrdiOpen = DB::table('nsrdi_logs')->where('status', 'Open')->count();
            $this->sendMessage($chatId, "⚠️ *NSRDI Terbuka saat ini:* {$nsrdiOpen} kasus.", $botToken);
        } elseif (str_starts_with($text, '/dmi')) {
            $dmiOpen = DB::table('dmi_logs')->where('status', 'Open')->count();
            $this->sendMessage($chatId, "🔧 *DMI Terbuka saat ini:* {$dmiOpen} kasus.", $botToken);
        } elseif (str_starts_with($text, '/cml')) {
            $cmlOpen = DB::table('cml_logs')->where('status', 'Open')->count();
            $this->sendMessage($chatId, "📋 *CML Terbuka saat ini:* {$cmlOpen} kasus.", $botToken);
        } elseif (str_starts_with($text, '/wo')) {
            $woOpen = DB::table('wo_logs')->where('status', 'Open')->count();
            $woToday = DB::table('wo_logs')->whereDate('date', now()->format('Y-m-d'))->where('status', 'Closed')->count();
            $this->sendMessage($chatId, "🛠️ *Status Work Order:*\n▫️ Terbuka (Total): {$woOpen}\n▫️ Selesai (Hari ini): {$woToday}", $botToken);
        } else {
            $this->sendMessage($chatId, 'Maaf, saya tidak mengerti perintah tersebut. Ketik /start untuk melihat menu.', $botToken);
        }

        return response()->json(['status' => 'ok']);
    }

    private function sendStatusReport($chatId, $botToken)
    {
        $codService = app(CodReportService::class);
        $msg = $codService->generateReportText();

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
