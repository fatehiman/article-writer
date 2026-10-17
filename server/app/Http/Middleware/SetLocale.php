<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** UI language: the user's choice, then the session (login page), then the default. */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locales = array_keys(config('languages.ui'));
        $locale = $request->user()?->locale ?? $request->session()->get('locale') ?? config('app.locale');
        app()->setLocale(in_array($locale, $locales, true) ? $locale : config('app.locale'));

        return $next($request);
    }
}
