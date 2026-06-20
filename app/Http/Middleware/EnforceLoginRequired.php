<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * When the "login_required" setting is on, the whole public site is gated:
 * guests are redirected to the login page. Auth pages and the Filament admin
 * (which has its own login) remain reachable so users can actually sign in.
 */
class EnforceLoginRequired
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() || ! Setting::flag('login_required')) {
            return $next($request);
        }

        if ($this->isExempt($request)) {
            return $next($request);
        }

        return redirect()->guest(route('login'));
    }

    private function isExempt(Request $request): bool
    {
        return $request->is(
            'login',
            'register',
            'forgot-password',
            'reset-password',
            'reset-password/*',
            'admin',
            'admin/*',
        );
    }
}
