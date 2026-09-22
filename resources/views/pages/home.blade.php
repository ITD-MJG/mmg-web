@extends('layouts.app')

@section('title', \App\Models\Setting::get('default_meta_title', 'Medquest Mitra Global'))

{{-- The header is transparent on this page and sits on the hero photograph.
     `partials/nav` reads this section; no other page pushes it. See the Header
     block in `resources/css/app.css`. --}}
@section('header-overlay', 'true')

@section('content')
    {{-- 1. Hero ------------------------------------------------------------
         A full viewport tall, with the photograph running full-bleed behind
         the message under a scrim, so the white type keeps its contrast over
         the bright areas of the frame.

         `.hero-full` owns the height and the header offset. It lifts the
         section under the sticky header with a negative margin and pays the
         same amount back as padding, so the photograph starts at the very top
         of the viewport and the copy starts below the header. Both are the
         `--header-h` token, so the offset cannot drift from the header's own
         height.

         The copy is centred in the band and left-aligned within a capped
         column: centred type over a photograph reads as a poster, and a
         procurement lead scanning for a company name does not want to hunt for
         the start of a line. The vertical centring is what puts the block in
         the optical middle of a screen that is now a full viewport rather than
         a fraction of one.

         The type and the padding step down below `sm` because a phone has
         roughly half the height for the same four-line headline. --}}
    <section class="hero-full relative isolate flex items-center overflow-hidden bg-ink">
        {{-- The photograph is still a real <img> rather than a CSS background,
             so it keeps alt text, srcset selection, and high fetch priority.
             It is absolutely positioned to fill the section, which is what
             makes the scrim below it a single flat layer instead of a
             per-element shadow.

             The photograph is a clinical laboratory, which is the part of
             the catalogue the company is least visible in and the part a
             procurement lead is most likely to be buying for. Its source,
             licence, and the script that produced these files are recorded
             in `scripts/build-hero-image.php`.

             `sizes` is `100vw` because the image is the full viewport
             width at every breakpoint.

             No `loading="lazy"`: this is the largest element above the fold
             on every visit, and deferring it would delay the one image that
             decides the page's perceived speed. --}}
        <img src="{{ asset('images/hero-1600.jpg') }}"
             srcset="{{ asset('images/hero-800.jpg') }} 800w, {{ asset('images/hero-1600.jpg') }} 1600w"
             sizes="100vw"
             alt="{{ __('ui.hero.image_alt') }}"
             width="1600"
             height="900"
             fetchpriority="high"
             decoding="async"
             class="absolute inset-0 -z-20 h-full w-full object-cover">

        <div class="absolute inset-0 -z-10 bg-black/55" aria-hidden="true"></div>

        <div class="mx-auto w-full max-w-shell px-4 py-16 sm:py-20">
            <h1 class="max-w-3xl text-3xl font-semibold tracking-tight text-white text-balance sm:text-4xl lg:text-5xl lg:leading-[1.1]">
                {{ __('ui.hero.heading') }}
            </h1>
            <p class="mt-5 max-w-xl text-base leading-relaxed text-white/90 sm:mt-6 sm:text-lg sm:leading-loose">
                {{ __('ui.hero.subtext') }}
            </p>

            {{-- The two buttons keep their layout and change their
                 destinations: the primary jumps to the principal register and
                 the secondary opens the contact page. The labels live in
                 `lang/*/ui.php`, so what a visitor reads and where the button
                 goes are decided together in one place.

                 The primary is an in-page anchor rather than a route. "About
                 us" is answered on this page: the register of manufacturers
                 the company represents is the most concrete statement of who
                 it is, and it is already here. Sending a visitor to a separate
                 page to read a shorter version of the same thing is a detour,
                 so the button scrolls to the section instead.

                 The target is the section's own `id`, and the sticky header is
                 cleared by `scroll-margin-top` in the stylesheet. Without that
                 the browser would align the section's top edge with the top of
                 the viewport, which is where the header is, and the heading
                 would arrive already hidden. --}}
            <div class="mt-8 flex flex-wrap items-center gap-3 sm:mt-10">
                {{-- `px-4` below `sm` rather than `px-5`: the pair is 295px wide
                     at the desktop padding and the content box is 287px on a
                     375px phone, so the second button wrapped onto its own row. --}}
                <a href="#principal"
                   class="rounded-control bg-accent px-4 py-3 text-sm font-semibold text-accent-ink transition-colors hover:bg-accent-hover sm:px-5">
                    {{ __('ui.hero.cta') }}
                </a>
                <a href="{{ route("{$locale}.contact") }}"
                   class="rounded-control border border-white/40 px-4 py-3 text-sm font-semibold text-white transition-colors hover:bg-white/10 sm:px-5">
                    {{ __('ui.hero.secondary_cta') }}
                </a>
            </div>
        </div>
    </section>

    {{-- 2. Facilities marquee -----------------------------------------------
         Breadth without weight: these are the facility types the company
         serves, and a marquee reads them as a category signal rather than a
         list to be studied.

         Each item is separated by a hairline rule rather than by whitespace
         alone, so a run of names reads as a set of distinct entries instead of
         one long phrase. The rule and its spacing are one pseudo-element on the
         item (`.marquee-item`), and the list carries no `gap` of its own: the
         interval across the seam between the two copies then equals the
         interval between any other two items, which is what keeps the loop
         seamless.

         `md:text-base` rather than `text-sm` throughout: at the desktop width
         the strip was the smallest type on the page by a wide margin, which
         read as a footnote to the hero rather than as a section of its own.
         The phone keeps the smaller size, where the strip is competing with the
         hero for the same screen.

         The colour is `text-ink-muted` rather than `text-ink-subtle`. The
         subtle token is for labels that are meant to recede; these are content,
         and at the smaller size the extra contrast is what makes them legible
         at a glance as they move. --}}
    @if (filled($facilities))
        <section class="border-b border-line bg-surface py-5" aria-label="{{ __('ui.sections.facilities') }}">
            <div class="marquee overflow-hidden">
                <div class="flex w-max animate-marquee items-center">
                    @foreach ([1, 2] as $copy)
                        <ul class="flex items-center" @if ($copy === 2) aria-hidden="true" @endif>
                            @foreach ($facilities as $facility)
                                <li class="marquee-item whitespace-nowrap text-sm font-medium tracking-wide text-ink-muted md:text-base">
                                    {{ $facility }}
                                </li>
                            @endforeach
                        </ul>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- 3. Principal carousel ------------------------------------------------
         A paged carousel rather than a marquee. The marquee made the set
         readable but not scannable: a logo slid past and was gone, so a
         visitor could not see who the manufacturers are in one look. Paging
         presents the register as a set, and the wireframe's two-marquee rule is
         amended deliberately here rather than broken by accident.

         Each principal renders its mark where one exists, and its name where
         one does not. The name is always the accessible text: as the `alt`
         when a logo is present, and as the visible fallback when it is not, so
         a screen reader never gets a bare filename.

         Every mark is normalised into a 1:1 frame. The sourced files ranged
         from 3:1 wordmarks to square badges, so a row of them at a shared
         height read as a ragged line: the wide marks were scaled down to fit
         and the square ones towered over them. One frame shape for the set
         makes the row scan as a register rather than as a pile of unrelated
         images. `scripts/normalize-principal-logos.php` produces those frames
         and is the reason every file in `public/images/principals` is square.

         The frame is not a card: no border, and no fill in light mode. A white
         plate is applied in dark mode only, where dark artwork on a dark
         surface would otherwise be unreadable.

         The column count is a responsive grid class rather than a number the
         server decided. How many marks fit on a row depends on the viewport,
         which the server cannot know, so the count has to come from the
         browser. `app.js` reads the resolved `grid-template-columns` and pages
         by whatever it finds, which also means the two can never drift.

         The whole list is rendered and JavaScript only decides which items are
         visible. With no JavaScript every principal is shown in the same grid,
         so the section degrades to a plain logo wall rather than to a single
         page or to nothing.

         Navigation is the two arrows and nothing else. The arrows already
         disable at the ends, which is the same information a page counter
         would carry without a second element to keep in step with the first.

         The movement pauses while the pointer is over the strip, or while a
         keyboard visitor has focus inside it, and it is not started at all when
         the visitor has asked for reduced motion. --}}
    @if ($principals->isNotEmpty())
        {{-- Named by the heading rather than by `aria-label`, so the section
             appears in the page's heading outline and the two cannot drift.

             `bg-surface-muted` rather than the canvas. The register is a
             distinct band and it now reads as one: the canvas behind it is the
             same warm near-white as the hero's surroundings, so the row of
             marks had nothing to sit on and the section boundary was carried by
             a single hairline. The muted step is one recessed level down from
             the white the strip above uses, which separates the three bands
             without introducing a colour the palette does not already have.

             The band is no longer a row of the fold grid, so its vertical
             padding is what gives it room: `py-14` up to `py-24` on a wide
             screen. That padding is the whole of the section's breathing space
             now, where the fold's grid row used to do the work. --}}
        <section class="flex scroll-mt-[var(--header-h)] flex-col justify-center border-b border-line bg-surface-muted py-14 md:py-20 lg:py-24"
                 aria-labelledby="principal-heading"
                 id="principal">
            {{-- `max-w-shell` is deliberately not used here. The register is the
                 one band on the page that runs wider than the text column: the
                 shell is sized for reading, and a row of logos is not read, it
                 is scanned. The extra 2vw per side gives every mark a wider
                 track at every breakpoint without changing the header's
                 alignment with the copy above and below.

                 `vw` and not `%` for the same reason the shell token uses it: a
                 percentage would resolve against the section and compound the
                 `px-4` inside it. --}}
            <div class="mx-auto w-full max-w-[89vw] px-4">
                {{-- Larger and heavier than the other section headings on
                     purpose. Products and Contact are `text-2xl`/`text-3xl` at
                     `font-semibold`; this is a step above both, because the
                     register is the section whose content is least
                     self-explanatory. --}}
                <h2 id="principal-heading" class="text-center text-2xl font-bold tracking-tight text-ink md:text-3xl lg:text-4xl">
                    {{ __('ui.sections.principal') }}
                </h2>

                {{-- The sentence that says what the row below it is. It is the
                     reason the heading could stay a bare noun: the register is
                     named in the heading and described here, and a visitor who
                     has never heard the word "principal" is told what they are
                     looking at before they look at it.

                     `text-ink-muted` and a step below the heading in size, so it
                     reads as the heading's support rather than as a second
                     heading competing with it. --}}
                <p class="mx-auto mt-3 max-w-2xl text-center text-sm leading-relaxed text-ink-muted sm:text-base">
                    {{ __('ui.sections.principal_subtext') }}
                </p>

                <div class="mt-10" data-carousel>
                    <div class="flex items-center gap-2 sm:gap-4">
                        {{-- The arrows are real buttons, not labels bound to an
                             input. Paging needs to know the current position to
                             know where "next" is, and that state lives in
                             `app.js`. `disabled` on the ends is the honest state
                             for a control that cannot move.

                             They are `hidden` until JavaScript has run, so a
                             no-JavaScript visitor is not shown two dead
                             controls. `app.js` reveals them once it has paged
                             the list.

                             `max-md:hidden`: on a phone the strip pages on its
                             own, and two controls either side of a two-column
                             row would take a third of the width the marks need.
                             A `md:block` would have been wrong: an author
                             `display` utility outranks the `[hidden]` rule, so
                             it would have pinned both arrows open at every
                             width and made `reflect()` unable to disable them. --}}
                        <button type="button"
                                data-carousel-prev
                                hidden
                                class="max-md:hidden shrink-0 rounded-control border border-line p-2 text-ink-muted transition-colors hover:border-line-strong hover:text-ink disabled:pointer-events-none disabled:opacity-40"
                                aria-label="{{ __('ui.carousel.previous') }}">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75"
                                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12.5 15L7.5 10l5-5"/>
                            </svg>
                        </button>

                        <div class="min-w-0 flex-1">
                            {{-- The viewport clips the strip to the one row it
                                 shows. It is also what the script measures to
                                 know how far a page travels: the page is as wide
                                 as the viewport, so the step is the viewport's
                                 own width rather than a number written down in
                                 two places. `overflow-hidden` is the clip; the
                                 track inside it is wider than the box.

                                 It carries no margin of its own, because any
                                 horizontal padding here would make the visible
                                 width differ from the width a page steps by. --}}
                            <div data-carousel-viewport class="overflow-hidden">
                                {{-- One square frame per principal. The frame owns the
                                     size and the mark is fitted inside it, so a wide
                                     wordmark and a square badge occupy the same box
                                     and the row reads as a register. --}}
                                <ul data-carousel-track
                                    class="grid grid-cols-2 place-items-center gap-x-4 gap-y-8 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                                    @foreach ($principals as $principal)
                                        {{-- The frame is `aspect-square`, so it is square at
                                             every viewport. The cap grows with the grid: at
                                             2 columns on a phone the column is narrower than a
                                             `max-w-44` frame would be, so a single cap would
                                             either overflow small screens or waste the space a
                                             wide one has. `place-items-center` centres the
                                             mark inside the frame.

                                             The caps are one step above where they were, in
                                             step with the section's own width: the register
                                             now runs to `89vw` rather than the text shell, so
                                             every column is wider and the mark grows into it
                                             instead of leaving the extra space empty.

                                             Once the script takes over, the single-row layout
                                             is written on the track inline: the grid class
                                             still decides how many columns there are, and
                                             `app.js` lays them out in one row and hands each
                                             one the width the class would have given it. --}}
                                        <li data-carousel-item
                                            class="grid aspect-square w-full max-w-40 place-items-center sm:max-w-44 md:max-w-48 lg:max-w-52 xl:max-w-56">
                                            @if ($principal->logoUrl())
                                                {{-- Intrinsic `width`/`height` are the file's own
                                                     dimensions (512x512, set by the normaliser),
                                                     and match the frame's 1:1 ratio, so the plate
                                                     is reserved before the image arrives and the
                                                     row does not reflow as marks load. --}}
                                                <img src="{{ $principal->logoUrl() }}"
                                                     alt="{{ $principal->name }}"
                                                     width="512"
                                                     height="512"
                                                     loading="lazy"
                                                     class="h-full w-full rounded-sm object-contain p-1 dark:bg-logo-plate">
                                            @else
                                                <span class="text-center text-sm leading-tight font-medium tracking-tight text-ink-muted">
                                                    {{ $principal->name }}
                                                </span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>

                        <button type="button"
                                data-carousel-next
                                hidden
                                class="max-md:hidden shrink-0 rounded-control border border-line p-2 text-ink-muted transition-colors hover:border-line-strong hover:text-ink disabled:pointer-events-none disabled:opacity-40"
                                aria-label="{{ __('ui.carousel.next') }}">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75"
                                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M7.5 5l5 5-5 5"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- 4. Products: grid 3x2 + full-width CTA ----------------------------- --}}
    <section class="border-t border-line bg-surface py-16 lg:py-20" aria-labelledby="products-heading">
        <div class="mx-auto max-w-shell px-4">
            <div class="flex flex-wrap items-baseline justify-between gap-4">
                <h2 id="products-heading" class="text-2xl font-semibold tracking-tight text-ink lg:text-3xl">
                    {{ __('ui.sections.products') }}
                </h2>
                {{-- The trailing arrow is what marks this as a link rather than
                     as a label, and it moves on hover the way the product
                     cards' own detail arrow does, so the two read as the same
                     affordance. --}}
                <a href="{{ route("{$locale}.products.index") }}"
                   class="group inline-flex items-center gap-1.5 text-sm font-medium text-accent transition-colors hover:text-accent-hover">
                    {{ __('ui.products.cta') }}
                    <svg class="h-3.5 w-3.5 transition-transform duration-300 group-hover:translate-x-0.5"
                         viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8h10m0 0L9 4m4 4-4 4"/>
                    </svg>
                </a>
            </div>

            @if ($products->isEmpty())
                {{-- An empty state, and nothing else. It used to carry a
                     "Request a Quote" button, which put a call to action under
                     a heading that had just said there is nothing to act on:
                     the visitor came to look at products and is offered a
                     sales conversation instead, before being told anything
                     about what the company distributes. The sentence explains
                     the situation; the page's own Contact section, and the
                     button in the header, are both one scroll or one click
                     away for a visitor who does want to talk. --}}
                <div class="mt-8 rounded-card border border-dashed border-line-strong bg-canvas px-6 py-14 text-center">
                    <p class="text-sm text-ink-muted">{{ __('ui.products.empty') }}</p>
                </div>
            @else
                <div class="mt-8 grid grid-cols-1 gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($products as $product)
                        @include('partials.product-card', ['product' => $product])
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- 5. Contact Us: map (left) + details (right) ------------------------ --}}
    @php
        $address = \App\Models\Setting::get('address');
        $phone = \App\Models\Setting::get('contact_phone');
        $email = \App\Models\Setting::get('contact_email');
        $whatsapp = \App\Models\Setting::get('whatsapp');
        // Digits only: wa.me rejects spaces, dashes, and a leading '+'.
        $whatsappDigits = $whatsapp ? preg_replace('/\D+/', '', $whatsapp) : null;
        $hasDetails = filled($address) || filled($phone) || filled($email) || filled($whatsappDigits);

        // Geo, seeded from the company's own Maps listing. Strings rather than
        // floats: the settings table stores JSON and a float would round-trip
        // with locale-dependent formatting.
        $latitude = \App\Models\Setting::get('latitude');
        $longitude = \App\Models\Setting::get('longitude');

        // The company's registered name, which is what a visitor is looking for
        // on the map. It is a setting, so an install that has renamed itself
        // shows the name it was given rather than a hardcoded one.
        $companyName = \App\Models\Setting::get('company_name', 'Medquest Mitra Global');

        // One query for the map, and it is the address when there is one and
        // the coordinates otherwise. The coordinates are the fallback rather
        // than the primary because a search for a street address resolves to
        // the building's own pin and its name, where a bare coordinate pair
        // resolves to a dropped pin with no label on it.
        $mapQuery = filled($address)
            ? $address
            : (filled($latitude) && filled($longitude) ? "{$latitude},{$longitude}" : null);
    @endphp

    <section class="border-t border-line py-16 lg:py-20" aria-labelledby="contact-heading">
        <div class="mx-auto max-w-shell px-4">
            {{-- Centred, because the section is a single column of information
                 rather than a grid with a leading edge: the title sits over the
                 map and the details, not beside them. --}}
            <h2 id="contact-heading" class="text-center text-2xl font-semibold tracking-tight text-ink lg:text-3xl">
                {{ __('ui.sections.contact') }}
            </h2>

            @if (! $hasDetails)
                {{-- An unconfigured install must not render an empty two-column
                     shell. Say so plainly instead. --}}
                <p class="mt-6 text-center text-sm text-ink-muted">{{ __('ui.contact.unconfigured') }}</p>
            @else
                <div class="mt-10 grid gap-8 md:grid-cols-2 md:gap-12">
                    {{-- Map is guarded: an unconfigured install renders the
                         details column alone rather than a broken iframe.
                         Plain embed URL, no API key and no JS SDK.

                         One pin, not a scatter. The `q=` form with an address
                         gives Google's own listing pin, which is the entity the
                         visitor is trying to find; it also carries the place
                         name on the pin's label. The `ll=`/`z=` variant that was
                         tried here centred the map on a coordinate and dropped
                         an unlabelled marker on it, which is the same place but
                         a worse answer to "where are they". --}}
                    @if (filled($mapQuery))
                        <div class="overflow-hidden rounded-card border border-line bg-surface-muted">
                            <iframe
                                title="{{ __('ui.contact.map_title') }} - {{ $companyName }}"
                                src="https://www.google.com/maps?q={{ urlencode($mapQuery) }}&amp;output=embed"
                                loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"
                                class="h-80 w-full border-0"></iframe>
                        </div>
                    @endif

                    {{-- `text-base` rather than `text-sm`, and `text-ink` rather
                         than `text-ink-muted` on the values. This is the one
                         place on the page a visitor copies something down: an
                         address, a phone number, an email. The muted token is
                         for supporting copy, and at the smaller size the
                         address was the least legible text on a page whose
                         whole job is to be contactable.

                         The labels stay `text-xs`/`uppercase`/`text-ink-subtle`
                         so the contrast between label and value is carried by
                         size and colour together rather than by colour alone. --}}
                    <dl class="grid gap-6 self-center text-base sm:grid-cols-2">
                        @if (filled($address))
                            <div class="sm:col-span-2">
                                <dt class="text-xs font-semibold tracking-wide text-ink-subtle uppercase">
                                    {{ __('ui.contact.address') }}
                                </dt>
                                <dd class="mt-2 leading-relaxed text-ink">{{ $address }}</dd>
                            </div>
                        @endif

                        @if (filled($phone))
                            <div>
                                <dt class="text-xs font-semibold tracking-wide text-ink-subtle uppercase">
                                    {{ __('ui.contact.phone') }}
                                </dt>
                                <dd class="mt-2">
                                    <a href="tel:{{ $phone }}"
                                       class="text-ink transition-colors hover:text-accent">{{ $phone }}</a>
                                </dd>
                            </div>
                        @endif

                        @if (filled($email))
                            <div>
                                <dt class="text-xs font-semibold tracking-wide text-ink-subtle uppercase">
                                    {{ __('ui.contact.email') }}
                                </dt>
                                <dd class="mt-2">
                                    <a href="mailto:{{ $email }}"
                                       class="break-all text-ink transition-colors hover:text-accent">{{ $email }}</a>
                                </dd>
                            </div>
                        @endif

                        @if (filled($whatsappDigits))
                            <div>
                                <dt class="text-xs font-semibold tracking-wide text-ink-subtle uppercase">
                                    {{ __('ui.contact.whatsapp') }}
                                </dt>
                                <dd class="mt-2">
                                    <a href="https://wa.me/{{ $whatsappDigits }}"
                                       class="text-ink transition-colors hover:text-accent">{{ $whatsapp }}</a>
                                </dd>
                            </div>
                        @endif
                    </dl>
                </div>
            @endif
        </div>
    </section>
@endsection
