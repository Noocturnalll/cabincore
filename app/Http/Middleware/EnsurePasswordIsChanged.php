<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->is_default_password) {
            // Prevent redirect loops by checking if the user is already on the password change route
            if (! $request->routeIs('force-password-reset', 'logout')) {
                return redirect()->route('force-password-reset')
                    ->with('warning', 'You must change your default password to continue.');
            }
        }

        return $next($request);
    }
}
