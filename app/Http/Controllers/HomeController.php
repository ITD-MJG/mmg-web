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
            'principals' => Principal::published()->orderBy('sort_order')->take(12)->get(),
            'facilities' => config('site.facility_types'),
        ]);
    }
}
