<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // The project ships its own paginator view under
        // `resources/views/vendor/pagination/tailwind.blade.php`. Laravel
        // resolves the `pagination::` namespace against the application's
        // `vendor/pagination` directory first, so the file overrides the
        // framework's own without this line — the line is here to make the
        // intent explicit and to fail loudly if the path ever moves.
        //
        // Only the full view is registered. The simple view is deliberately
        // left alone: this view calls `total()`, which a SimplePaginator does
        // not have, and nothing in the application uses `simplePaginate()`.
        Paginator::defaultView('pagination::tailwind');
    }
}
