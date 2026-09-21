<?php

use App\Models\Principal;
use App\Models\Product;
use App\Models\Setting;

/**
 * Guards the design system's invariants.
 *
 * These are the properties that are easy to break silently: a hardcoded hex
 * in a view, a translated-looking string that is actually English, an accent
 * colour creeping in from somewhere else. None of them fail a functional
 * test, which is exactly why they need their own.
 */
it('defines every design token in both light and dark schemes', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    // Split at the dark-mode block so each scheme can be checked separately.
    $darkAt = strpos($css, 'prefers-color-scheme: dark');
    expect($darkAt)->not->toBeFalse('No dark-mode block found in app.css');

    $light = substr($css, 0, $darkAt);
    $dark = substr($css, $darkAt);

    $tokens = [
        '--canvas', '--surface', '--surface-muted',
        '--ink', '--ink-muted', '--ink-subtle',
        '--line', '--line-strong',
        '--accent', '--accent-hover', '--accent-ink', '--accent-soft',
    ];

    foreach ($tokens as $token) {
        // Match `--token:` but not `--token-something:`, so --ink does not
        // pass on the strength of --ink-muted.
        expect($light)->toMatch('/'.preg_quote($token, '/').'\s*:/', "{$token} missing from light scheme");
        expect($dark)->toMatch('/'.preg_quote($token, '/').'\s*:/', "{$token} missing from dark scheme");
    }
});

it('keeps the two-tier radius scale the views rely on', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    expect($css)->toContain('--r-card')
        ->toContain('--r-control')
        ->toContain('--radius-card')
        ->toContain('--radius-control');
});

it('renders the home page without any em-dash or en-dash', function () {
    // The design system bans these characters outright. They are the most
    // common AI-writing tell and they render inconsistently across the
    // fonts in use, so the rule is binary rather than stylistic.
    //
    // Content is seeded deliberately. Without it the marquee and grid
    // sections do not render at all, and this test would pass on an empty
    // page while proving nothing. That was verified by mutation: an em-dash
    // injected into the principal marquee went undetected until a principal
    // existed to render it.
    Principal::factory()->create(['is_published' => true, 'name' => 'Principal Satu']);
    Product::factory()->create(['is_published' => true]);
    Setting::set('address', 'Jl. Contoh No. 1, Jakarta');

    foreach (['/', '/en'] as $uri) {
        $html = $this->get($uri)->assertOk()->getContent();

        // Assert the seeded content actually rendered, so a future change
        // that empties these sections fails loudly here instead of turning
        // the dash check back into a no-op.
        expect($html)->toContain('Principal Satu');

        // Strip the inline <style> block: CSS comments may legitimately
        // describe ranges, and they are not visible to a reader.
        $visible = preg_replace('#<style\b.*?</style>#is', '', $html);
        $visible = preg_replace('#<!--.*?-->#s', '', $visible);

        // `expect(...)->not->toContain($needle, $message)` is NOT usable here:
        // Pest's `not` modifier swallows the message argument, and the
        // assertion was observed passing on a string that plainly contained
        // the character. Explicit booleans fail correctly, which was verified
        // by mutation.
        expect(str_contains($visible, "\u{2014}"))->toBeFalse("em-dash on {$uri}");
        expect(str_contains($visible, "\u{2013}"))->toBeFalse("en-dash on {$uri}");
    }
});

it('translates every aria-label rather than hardcoding English', function () {
    $html = $this->get('/')->assertOk()->getContent();

    preg_match_all('/aria-label="([^"]+)"/', $html, $matches);
    expect($matches[1])->not->toBeEmpty('No aria-labels found to check');

    // The Indonesian page must not carry English chrome. These are the exact
    // strings that were hardcoded before this was fixed.
    //
    // Written as explicit booleans for the same reason as the dash check:
    // `not->toContain($needle, $message)` swallows the message and was
    // observed passing while the needle was present.
    foreach (['Primary', 'Primary mobile', 'Facility types'] as $english) {
        expect(in_array($english, $matches[1], true))
            ->toBeFalse("Untranslated aria-label on /: {$english}");
    }
});

it('uses design tokens rather than raw palette utilities in views', function () {
    $views = collect([
        resource_path('views/layouts/app.blade.php'),
        resource_path('views/pages/home.blade.php'),
        resource_path('views/partials/nav.blade.php'),
        resource_path('views/partials/footer.blade.php'),
        resource_path('views/partials/logo.blade.php'),
        resource_path('views/partials/product-card.blade.php'),
    ]);

    foreach ($views as $path) {
        $blade = file_get_contents($path);

        // A Tailwind default-palette utility means a colour escaped the token
        // layer and will not follow the dark-mode swap.
        expect($blade)->not->toMatch(
            '/\b(?:bg|text|border|ring)-(?:slate|zinc|gray|neutral|stone)-\d{2,3}\b/',
            basename($path).' uses a raw palette utility instead of a token',
        );
    }
});

it('provides a visible focus style and honours reduced motion', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    expect($css)->toContain(':focus-visible')
        ->toContain('prefers-reduced-motion');
});

it('sizes every page container from a single shell token', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    // Tailwind v4 maps `--container-*` to the `max-w-*` utilities, so this one
    // value is the content width for the whole site.
    preg_match('/--container-shell:\s*([\d.]+)rem/', $css, $matches);
    expect($matches)->not->toBeEmpty('No --container-shell token in app.css');

    // The brief is a wide desktop canvas. Below 7xl (80rem) the container is
    // narrower than the Tailwind default this replaced, which would be a
    // silent regression back to the cramped layout.
    expect((float) $matches[1])->toBeGreaterThanOrEqual(80.0);

    $views = [
        'pages/home.blade.php',
        'partials/nav.blade.php',
        'partials/footer.blade.php',
    ];

    foreach ($views as $relative) {
        $blade = file_get_contents(resource_path("views/{$relative}"));

        // Header, footer, and page sections must share one container or the
        // gutters stop lining up down the seams.
        expect(str_contains($blade, 'max-w-shell'))
            ->toBeTrue("{$relative} has no max-w-shell container");

        // A hardcoded width would silently re-narrow one section and break
        // the vertical line the header and footer share. Written as an
        // explicit boolean for the same reason as the dash check above:
        // `not->toMatch($pattern, $message)` swallows the message.
        expect((bool) preg_match('/max-w-(?:7xl|6xl|5xl|4xl)\b/', $blade))
            ->toBeFalse("{$relative} hardcodes a container width instead of max-w-shell");
    }
});
