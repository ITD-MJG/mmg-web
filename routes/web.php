<?php

use App\Http\Controllers\HomeController;
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
            Route::get('/', HomeController::class)->name('home');

            // Placeholder routes for pages built in later tasks. Each is replaced
            // by its real controller when that task lands — see Tasks 11, 12, 13,
            // 14, 15. They exist now because the nav and the product card call
            // route() on these names, and route() on an unregistered name throws
            // RouteNotFoundException, which would stop the home page rendering at
            // all. The paths are the real locale-conditional paths, so only the
            // handler and view change when the real routes arrive.
            Route::view($locale === 'en' ? '/products' : '/produk', 'pages.placeholder')
                ->name('products.index');
            Route::view($locale === 'en' ? '/products/{slug}' : '/produk/{slug}', 'pages.placeholder')
                ->name('products.show');
            Route::view($locale === 'en' ? '/brands' : '/brand', 'pages.placeholder')
                ->name('brands.index');
            Route::view($locale === 'en' ? '/brands/{slug}' : '/brand/{slug}', 'pages.placeholder')
                ->name('brands.show');
            Route::view($locale === 'en' ? '/about' : '/tentang-kami', 'pages.placeholder')
                ->name('about');
            Route::view($locale === 'en' ? '/contact' : '/kontak', 'pages.placeholder')
                ->name('contact');
        });
}
