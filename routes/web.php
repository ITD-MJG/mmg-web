<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Localised Routes
|--------------------------------------------------------------------------
|
| Every public route is registered once per locale. Route names are
| "{locale}.{name}", which is what LocaleUrls uses to derive hreflang
| alternates and the language switcher target. Adding a route means adding
| it inside this loop — never outside it — or hreflang silently loses a
| locale and the whole annotation set is discarded.
|
*/

foreach (config('app.locales') as $locale => $prefix) {
    Route::prefix($prefix)
        ->middleware("locale:{$locale}")
        ->name("{$locale}.")
        ->group(function () use ($locale) {
            Route::view('/', 'pages.home')->name('home');
        });
}
