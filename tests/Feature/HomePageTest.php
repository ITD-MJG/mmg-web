<?php

use App\Models\Category;
use App\Models\Principal;
use App\Models\Product;
use App\Models\Setting;

it('renders the home page in both locales', function () {
    $this->get('/')->assertOk();
    $this->get('/en')->assertOk();
});

it('shows the latest products', function () {
    $product = Product::factory()->create(['is_published' => true]);

    $this->get('/')->assertSee($product->getTranslation('name', 'id'));
});

it('never renders a price', function () {
    Product::factory()->create(['is_published' => true]);

    $response = $this->get('/');

    $response->assertDontSee('Rp', false);
    $response->assertDontSee('IDR');
});

it('shows at most six products, newest first', function () {
    $old = Product::factory()->create(['is_published' => true, 'name' => ['id' => 'Tertua']]);
    Product::factory()->count(6)->create(['is_published' => true]);

    $response = $this->get('/');

    $response->assertDontSee('Tertua');
});

it('renders the facilities marquee from config', function () {
    $first = config('site.facility_types')[0];

    $this->get('/')->assertSee($first);
});

it('renders the principal carousel from published principals only', function () {
    Principal::factory()->create(['is_published' => true, 'name' => 'Principal Tampil']);
    Principal::factory()->create(['is_published' => false, 'name' => 'Principal Tersembunyi']);

    $response = $this->get('/');

    $response->assertSee('Principal Tampil');
    $response->assertDontSee('Principal Tersembunyi');
});

it('renders a principal logo when one is set, and its name as the alt text', function () {
    Principal::factory()->create([
        'is_published' => true,
        'name' => 'Principal Berlogo',
        'logo' => 'images/principals/mindray.png',
    ]);

    $html = $this->get('/')->assertOk()->getContent();

    // A seeded logo is a shipped public asset, so it must resolve through
    // `asset()` to a URL the browser can actually fetch. Asserting only on the
    // filename would pass on a path that 404s.
    expect($html)->toContain(asset('images/principals/mindray.png'));

    // The name stays the accessible name; a screen reader must not be handed
    // the filename.
    expect($html)->toContain('alt="Principal Berlogo"');
});

it('frames every principal mark in a square that reserves its space', function () {
    Principal::factory()->create([
        'is_published' => true,
        'name' => 'Principal Berlogo',
        'logo' => 'images/principals/mindray.png',
    ]);

    $html = $this->get('/')->assertOk()->getContent();

    preg_match('#aria-label="Principal".*?</section>#s', $html, $section);
    expect($section)->not->toBeEmpty('No principal carousel found');

    // The frame is square because the files are: the normaliser in `scripts/`
    // writes 1:1 PNGs, and `object-contain` then fits each mark to the frame
    // instead of the frame to the mark. Without the class a wide wordmark and
    // a square badge would render at different heights in the same row.
    expect($section[0])->toContain('aspect-square');

    // Intrinsic dimensions matching the file (512x512) reserve the plate
    // before the image arrives. Drop them and the row reflows as the marks
    // load, which is a layout shift on the busiest section of the home page.
    expect($section[0])->toContain('width="512"')
        ->toContain('height="512"');

    // The plate is dark-mode only. In light mode the frame is deliberately
    // empty: an unconditional white plate was invisible while it hugged the
    // logo's own bounding box, but the frame is larger than the mark now, so
    // an unconditional plate renders as a white box around every logo.
    expect($section[0])->toContain('dark:bg-logo-plate');

    // The frame grows with the grid rather than being pinned to one size: at
    // two columns on a phone the column is narrower than a `max-w-44` frame, so
    // a single cap would either overflow small screens or waste the space a
    // wide one has. The caps were raised once the page counter went away.
    foreach (['max-w-28', 'sm:max-w-32', 'md:max-w-36', 'lg:max-w-40', 'xl:max-w-44'] as $utility) {
        expect($section[0])->toContain($utility);
    }
});

it('falls back to the principal name when no logo is set', function () {
    Principal::factory()->create([
        'is_published' => true,
        'name' => 'Principal Tanpa Logo',
        'logo' => null,
    ]);

    $html = $this->get('/')->assertOk()->getContent();

    // Scope to the principal strip: the name also appears in no other place on
    // the home page, but scoping keeps the assertion honest if that changes.
    preg_match('#aria-label="Principal".*?</section>#s', $html, $matches);
    expect($matches)->not->toBeEmpty('No principal marquee found');
    expect($matches[0])->toContain('Principal Tanpa Logo');
    expect($matches[0])->not->toContain('<img');
});

