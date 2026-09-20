<?php

namespace App\Http\Controllers;

use App\Models\Principal;
use App\Models\Product;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('pages.home', [
            'products' => Product::published()
                ->with(['category', 'principal', 'images'])
                ->orderByDesc('created_at')
                // created_at is second-precision, so products seeded or
                // imported in one go share a timestamp. Without a tiebreaker
                // "the newest six" is whichever rows MySQL happens to return
                // for the tie group, which is not stable across runs.
                ->orderByDesc('id')
                ->take(6)
                ->get(),
            // Every published principal is shown. The carousel pages through
            // the set in the browser rather than truncating it here, so capping
            // the query would silently hide brands the company distributes for.
            // The old cap of 12 was below the real register of 26, so it
            // dropped half of them.
            //
            // How many appear per page is deliberately not decided here: it
            // depends on the viewport, which the server cannot know. `app.js`
            // reads the resolved column count from the grid instead.
            'principals' => Principal::published()->orderBy('sort_order')->get(),
            'facilities' => config('site.facility_types'),
        ]);
    }
}
