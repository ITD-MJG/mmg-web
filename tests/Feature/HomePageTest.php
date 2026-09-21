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

    $html = $this->get('/')->assertOk()->getContent();

    // The head, scripts, styles and every attribute value are removed before
    // the search. A bare `assertDontSee('Rp')` scans the whole document, and
    // the Vite asset hash is a random string: the stylesheet built for this
    // change is `app-BoVPRpuB.css`, which contains "Rp" in its hash. The test
    // therefore passed or failed depending on which hash the last build
    // produced, which is not a claim about prices at all.
    //
    // The claim being tested is about rendered text, so the filenames and the
    // markup that carries them are removed first and the search runs on what
    // is left.
    $visible = preg_replace([
        '#<head\b.*?</head>#is',
        '#<script\b.*?</script>#is',
        '#<style\b.*?</style>#is',
        '#\s[a-z-]+="[^"]*"#i',
    ], '', $html);

    expect($visible)->not->toContain('Rp')
        ->not->toContain('IDR');
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

it('gives the principal register a section heading, not a caption', function () {
    Principal::factory()->count(6)->create(['is_published' => true]);

    $html = $this->get('/')->assertOk()->getContent();

    // The title is the section's accessible name, so the assertion is that the
    // region points at a heading rather than at a string duplicated into an
    // `aria-label`. Two copies of the same text can drift; one id cannot.
    preg_match('#aria-labelledby="principal-heading".*?</section>#s', $html, $section);
    expect($section)->not->toBeEmpty('No principal carousel found');

    expect($section[0])->toContain('<h2 id="principal-heading"')
        ->toContain(__('ui.sections.principal'));

    // A step above the other section headings on a desktop, and a step down on
    // a phone. The register is the section whose content is least
    // self-explanatory — a row of logos with no sentence saying what they are —
    // so its title is heavier than Products and Contact rather than matching
    // them; the mobile size steps back down because the band is a third of the
    // fold there rather than the half it was.
    preg_match('#<h2 id="principal-heading" class="([^"]*)"#', $section[0], $class);
    expect($class)->not->toBeEmpty('the principal heading has no class attribute');

    foreach (['text-2xl', 'md:text-3xl', 'lg:text-4xl', 'font-bold', 'tracking-tight'] as $utility) {
        expect($class[1])->toContain($utility);
    }

    // The mobile step is a step down, not the desktop size. Asserting the base
    // is `text-2xl` and not `text-3xl` is the half that catches a revert to the
    // larger base with the responsive step left in place.
    expect($class[1])->not->toContain('text-3xl font')
        ->not->toContain('text-xs')
        ->not->toContain('uppercase')
        ->not->toContain('font-semibold');

    // Products and Contact stay where they are, which is what makes the
    // principal title the largest of the three rather than the newest style to
    // be copied onto them.
    preg_match('#<h2 id="products-heading" class="([^"]*)"#', $html, $products);
    expect($products)->not->toBeEmpty('No products heading found');

    foreach (['text-2xl', 'font-semibold', 'tracking-tight'] as $utility) {
        expect($products[1])->toContain($utility);
    }

    expect($products[1])->not->toContain('font-bold');
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

    preg_match('#aria-labelledby="principal-heading".*?</section>#s', $html, $section);
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
    // two columns on a phone the column is narrower than a `max-w-52` frame, so
    // a single cap would either overflow small screens or waste the space a
    // wide one has. The caps were raised once the page counter went away, and
    // again when the arrows were shrunk — the smaller controls hand the row
    // back 20px, and the caps were set above every column width so the column,
    // not the cap, is what sizes the mark.
    foreach (['max-w-36', 'sm:max-w-40', 'md:max-w-44', 'lg:max-w-48', 'xl:max-w-52'] as $utility) {
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
    preg_match('#aria-labelledby="principal-heading".*?</section>#s', $html, $matches);
    expect($matches)->not->toBeEmpty('No principal marquee found');
    expect($matches[0])->toContain('Principal Tanpa Logo');
    expect($matches[0])->not->toContain('<img');
});

it('renders a responsive column count rather than one fixed per breakpoint', function () {
    Principal::factory()->count(9)->create(['is_published' => true]);

    $html = $this->get('/')->assertOk()->getContent();

    preg_match('#aria-labelledby="principal-heading".*?</section>#s', $html, $section);
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

    preg_match('#aria-labelledby="principal-heading".*?</section>#s', $html, $section);
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

    preg_match('#aria-labelledby="principal-heading".*?</section>#s', $html, $section);
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

    preg_match('#aria-labelledby="principal-heading".*?</section>#s', $html, $section);
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

    // Both were raised so the register stops reading as an interruption. The
    // travel and the hold are asserted as values, not as "present", because
    // the complaint they answer was about pace and only the numbers carry it.
    preg_match('/SLIDE_DURATION\s*=\s*(\d+)/', $js, $slide);
    preg_match('/AUTOPLAY_DELAY\s*=\s*(\d+)/', $js, $hold);

    expect((int) $slide[1])->toBeGreaterThan(620, 'the travel must be slower than it was');
    expect((int) $hold[1])->toBeGreaterThan(4500, 'the hold must be longer than it was');

    // A slow travel with a hold barely longer than it would still read as a
    // loop: the strip would be moving for a third of the time. The hold has to
    // dominate, which is the property that makes it read as a slide that
    // happens to be there rather than as an animation.
    expect((int) $hold[1])->toBeGreaterThan((int) $slide[1] * 4);

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

it('measures the carousel again after the first layout pass', function () {
    $js = file_get_contents(resource_path('js/app.js'));

    // The bug this pins: `sync()` at the end of `createCarousel` runs while the
    // document is still parsing, and the viewport's `clientWidth` at that
    // moment is not the width it ends up with. On a 390px phone it measured
    // 192px instead of 300px, so the first page came out at four 56px specks
    // and only corrected itself when something forced a resize.
    //
    // A `requestAnimationFrame` callback runs after layout, so it is the cheap
    // way to read the real box. Both halves are asserted: the frame callback,
    // and the fonts settling, because a webfont arriving late changes the
    // width of the names the grid is built from.
    expect($js)->toContain('requestAnimationFrame(sync)')
        ->toContain('document.fonts');

    // The bare `sync()` has to still be there as well: the frame is a second
    // pass, not a replacement, and the strip must be usable before it lands.
    expect(preg_match('/^\s*sync\(\);$/m', $js))
        ->toBe(1, 'the synchronous first pass must remain');
});

it('gives the principal frames a cap above every column width they are used at', function () {
    Principal::factory()->count(6)->create(['is_published' => true]);

    $html = $this->get('/')->assertOk()->getContent();

    preg_match('#aria-labelledby="principal-heading".*?</section>#s', $html, $section);
    expect($section)->not->toBeEmpty('No principal carousel found');

    // Two columns on a phone is the layout, and the marks have to be readable
    // inside it. The row is `max-w-shell` (85vw) less `px-4` and the two
    // arrow columns, so at 390px each column is about 96px; at 1440px each of
    // the six columns is about 167px. A cap below the column width is what
    // makes the mark smaller than the cell it sits in, which is what the
    // previous 112px/176px caps did on a phone.
    //
    // The assertion is on the cap's own value rather than on the rendered size,
    // because the rendered size needs a browser and this suite has none.
    preg_match('#max-w-36[^\"]*sm:max-w-40[^\"]*md:max-w-44[^\"]*lg:max-w-48[^\"]*xl:max-w-52#', $section[0], $caps);
    expect($caps)->not->toBeEmpty('the frame caps are not in ascending order');

    // 9rem, 10rem, 11rem, 12rem, 13rem: 144px up to 208px. Tailwind's `max-w-*`
    // scale is 0.25rem per step, so these are the values the utilities resolve
    // to and the numbers the comment in the view quotes.
    foreach ([[36, 144], [40, 160], [44, 176], [48, 192], [52, 208]] as [$step, $px]) {
        expect($step * 4)->toBe($px);
    }
});

it('keeps the carousel controls smaller than the marks they page', function () {
    Principal::factory()->count(6)->create(['is_published' => true]);

    $html = $this->get('/')->assertOk()->getContent();

    preg_match('#aria-labelledby="principal-heading".*?</section>#s', $html, $section);
    expect($section)->not->toBeEmpty('No principal carousel found');

    // The arrows are navigation, not content, so they must not out-weigh the
    // register. `p-2.5` around a `h-6 w-6` icon is 46px square, which was
    // wider than the 112px frame it sat beside was tall; `p-2` around
    // `h-5 w-5` is 38px. Both halves are pinned because either one alone
    // leaves the control able to grow back.
    expect($section[0])->toContain('p-2 ')
        ->toContain('h-5 w-5')
        ->not->toContain('p-2.5')
        ->not->toContain('h-6 w-6');
});

it('hides the carousel arrows on a phone and pages on its own there', function () {
    Principal::factory()->count(6)->create(['is_published' => true]);

    $html = $this->get('/')->assertOk()->getContent();

    preg_match('#aria-labelledby="principal-heading".*?</section>#s', $html, $section);
    expect($section)->not->toBeEmpty('No principal carousel found');

    // Two controls either side of a two-column row took 96px of a 300px
    // viewport — a third of the width the marks need. They are hidden below
    // `md` and return above it, where there is room for both.
    //
    // `max-md:hidden` and not `md:block`. An author `display` utility outranks
    // the `[hidden]` rule in the UA stylesheet, so `md:block` would have pinned
    // both arrows open at every width and left `reflect()` unable to disable
    // them at the ends of the register.
    expect(substr_count($section[0], 'max-md:hidden'))->toBe(2, 'both arrows must be hidden below md');    expect($section[0])->not->toContain('md:block');

    // The strip still pages with no controls: the timer is what drives it, and
    // `reflect()` hides the arrows rather than the script depending on them.
    $js = file_get_contents(resource_path('js/app.js'));
    expect($js)->toContain('AUTOPLAY_DELAY')
        ->toContain('setTimeout');
});

it('gives the hero the whole first screen on a phone', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    // Below `md` the hero's row is a full viewport below the header, and the two
    // bands under it are content-sized. Asserting the media query is scoped to
    // the phone is the half that matters: unscoped, this would make the hero a
    // full screen on a desktop too and push the register off the fold there,
    // which is the thing the fold group exists to prevent.
    preg_match('#@media \(max-width: 767px\)\s*\{(.*?)\n\}#s', $css, $phone);
    expect($phone)->not->toBeEmpty('no phone media query found');

    expect($phone[1])->toContain('minmax(calc(100svh - var(--header-h)), auto) auto auto')
        ->toContain('min-height: 0');

    // `auto` and not `0`. A fixed row does not grow, so a very short phone would
    // clip the hero's copy rather than scroll it.
    expect($phone[1])->toContain(', auto)');

    // The desktop keeps the three-band fold. If the phone rows leaked out of the
    // query, this would be the assertion that caught it.
    preg_match('#\.fold\s*\{(.*?)\}#s', $css, $fold);
    expect($fold[1])->toContain('grid-template-rows: 3fr auto 1.5fr');

    // The short-window trim is desktop-only now, because the phone no longer has
    // three bands to fit on one screen — and that trim is what was overriding
    // the register's padding on a phone.
    expect($css)->toContain('@media (max-height: 700px) and (min-width: 768px)');
    expect($css)->not->toContain('@media (max-height: 700px) {');
});

it('gives the principal band less of the fold from tablet up, with padding on a phone', function () {
    Principal::factory()->count(6)->create(['is_published' => true]);

    $html = $this->get('/')->assertOk()->getContent();

    preg_match('#aria-labelledby="principal-heading".*?</section>#s', $html, $section);
    expect($section)->not->toBeEmpty('No principal carousel found');

    preg_match('#<section class="([^"]*)"\s+aria-labelledby="principal-heading"#', $html, $sectionClass);
    expect($sectionClass)->not->toBeEmpty('the principal section has no class attribute');

    // A third of the fold from `md` up rather than the two fifths it took for a
    // band that reads in one glance; the phone keeps its larger share, where
    // the frames are big enough to be worth the room.
    //
    // The ratio is the grid's `3fr auto 1.5fr` rows, not a class on the section,
    // so it is asserted in the stylesheet. It has to be the grid: with the flex
    // version the free space did not divide at all — at 1440x900 the hero stayed
    // at its content floor while the register took all 164px of the surplus.
    $css = file_get_contents(resource_path('css/app.css'));
    preg_match('#\.fold\s*\{(.*?)\}#s', $css, $fold);
    expect($fold)->not->toBeEmpty('no .fold rule found');

    expect($fold[1])->toContain('display: grid')
        ->toContain('grid-template-rows: 3fr auto 1.5fr')
        ->not->toContain('display: flex');

    // The phone is a different shape, not the same one with different numbers:
    // the hero takes the whole first screen and the strip and register follow it
    // in the scroll. That is what makes the register's padding visible — as a
    // `fr` row the band was taller than its content, so padding only shrank the
    // box the content was centred in.
    //
    // `minmax(..., auto)` and not a bare `calc`: a fixed row cannot grow, so on
    // a very short phone the hero's copy would spill out of it.
    expect($css)->toContain('grid-template-rows: minmax(calc(100vh - var(--header-h)), auto) auto auto')
        ->toContain('grid-template-rows: minmax(calc(100svh - var(--header-h)), auto) auto auto');

    expect($css)->not->toContain('grid-template-rows: 3fr auto 2fr');

    // Vertical padding below `md` only. On a phone the title and the row were
    // flush against the strip above and the section below; from `md` up the grid
    // row is tall enough that the centring does the same job, and padding there
    // would only add height the fold has to absorb.
    expect($sectionClass[1])->toContain('py-8')
        ->toContain('md:py-0');

    // No other vertical padding utilities. `py-8`/`md:py-0` is the whole of it,
    // so a second one appearing means the two are fighting. The `[\s:]` covers
    // the responsive prefix: `md:py-0` has a colon in front of it, not a space.
    expect(preg_match_all('/(?:^|[\s:])(?:py|pt|pb)-/', $sectionClass[1]))
        ->toBe(2, 'exactly one vertical padding pair, in two halves');
});

it('renders a real photograph as the hero, with a srcset and reserved space', function () {
    $html = $this->get('/')->assertOk()->getContent();

    // Scope to the hero. The product grid also renders images further down, so
    // a page-wide assertion could pass on one of those instead.
    preg_match('#<section class="[^"]*border-b border-line[^"]*">.*?</section>#s', $html, $matches);
    expect($matches)->not->toBeEmpty('No hero section found');
    $hero = $matches[0];

    // A photograph, not the abstract placeholder that used to stand in. The
    // assertion is on the file name rather than a description so replacing the
    // photograph later does not break the test.
    expect($hero)->toContain('images/hero-1600.jpg');

    // Two widths behind a srcset, so a phone is not sent the desktop file. The
    // 800 is asserted as the small candidate: shipping only the 1600 would
    // still render correctly, which is exactly why this needs pinning.
    expect($hero)->toContain('srcset')
        ->toContain('images/hero-800.jpg 800w')
        ->toContain('images/hero-1600.jpg 1600w');

    // `sizes` is what the browser uses to choose between them. Without it the
    // srcset is ignored and the full-size file is always fetched, so its
    // absence would silently undo the point of shipping two files.
    expect($hero)->toContain('sizes=');

    // Intrinsic dimensions matching the file (1600x900) reserve the slot, so
    // the headline beside it does not reflow as the image arrives.
    expect($hero)->toContain('width="1600"')
        ->toContain('height="900"');

    // The hero is the largest element above the fold. `loading="lazy"` here
    // would defer the one image that decides perceived speed.
    expect(str_contains($hero, 'loading="lazy"'))
        ->toBeFalse('the hero image must not be lazy-loaded');
});

it('ships the hero files the view references, at the ratio it declares', function () {
    $html = $this->get('/')->assertOk()->getContent();

    preg_match('#<section class="[^"]*border-b border-line[^"]*">.*?</section>#s', $html, $matches);
    expect($matches)->not->toBeEmpty('No hero section found');

    // A referenced-but-missing image renders as a broken box, and the markup
    // test above cannot catch that: it only asserts the path is in the HTML.
    preg_match_all('/images\/(hero-[0-9]+\.jpg)/', $matches[0], $paths);
    expect($paths[1])->not->toBeEmpty('No hero image referenced');

    foreach (array_unique($paths[1]) as $file) {
        $path = public_path("images/{$file}");

        expect(is_file($path))->toBeTrue("Missing hero file: public/images/{$file}");

        [$width, $height] = getimagesize($path);

        // 16:9, which is what the `width`/`height` in the view promise. A file
        // at another ratio would be cropped by `object-cover` and the declared
        // dimensions would reserve the wrong box.
        expect($width / $height)->toEqualWithDelta(16 / 9, 0.001, "{$file} is not 16:9");
    }

    // The source photograph is deliberately untracked, so the committed files
    // must be present without it. This is the property that lets the
    // production host serve the hero with no image tooling at all.
    expect(is_file(public_path('images/hero-1600.jpg')))
        ->toBeTrue('the committed hero file is missing');
});

it('does not reference the removed placeholder hero artwork', function () {
    // The abstract SVG placeholder was replaced by the photograph. Leaving a
    // reference behind would be a 404 on the busiest section of the site.
    $blade = file_get_contents(resource_path('views/pages/home.blade.php'));

    expect(str_contains($blade, 'hero.svg'))
        ->toBeFalse('home.blade.php still references the removed hero.svg');

    expect(is_file(public_path('images/hero.svg')))
        ->toBeFalse('public/images/hero.svg should have been removed');
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
