<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Resolve the locale from the route group and apply it to the request.
     *
     * The locale is not sniffed from the browser: it comes from the URL prefix
     * only, so every page has exactly one canonical address per locale and
     * hreflang can be derived from route names alone.
     */
    public function handle(Request $request, Closure $next, string $locale): Response
    {
        abort_unless(array_key_exists($locale, config('app.locales')), 404);

        app()->setLocale($locale);
        Carbon::setLocale($locale);
        View::share('locale', $locale);

        return $next($request);
    }
}
