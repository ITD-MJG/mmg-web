<?php

use App\Support\LocaleUrls;
use Illuminate\Http\Request;

/**
 * Resolve a request bound to a real, boot-registered route.
 *
 * Runtime route registration is deliberately avoided: Laravel caches route
 * names at boot, so routes added inside a test are not visible to Route::has
 * until the lookup table is refreshed. Testing against boot routes exercises
 * the same code path production uses.
 */
function requestFor(string $routeName): Request
{
    $request = Request::create('/');
    $route = app('router')->getRoutes()->getByName($routeName);

    expect($route)->not->toBeNull("Route {$routeName} is not registered");

    $request->setRouteResolver(fn () => $route);

    return $request;
}

it('builds alternates for both locales from a route name', function () {
    $alternates = LocaleUrls::alternates(requestFor('id.home'));

    expect($alternates)->toHaveKeys(['id', 'en'])
        ->and($alternates['en'])->toEndWith('/en')
        ->and($alternates['id'])->not->toContain('/en');
});

it('returns fully qualified absolute urls', function () {
    foreach (LocaleUrls::alternates(requestFor('id.home')) as $url) {
        expect($url)->toStartWith('http');
    }
});

it('returns no alternates when the request has no route', function () {
    $request = Request::create('/');
    $request->setRouteResolver(fn () => null);

    expect(LocaleUrls::alternates($request))->toBe([]);
});

it('returns no alternates when the request has no named route', function () {
    $request = Request::create('/up');

    // The health-check route is real and unnamed.
    $route = app('router')->getRoutes()->match($request);

    expect($route->getName())->toBeNull();

    $request->setRouteResolver(fn () => $route);

    expect(LocaleUrls::alternates($request))->toBe([]);
});

it('switches to the equivalent route when a counterpart exists', function () {
    expect(LocaleUrls::switchTo(requestFor('id.home'), 'en'))->toEndWith('/en')
        ->and(LocaleUrls::switchTo(requestFor('en.home'), 'id'))->not->toContain('/en');
});

it('falls back to the locale home page for a route with no counterpart', function () {
    $request = Request::create('/');
    $request->setRouteResolver(fn () => app('router')->getRoutes()->getByName('filament.admin.auth.login'));

    // The Filament login route has no locale-prefixed twin, so the switcher
    // must still return a working URL rather than 404.
    expect(LocaleUrls::switchTo($request, 'en'))->toEndWith('/en');
});
