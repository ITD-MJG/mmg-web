<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class LocaleUrls
{
    /**
     * Absolute URL of the current page in every supported locale.
     *
     * Route names follow "{locale}.{name}", so the alternate for a given
     * locale is the same route name with the locale segment swapped. This is
     * the only place that mapping happens — callers never build hreflang by
     * hand, which is what keeps self-reference and reciprocity structural
     * rather than something a template can forget.
     *
     * Returns an empty array when the current request has no named route, so
     * error pages and non-localised routes emit no hreflang at all rather
     * than a partial set. Partial sets are discarded wholesale by Google.
     *
     * @return array<string, string>
     */
    public static function alternates(Request $request): array
    {
        $route = $request->route();

        if (! $route || ! $route->getName()) {
            return [];
        }

        $bare = Str::after($route->getName(), '.');

        // parameters() throws when a Route object has not been dispatched.
        // Real requests always have bound parameters, but route-name tooling
        // and tests construct Route objects without them.
        try {
            $params = $route->parameters();
        } catch (\LogicException) {
            $params = [];
        }

        $urls = [];

        foreach (array_keys(config('app.locales')) as $locale) {
            $name = "{$locale}.{$bare}";

            if (Route::has($name)) {
                $urls[$locale] = route($name, $params);
            }
        }

        // An incomplete set is worse than none: Google ignores non-reciprocal
        // annotations. Only emit when every locale resolved.
        if (count($urls) !== count(config('app.locales'))) {
            return [];
        }

        return $urls;
    }

    /**
     * The same page in another locale, for the language switcher.
     *
     * Falls back to that locale's home page when the current route has no
     * counterpart, so the switcher never produces a 404.
     */
    public static function switchTo(Request $request, string $locale): string
    {
        return self::alternates($request)[$locale] ?? route("{$locale}.home");
    }
}
