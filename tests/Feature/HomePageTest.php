<?php

use App\Models\Principal;
use App\Models\Product;
use App\Models\ProductCategory;
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
    // two columns on a phone the column is narrower than a `max-w-56` frame, so
    // a single cap would either overflow small screens or waste the space a
    // wide one has. The caps were raised once the page counter went away, again
    // when the arrows were shrunk, and again when the register stopped using
    // the text shell and started running at `89vw`: every column is wider now,
    // and a cap left at the old value would leave the extra width empty.
    foreach (['max-w-40', 'sm:max-w-44', 'md:max-w-48', 'lg:max-w-52', 'xl:max-w-56'] as $utility) {
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
    // inside it. The register now runs at `89vw` less `px-4` and the two arrow
    // columns, so at 390px each column is about 100px and at 1440px each of the
    // six columns is about 175px. A cap below the column width is what makes
    // the mark smaller than the cell it sits in.
    //
    // The assertion is on the cap's own value rather than on the rendered size,
    // because the rendered size needs a browser and this suite has none.
    preg_match('#max-w-40[^\"]*sm:max-w-44[^\"]*md:max-w-48[^\"]*lg:max-w-52[^\"]*xl:max-w-56#', $section[0], $caps);
    expect($caps)->not->toBeEmpty('the frame caps are not in ascending order');

    // 10rem, 11rem, 12rem, 13rem, 14rem: 160px up to 224px. Tailwind's `max-w-*`
    // scale is 0.25rem per step, so these are the values the utilities resolve
    // to and the numbers the comment in the view quotes. The whole ladder is one
    // step above where it was, which is what the wider register pays for.
    foreach ([[40, 160], [44, 176], [48, 192], [52, 208], [56, 224]] as [$step, $px]) {
        expect($step * 4)->toBe($px);
    }

    // The register is wider than the text shell on purpose: the shell is sized
    // for reading, and a row of logos is scanned rather than read. Asserting the
    // width is what stops the caps above from being raised against a container
    // that quietly shrank back to `max-w-shell`.
    expect($section[0])->toContain('max-w-[89vw]')
        ->not->toContain('max-w-shell');
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
    expect(substr_count($section[0], 'max-md:hidden'))->toBe(2, 'both arrows must be hidden below md');
    expect($section[0])->not->toContain('md:block');

    // The strip still pages with no controls: the timer is what drives it, and
    // `reflect()` hides the arrows rather than the script depending on them.
    $js = file_get_contents(resource_path('js/app.js'));
    expect($js)->toContain('AUTOPLAY_DELAY')
        ->toContain('setTimeout');
});

it('makes the hero a full viewport tall and offsets it under the header', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    // A full viewport, and `svh` before `vh`. `svh` is the viewport with the
    // mobile browser chrome showing, which is the height a visitor actually has
    // when the page first paints; `vh` on a phone is the taller height with the
    // chrome hidden, so a `100vh` hero puts its own bottom edge under the fold
    // until the address bar retracts. Both are declared because a browser that
    // does not understand `svh` drops the second and keeps the first.
    preg_match('#\.hero-full\s*\{(.*?)\}#s', $css, $hero);
    expect($hero)->not->toBeEmpty('no .hero-full rule found');

    expect($hero[1])->toContain('min-height: 100vh')
        ->toContain('min-height: 100svh');

    // The header floats over the hero, so the section has to start at the very
    // top of the viewport and its copy has to start below the header. Both are
    // the one `--header-h` token, written together, so the offset cannot drift
    // from the header's own height the way two independent values would.
    expect($hero[1])->toContain('margin-top: calc(var(--header-h) * -1)')
        ->toContain('padding-top: var(--header-h)');

    // `main` has to establish a block formatting context, or that negative
    // margin collapses through it and moves the whole element instead of the
    // hero inside it.
    expect($css)->toContain('display: flow-root');

    // And the header itself is what the offset is measured against, so the
    // token must exist.
    expect($css)->toContain('--header-h:');
});

it('carries the header offset as one token rather than two numbers', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    // `--header-h` is the header's own arithmetic, and the hero reads it rather
    // than repeating it. A hardcoded offset in `.hero-full` would be a second
    // copy of the header's height, and the two would drift the first time the
    // logo grew.
    preg_match('#\.hero-full\s*\{(.*?)\}#s', $css, $hero);
    expect($hero)->not->toBeEmpty('no .hero-full rule found');

    expect((bool) preg_match('/calc\(\s*[0-9.]+rem\s*\*\s*-1\s*\)/', $hero[1]))
        ->toBeFalse('the hero hardcodes the header height instead of reading --header-h');

    // The header no longer has to fit in a fraction of the first screen, so the
    // fold group and its `3fr auto 1.5fr` rows are gone. Asserting their absence
    // is what stops the ratio coming back: it would silently put the hero back
    // at two thirds of a viewport and re-clip the register.
    expect($css)->not->toContain('.fold')
        ->not->toContain('grid-template-rows: 3fr auto 1.5fr')
        ->not->toContain('@media (max-height: 700px) and (min-width: 768px)');
});

