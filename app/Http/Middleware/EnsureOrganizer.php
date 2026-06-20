<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the organizer self-service area: a logged-in user without an organizer
 * profile is sent to the registration page so they can create one.
 */
class EnsureOrganizer
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->organizer === null) {
            return redirect()->route('organizer.register');
        }

        return $next($request);
    }
}
