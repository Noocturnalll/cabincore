<?php

namespace App\Http\Controllers;

use App\Services\WhatsApp\WhatsAppEvidenceProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    /**
     * Handle incoming webhook requests from WhatsApp gateway.
     */
    public function handle(Request $request, WhatsAppEvidenceProcessor $processor): JsonResponse
    {
        // Optional webhook secret verification if configured
        $secret = config('services.whatsapp.webhook_secret');
        if ($secret) {
            $headerSecret = $request->header('X-WhatsApp-Token') ?? $request->header('X-Hub-Signature-256') ?? $request->query('token');
            if ($headerSecret !== $secret) {
                return response()->json(['error' => 'Unauthorized webhook token'], 401);
            }
        }

        $payload = $request->all();

        // Support webhook verification challenges (e.g. Meta / webhook ping)
        if ($request->has('hub_challenge')) {
            return response()->json($request->input('hub_challenge'));
        }

        try {
            $result = $processor->process($payload);

            return response()->json([
                'status' => 'success',
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('WhatsApp Webhook Processing Exception: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'payload' => $payload,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