it('renders a responsive column count rather than one fixed per breakpoint', function () {
    Principal::factory()->count(9)->create(['is_published' => true]);

    $html = $this->get('/')->assertOk()->getContent();

    preg_match('#aria-label="Principal".*?</section>#s', $html, $section);
    expect($section)->not->toBeEmpty('No principal carousel found');

    // The column count is a grid class, not a number the server decided: how
    // many marks fit on a row depends on the viewport. Asserting the breakpoint
    // list rather than a single count is the point of this test.
    foreach (['grid-cols-2', 'sm:grid-cols-3', 'md:grid-cols-4', 'lg:grid-cols-5', 'xl:grid-cols-6'] as $utility) {
        expect($section[0])->toContain($utility);
    }

    // No fixed slide count anywhere: the old implementation chunked the list
    // server-side into slides of four, which is exactly what cannot be correct
    // across breakpoints.
    expect($section[0])->not->toContain('data-pages')
        ->not->toContain('--carousel-pages');
});

it('renders every principal up front so the section works without JavaScript', function () {
    // Paging happens in the browser, so the server must not withhold anything.
    // If it did, a visitor without JavaScript would see only the first page.
    $principals = Principal::factory()->count(9)->create(['is_published' => true]);

    $html = $this->get('/')->assertOk()->getContent();

    preg_match('#aria-label="Principal".*?</section>#s', $html, $section);
    expect($section)->not->toBeEmpty('No principal carousel found');

    foreach ($principals as $principal) {
        // `e()` because the rendered form is escaped; this keeps the test about
        // the count rather than about escaping.
        expect(substr_count($section[0], e($principal->name)))
            ->toBe(1, "{$principal->name} should render exactly once");
    }

    // Nothing is hidden by default. Hiding is applied by `app.js`; if the
    // markup shipped items already hidden, the no-JavaScript view would be
    // broken in a way no screenshot in a JS-enabled browser would show.
    //
    // Counted as an attribute (` hidden` followed by whitespace or `>`) rather
    // than by substring: the surrounding comments mention the word "hidden",
    // and a plain substring count picks those up.
    preg_match_all('/\shidden(?=[\s>])/', $section[0], $hiddenAttrs);

    // The two controls are hidden until the script runs, and nothing else.
    expect($hiddenAttrs[0])->toHaveCount(2, 'Only the two controls may ship hidden');

    // The strip is clipped by a viewport element rather than by hiding items,
    // so the slide has a box to travel inside. Without it the strip would run
    // past the edge of the section.
    expect($section[0])->toContain('data-carousel-viewport');

    // The viewport must carry no horizontal padding: any would make the visible
    // width differ from the width a page steps by, and the two would drift.
    preg_match('#data-carousel-viewport[^>]*class="([^"]*)"#', $section[0], $viewportClass);
    expect($viewportClass)->not->toBeEmpty('Carousel viewport has no class attribute');
    expect($viewportClass[1])->toContain('overflow-hidden')
        ->not->toMatch('/\bpx-|\bpl-|\bpr-|\bp-[0-9]/');
});

it('uses buttons rather than radio inputs for the arrows', function () {
    Principal::factory()->count(9)->create(['is_published' => true]);

    $html = $this->get('/')->assertOk()->getContent();

    preg_match('#aria-label="Principal".*?</section>#s', $html, $section);
    expect($section)->not->toBeEmpty('No principal carousel found');

    // Paging needs to know the current position to know where "next" is, and
    // that state lives in `app.js`. The radio-group mechanism could not express
    // it: CSS has no way to read the checked input's number, so any hardcoded
    // target went dead past the first click.
    expect($section[0])->not->toContain('type="radio"')
        ->not->toContain('carousel-dot');

    // Real buttons, one per direction, each wired by a data attribute.
    expect(substr_count($section[0], 'data-carousel-prev'))->toBe(1);
    expect(substr_count($section[0], 'data-carousel-next'))->toBe(1);

    // Both are translated and both are hidden until the script has run, so a
    // no-JavaScript visitor is not shown two controls that do nothing.
    //
    // Matched as a pattern rather than a literal because the attributes are on
    // separate lines, and a hardcoded run of spaces would break the moment the
    // markup is reformatted.
    expect($section[0])->toContain(__('ui.carousel.previous'))
        ->toContain(__('ui.carousel.next'));

    expect($section[0])->toMatch('/data-carousel-prev\s+hidden/');
    expect($section[0])->toMatch('/data-carousel-next\s+hidden/');
});

it('navigates the carousel with the arrows alone', function () {
    Principal::factory()->count(9)->create(['is_published' => true]);

    $html = $this->get('/')->assertOk()->getContent();

    preg_match('#aria-label="Principal".*?</section>#s', $html, $section);
    expect($section)->not->toBeEmpty('No principal carousel found');

    // The arrows disable at the ends, which is the only position indicator the
    // section has. A page counter used to sit under the grid; it was removed
    // rather than hidden, because it duplicated what the arrows already say and
    // it was the widest element in the section.
    expect($section[0])->not->toContain('data-carousel-status')
        ->not->toContain('role="status"');
});