it('gives the principal register its own band rather than a share of the fold', function () {
    Principal::factory()->count(6)->create(['is_published' => true]);

    $html = $this->get('/')->assertOk()->getContent();

    preg_match('#aria-labelledby="principal-heading".*?</section>#s', $html, $section);
    expect($section)->not->toBeEmpty('No principal carousel found');

    preg_match('#<section class="([^"]*)"\s+aria-labelledby="principal-heading"#', $html, $sectionClass);
    expect($sectionClass)->not->toBeEmpty('the principal section has no class attribute');

    // The band is content-sized now, so its own padding is the whole of its
    // breathing room. It steps up with the viewport rather than being one value
    // everywhere: the register is the widest row on the page and needs more
    // separation from the bands above and below it as it grows.
    expect($sectionClass[1])->toContain('py-14')
        ->toContain('md:py-20')
        ->toContain('lg:py-24');

    // A recessed surface rather than the canvas. The section boundary used to be
    // carried by a single hairline against the same warm near-white the hero
    // sits on, so the row of marks had nothing to sit on.
    expect($sectionClass[1])->toContain('bg-surface-muted');

    // The fold group's centring is gone with the fold group: as a `fr` row the
    // band was taller than its content, so padding only shrank the box the
    // content was centred in.
    expect($sectionClass[1])->not->toContain('md:py-0');
});

it('explains the principal register in a sentence under its heading', function () {
    Principal::factory()->count(6)->create(['is_published' => true]);

    $html = $this->get('/')->assertOk()->getContent();

    preg_match('#aria-labelledby="principal-heading".*?</section>#s', $html, $section);
    expect($section)->not->toBeEmpty('No principal carousel found');

    // The heading is a bare noun and the word "principal" is a loanword that not
    // every visitor will know, so the register is described in a sentence rather
    // than left to the logos to explain themselves.
    //
    // Asserted in both locales because the sentence is the section's only piece
    // of explanatory copy: an EN key that fell back to Indonesian would read as
    // a language switch mid-section, which is exactly the failure the key-parity
    // test cannot see.
    foreach (['id' => '/', 'en' => '/en'] as $locale => $uri) {
        $body = $this->get($uri)->assertOk()->getContent();

        expect($body)->toContain(__('ui.sections.principal_subtext', [], $locale));

        // Scoped to the section, so a sentence that rendered elsewhere on the
        // page would not satisfy it.
        preg_match('#aria-labelledby="principal-heading".*?</section>#s', $body, $scoped);
        expect($scoped)->not->toBeEmpty("No principal carousel found on {$uri}");
        expect($scoped[0])->toContain(e(__('ui.sections.principal_subtext', [], $locale)));
    }

    // It sits between the heading and the strip, and it is a paragraph rather
    // than a second heading: it describes the register, it does not name it.
    expect($section[0])->toMatch('#<h2 id="principal-heading".*?</h2>\s*<p#s');
    expect($section[0])->toContain('text-center');
});

it('runs the principal register wider than the text shell', function () {
    Principal::factory()->count(6)->create(['is_published' => true]);

    $html = $this->get('/')->assertOk()->getContent();

    preg_match('#aria-labelledby="principal-heading".*?</section>#s', $html, $section);
    expect($section)->not->toBeEmpty('No principal carousel found');

    // The shell is sized for reading a paragraph, and a row of logos is scanned
    // rather than read. The register is the one band that runs wider than it,
    // which is what gives every column a wider track and lets the caps above it
    // grow without the marks floating in empty cells.
    expect($section[0])->toContain('max-w-[89vw]');

    // `vw` and not `%`: a percentage would resolve against the section and
    // compound the `px-4` inside it, so the width would depend on how deeply the
    // container happened to nest.
    expect((bool) preg_match('/max-w-\[\d+%\]/', $section[0]))
        ->toBeFalse('the register sizes from a percentage instead of the viewport');

    // The rest of the page still uses the shared shell, or the gutters stop
    // lining up at the seams.
    $blade = file_get_contents(resource_path('views/pages/home.blade.php'));
    expect($blade)->toContain('max-w-shell');
});

it('renders a real photograph as the hero, with a srcset and reserved space', function () {
    $html = $this->get('/')->assertOk()->getContent();

    // Scope to the hero. The product grid also renders images further down, so
    // a page-wide assertion could pass on one of those instead.
    preg_match('#<section class="[^"]*hero-full[^"]*">.*?</section>#s', $html, $matches);
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

    preg_match('#<section class="[^"]*hero-full[^"]*">.*?</section>#s', $html, $matches);
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
    ProductCategory::factory()->create(['is_published' => true, 'name' => ['id' => 'Kategori Tampil']]);

    $this->get('/')->assertDontSee('Kategori Tampil');
});
