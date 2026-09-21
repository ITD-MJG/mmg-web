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

    // Split at the dark block so each scheme can be checked separately. The
    // selector is `:root.dark` because dark is opt-in; a `prefers-color-scheme`
    // query here would mean the theme still followed the OS, which is the
    // behaviour this replaced.
    $darkAt = strpos($css, ':root.dark {');
    expect($darkAt)->not->toBeFalse('No :root.dark block found in app.css');

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

it('defaults to light rather than following the operating system', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    // The theme is driven by the `.dark` class, so nothing may read the OS
    // preference. This is the whole requirement: a dark-mode laptop must not
    // be served the dark palette unasked. Asserted against the token layer
    // rather than the whole file because the generated Tailwind output
    // legitimately contains `prefers-reduced-motion`.
    expect($css)->not->toContain('@media (prefers-color-scheme: dark)');

    // Light is the base scheme, so the base block must declare it. This is
    // what keeps browser-drawn chrome (scrollbars, form controls) light even
    // when the OS is dark.
    expect($css)->toContain('color-scheme: light');

    // The class-scoped variant is what makes `dark:` utilities follow the
    // same switch as the tokens. Without it the tokens would be light while
    // `dark:bg-logo-plate` still tracked the OS, and the two would disagree.
    expect($css)->toContain('@custom-variant dark')
        ->toContain('.dark *');

    // Nothing in the shipped application may set the class, or the default
    // would not be light. A toggle is what would introduce one.
    $views = glob(resource_path('views/**/*.blade.php'));
    $views = array_merge($views, glob(resource_path('views/*.blade.php')));
    $views = array_unique($views);

    foreach ($views as $path) {
        $relative = str_replace(resource_path('views/'), '', $path);

        // `welcome.blade.php` is the untouched Laravel default: not routed,
        // not part of the site, and full of stock `dark:` classes.
        if ($relative === 'welcome.blade.php') {
            continue;
        }

        expect((bool) preg_match('/<html[^>]*\bdark\b/', file_get_contents($path)))
            ->toBeFalse("{$relative} hardcodes the dark class on <html>");
    }

    $js = file_get_contents(resource_path('js/app.js'));

    expect((bool) preg_match('/classList\.add\(\s*[\'"]dark[\'"]\s*\)/', $js))
        ->toBeFalse('app.js adds the dark class, so light is no longer the default');
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
    preg_match('/--container-shell:\s*([\d.]+)(vw|rem)/', $css, $matches);
    expect($matches)->not->toBeEmpty('No --container-shell token in app.css');

    // The brief is a viewport-relative width: the container should keep the same
    // proportion of the screen at every size, rather than stopping at a cap and
    // letting the margin grow. A `rem` value here is the regression this guards:
    // it would still be a legal CSS length, and the page would still look right
    // on a laptop, so nothing else would catch it.
    expect($matches[2])->toBe('vw', 'the shell must be viewport-relative, not a fixed cap');

    // "Increase the width to 85%" is the requirement. Asserted as an exact
    // value rather than a range, because the whole point of a percentage is
    // that the proportion is deliberate.
    expect((float) $matches[1])->toBe(85.0);

    // `vw`, not `%`. A percentage resolves against the containing block, so a
    // section with its own padding would compound it into the gutter and the
    // content would get narrower the deeper it nested. The two are
    // indistinguishable on the home page and only diverge on inner pages,
    // which is exactly the kind of bug that ships.
    expect($css)->not->toContain('--container-shell: 85%');

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