it('leaves no carousel machinery in CSS', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    // The paging moved to `app.js`. Leaving the CSS behind would be dead rules
    // that read like the old mechanism is still live, and the peer selectors
    // would silently stop matching anything.
    expect($css)->not->toContain('carousel-track')
        ->not->toContain('carousel-shell')
        ->not->toContain('carousel-dot')
        ->not->toContain('carousel-current')
        ->not->toContain('carousel-pages');

    // The reverse keyframe existed only for the principal marquee, which is
    // also gone.
    expect($css)->not->toContain('@keyframes marquee-reverse')
        ->not->toContain('--animate-marquee-reverse');
});

it('ships carousel behaviour in the JavaScript entry the layout already loads', function () {
    // The paging needs the current page to compute the next one, which is state
    // the browser owns. `resources/js/app.js` is already an input to Vite and
    // already loaded by the layout, so this needs no new build wiring.
    $js = file_get_contents(resource_path('js/app.js'));

    expect($js)->toContain('[data-carousel]')
        ->toContain('[data-carousel-track]')
        ->toContain('[data-carousel-prev]')
        ->toContain('[data-carousel-next]')
        ->toContain('[data-carousel-viewport]');

    // The page size must come from the layout, not from a second copy of the
    // breakpoint list: reading the resolved `grid-template-columns` is what
    // keeps the count and the grid from drifting apart.
    expect($js)->toContain('gridTemplateColumns');

    // The arrows are the whole navigation. A status counter used to be wired
    // here too; leaving the selector behind would be a live-looking reference
    // to an element the view no longer renders.
    expect($js)->not->toContain('data-carousel-status');
});

it('slides a page at a time rather than swapping which items exist', function () {
    $js = file_get_contents(resource_path('js/app.js'));

    // The move is a transform on the track. Hiding and showing items would make
    // the remaining columns shift to close the gap, which is exactly the
    // alignment the slide depends on.
    expect($js)->toContain('translate3d')
        ->toContain('transform');

    // A move off the end lands on a duplicate of the first page and is then
    // swapped back with the transition off, so the loop has no visible seam.
    expect($js)->toContain('data-carousel-clone')
        ->toContain('aria-hidden');

    // The step is the stride between a page and the next, not the viewport
    // width: the columns either side of the seam are a gap apart, so stepping
    // by the viewport alone drifts one gap further out on every page.
    expect($js)->toContain('perPage * (columnWidth + gap)');

    // Nothing may be hidden to page. The old mechanism set the `hidden`
    // attribute on each item; the strip is clipped by its viewport instead.
    // The arrows are still hidden when there is nothing to page, which is why
    // this asserts on the item rather than on the attribute.
    expect($js)->not->toContain('item.hidden')
        ->not->toContain('items.forEach');
});

it('advances the carousel on a timer that yields to the visitor', function () {
    $js = file_get_contents(resource_path('js/app.js'));

    // A named hold and delay, so the pace is one thing to change rather than a
    // number buried in a callback.
    expect($js)->toContain('AUTOPLAY_DELAY')
        ->toContain('SLIDE_DURATION');

    // Every reason the strip stops, and each has to be able to stop it on its
    // own. Pointer and focus are the two a visitor causes directly; the other
    // two are the strip being invisible, which is not a moment to move.
    foreach (['pointerenter', 'pointerleave', 'focusin', 'focusout', 'visibilitychange', 'IntersectionObserver'] as $hook) {
        expect($js)->toContain($hook);
    }

    // Reduced motion turns the travel off and the timer with it, rather than
    // only shortening the duration. Asserting the query is consulted twice is
    // the point: once where a move decides whether to animate, and once where
    // the timer decides whether to start. A single gate would leave either a
    // strip that animates or one that pages on its own.
    expect($js)->toContain('prefers-reduced-motion')
        ->toContain('matchMedia');

    expect(substr_count($js, 'prefersReducedMotion.matches'))
        ->toBeGreaterThanOrEqual(2, 'Reduced motion must gate both the travel and the timer');
});

it('shows the contact details from settings', function () {
    Setting::set('address', 'Jl. Contoh No. 1, Jakarta');

    $this->get('/')->assertSee('Jl. Contoh No. 1, Jakarta');
});

it('renders the footer copyright with the current year and company name', function () {
    Setting::set('company_name', 'Medquest Mitra Global');

    $this->get('/')->assertSee(now()->year.' - Medquest Mitra Global');
});

it('does not render a category grid on the home page', function () {
    Category::factory()->create(['is_published' => true, 'name' => ['id' => 'Kategori Tampil']]);

    $this->get('/')->assertDontSee('Kategori Tampil');
});
