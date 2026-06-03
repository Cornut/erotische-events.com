<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale', 'de');

        if (in_array($locale, ['de', 'en'], true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
