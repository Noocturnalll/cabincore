<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Machine-to-machine endpoints (Apps Script, Telegram) carry a shared secret in a header.
 * Usage: ->middleware('webhook.secret:services.sheets_sync.token,X-Sync-Token')
 * A secret that is not configured closes the endpoint instead of leaving it open.
 */
class VerifyWebhookSecret
{
    public function handle(Request $request, Closure $next, string $configKey, string $header): Response
    {
        $expected = (string) config($configKey);
        $given = (string) $request->header($header);

        if ($expected === '' || ! hash_equals($expected, $given)) {
            abort(403, 'Forbidden');
        }

        return $next($request);
    }
}
