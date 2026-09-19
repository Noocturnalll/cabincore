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
            if (! $request->routeIs('password.force-change', 'logout')) {
                return redirect()->route('password.force-change')
                    ->with('warning', 'You must change your default password to continue.');
            }
        }

        return $next($request);
    }
}
