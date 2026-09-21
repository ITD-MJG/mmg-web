@extends('layouts.app')

@section('title', \App\Models\Setting::get('default_meta_title', 'Medquest Mitra Global'))

@section('content')
    {{-- The fold group: hero, facilities strip, and principal register share
         one box exactly as tall as the viewport below the header, so the
         three are what the first screen shows and everything after them is
         what scrolling reveals. `.fold` owns the height and the division; see
         the comment on it in `resources/css/app.css`. The closing tag is at
         the end of the principal section. --}}
    <div class="fold">

    {{-- 1. Hero ------------------------------------------------------------
         Single-column composition: the photograph runs full-bleed behind the
         message, under a 50% black scrim so the white type keeps its contrast
         over the bright areas of the frame.

         The band's share of the fold is the grid's `3fr auto 1.5fr` rows in
         `resources/css/app.css`, not a class here — the ratio lives in one
         place, because the rows and the items have to agree and two copies
         would not.

         A grid row never renders smaller than its content, which is
         load-bearing on a short window: the copy is taller than the row the
         ratio gives it, and the honest answer is for the fold group to grow
         and the page to scroll rather than for the headline to be clipped.
         Where there is room, the ratio decides the split; where there is not,
         the copy does.

         The type and the padding step down below `sm` because a phone has
         roughly half the height for the same four-line headline, and the
         desktop scale is what would push the register off a phone's fold. --}}
    <section class="relative isolate flex items-center border-b border-line bg-ink">
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

             `sizes` is `100vw` because the image is now the full viewport
             width at every breakpoint, so the browser can pick between the
             800 and the 1600 without guessing.

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

        <div class="absolute inset-0 -z-10 bg-black/50" aria-hidden="true"></div>

        <div class="mx-auto w-full max-w-shell px-4 py-8 sm:py-12">
            <h1 class="max-w-3xl text-3xl font-semibold tracking-tight text-white text-balance sm:text-4xl lg:text-5xl">
                {{ __('ui.hero.heading') }}
            </h1>
            <p class="mt-4 max-w-xl text-sm leading-relaxed text-white/85 sm:mt-5 sm:text-lg">
                {{ __('ui.hero.subtext') }}
            </p>

            <div class="mt-6 flex flex-wrap items-center gap-3 sm:mt-8">
                {{-- `px-4` below `sm` rather than `px-5`: the pair is 295px wide
                     at the desktop padding and the content box is 287px on a
                     375px phone, so the second button wrapped onto its own row.
                     That row cost 56px of the hero's height, which is the
                     difference between the principal register sitting on the
                     fold and falling under it. --}}
                <a href="{{ route("{$locale}.contact") }}"
                   class="rounded-control bg-accent px-4 py-3 text-sm font-semibold text-accent-ink transition-colors hover:bg-accent-hover sm:px-5">
                    {{ __('ui.hero.cta') }}
                </a>
                <a href="{{ route("{$locale}.products.index") }}"
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

         A fixed-height band in the fold group: it is one line of small type,
         so it takes the height it needs and the two big bands divide the
         rest. `shrink-0` keeps it from being squeezed to nothing when the
         viewport is short. --}}
    @if (filled($facilities))
        <section class="border-b border-line bg-surface py-5" aria-label="{{ __('ui.sections.facilities') }}">
            <div class="marquee overflow-hidden">
                <div class="flex w-max animate-marquee items-center gap-10 pr-10">
                    @foreach ([1, 2] as $copy)
                        <ul class="flex items-center gap-10" @if ($copy === 2) aria-hidden="true" @endif>
                            @foreach ($facilities as $facility)
                                <li class="whitespace-nowrap text-sm font-medium tracking-wide text-ink-subtle">
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

         The frame is not a card: no border, and no fill in light mode, so in
         light mode the marks sit directly on the canvas exactly as they did
         before. A white plate is applied in dark mode only, where dark artwork
         on a dark surface would otherwise be unreadable. That plate used to be
         unconditional, which was invisible when the plate hugged the logo's own
         bounding box; the frame is larger than the mark now, so an
         unconditional white plate would read as a box. A bordered card was
         tried and rejected here for the same reason: it turned the register
         into a grid of boxes that competed with the logos for attention.

         The column count is a responsive grid class rather than a number the
         server decided. How many marks fit on a row depends on the viewport,
         which the server cannot know, so the count has to come from the
         browser. `app.js` reads the resolved `grid-template-columns` and pages
         by whatever it finds, which also means the two can never drift: there
         is no second copy of the breakpoint list to keep in step.

         The whole list is rendered and JavaScript only decides which items are
         visible. With no JavaScript every principal is shown in the same grid,
         so the section degrades to a plain logo wall rather than to a single
         page or to nothing.

         Navigation is the two arrows and nothing else. A page counter used to
         sit under the grid, but the arrows already disable at the ends, which
         says the same thing without a second element to keep in step with the
         first. Dropping it also removed the widest thing in the section's
         vertical rhythm, so the frames could grow: the caps are 112px on a
         phone up to 176px on a wide screen, against 96px to 128px before. The
         column count is unchanged, so the extra size comes out of the slack the
         row used to leave inside each track rather than out of the row's width.

         The caps were raised again, to 144px on a phone up to 208px on a wide
         screen, when the arrows were shrunk to `p-2` and a 20px icon. Two
         columns of marks on a phone were the smallest thing on the page: at
         112px the frame was narrower than the gap between two of them, so the
         register read as a row of specks. The smaller controls hand the row
         back 20px, and the rest comes out of the same slack as before.

         The register then slides: one page travels across, and the next holds
         long enough to be read. `app.js` clips the strip to a single row and
         moves it by a transform, which is why the track sits inside a viewport
         below. The viewport is the clipping box and its width is the width of
         one page, so the script and the stylesheet agree on where a page ends
         without either one holding a copy of the other's numbers.

         The movement pauses while the pointer is over the strip, or while a
         keyboard visitor has focus inside it, and it is not started at all when
         the visitor has asked for reduced motion. Nothing moves under a hand
         that is already there, and nothing moves for someone who asked it not
         to. With no JavaScript the strip is the plain wall it always was. --}}
    @if ($principals->isNotEmpty())
        {{-- Named by the heading rather than by `aria-label`. The visible title
             used to be a `<p>`, which is a label but not a heading, so the
             section had to carry a duplicate `aria-label` to name itself. Now
             that the title is an `<h2>`, `aria-labelledby` points the region
             at it: one string in one place, and the register appears in the
             page's heading outline where a visitor navigating by heading would
             look for it. --}}
        {{-- The band's share of the fold is the grid's `3fr auto 1.5fr` rows in
             `resources/css/app.css`, not a class here: the two big bands are
             grid items, and the ratio has to be decided in one place or the
             rows and the items disagree.

             Vertical padding below `md` only. On a phone the title and the row
             of marks are the whole band and they were sitting flush against the
             strip above and the section below, so the band had no breathing
             room of its own. From `md` up the grid row is tall enough that the
             centring already does that job, and padding there would only add
             height the fold has to absorb. --}}
        <section class="flex flex-col justify-center border-b border-line py-8 md:py-0" aria-labelledby="principal-heading">
            <div class="mx-auto max-w-shell px-4">
                {{-- Larger and heavier than the other section headings on
                     purpose. Products and Contact are `text-2xl`/`text-3xl` at
                     `font-semibold`; this is a step above both, because the
                     register is the section whose content is least
                     self-explanatory — a row of logos with no sentence saying
                     what they are. It was a 12px uppercase caption before that.

                     A step below that on a phone. The mobile band is a third of
                     the fold rather than the half it was, so a 30px title took a
                     disproportionate share of it; `text-2xl` there keeps the
                     hierarchy over Products — which is also `text-2xl` below
                     `lg` — through weight alone, and the size returns at `md`.
                     The padding and the smaller title are the same trade: give
                     the band room without spending the fold on it. --}}
                <h2 id="principal-heading" class="text-center text-2xl font-bold tracking-tight text-ink md:text-3xl lg:text-4xl">
                    {{ __('ui.sections.principal') }}
                </h2>

                <div class="mt-6" data-carousel>
                    <div class="flex items-center gap-2 sm:gap-4">
                        {{-- The arrows are real buttons, not labels bound to an
                             input. Paging now needs to know the current position
                             to know where "next" is, and that state lives in
                             `app.js`. `disabled` on the ends is the honest state
                             for a control that cannot move. A `disabled` arrow
                             is the only position indicator in the section, so
                             it has to read as state and not as decoration.

                             They are `hidden` until JavaScript has run, so a
                             no-JavaScript visitor is not shown two dead
                             controls. `app.js` reveals them once it has paged
                             the list. --}}
                        {{-- `max-md:hidden`: on a phone the strip pages on its
                             own, and two controls either side of a two-column
                             row were taking 96px of the width the marks need —
                             a third of the row, for navigation the visitor can
                             also get by waiting. From `md` up there is room for
                             both the arrows and the register, so they return.

                             The class and the `hidden` attribute do not
                             conflict. Below `md` the class is `display: none`
                             and wins on its own; at `md` and above the class
                             stops applying and `app.js` is the only thing
                             deciding, which is what `reflect()` already does.
                             A `md:block` would have been wrong here: an author
                             `display` utility outranks the `[hidden]` rule, so
                             it would have pinned both arrows open at every
                             width and made `reflect()` unable to disable
                             them. --}}
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

                                             Once the script takes over, the single-row layout
                                             is written on the track inline: the grid class
                                             still decides how many columns there are, and
                                             `app.js` lays them out in one row and hands each
                                             one the width the class would have given it. --}}
                                        <li data-carousel-item
                                            class="grid aspect-square w-full max-w-36 place-items-center sm:max-w-40 md:max-w-44 lg:max-w-48 xl:max-w-52">
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

    </div>{{-- /.fold --}}

    {{-- 4. Products: grid 3x2 + full-width CTA ----------------------------- --}}
    <section class="border-t border-line bg-surface py-16 lg:py-20" aria-labelledby="products-heading">
        <div class="mx-auto max-w-shell px-4">
            <div class="flex flex-wrap items-baseline justify-between gap-4">
                <h2 id="products-heading" class="text-2xl font-semibold tracking-tight text-ink lg:text-3xl">
                    {{ __('ui.sections.products') }}
                </h2>
                <a href="{{ route("{$locale}.products.index") }}"
                   class="text-sm font-medium text-accent transition-colors hover:text-accent-hover">
                    {{ __('ui.products.cta') }}
                </a>
            </div>

            @if ($products->isEmpty())
                {{-- Composed empty state rather than a bare sentence: it
                     explains the situation and offers the next step. --}}
                <div class="mt-8 rounded-card border border-dashed border-line-strong bg-canvas px-6 py-14 text-center">
                    <p class="text-sm text-ink-muted">{{ __('ui.products.empty') }}</p>
                    <a href="{{ route("{$locale}.contact") }}"
                       class="mt-4 inline-block rounded-control bg-accent px-5 py-2.5 text-sm font-semibold text-accent-ink transition-colors hover:bg-accent-hover">
                        {{ __('ui.hero.cta') }}
                    </a>
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
    @endphp

    <section class="border-t border-line py-16 lg:py-20" aria-labelledby="contact-heading">
        <div class="mx-auto max-w-shell px-4">
            <h2 id="contact-heading" class="text-2xl font-semibold tracking-tight text-ink lg:text-3xl">
                {{ __('ui.sections.contact') }}
            </h2>

            @if (! $hasDetails)
                {{-- An unconfigured install must not render an empty two-column
                     shell. Say so plainly instead. --}}
                <p class="mt-6 text-sm text-ink-muted">{{ __('ui.contact.unconfigured') }}</p>
            @else
                <div class="mt-8 grid gap-8 md:grid-cols-2 md:gap-12">
                    {{-- Map is guarded: an unconfigured install renders the
                         details column alone rather than a broken iframe.
                         Plain embed URL, no API key and no JS SDK. --}}
                    @if (filled($address))
                        <div class="overflow-hidden rounded-card border border-line bg-surface-muted">
                            <iframe
                                title="{{ __('ui.contact.map_title') }}"
                                src="https://www.google.com/maps?q={{ urlencode($address) }}&amp;output=embed"
                                loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"
                                class="h-80 w-full border-0"></iframe>
                        </div>
                    @endif

                    <dl class="grid gap-6 self-center text-sm sm:grid-cols-2">
                        @if (filled($address))
                            <div class="sm:col-span-2">
                                <dt class="text-xs font-semibold tracking-wide text-ink-subtle uppercase">
                                    {{ __('ui.contact.address') }}
                                </dt>
                                <dd class="mt-2 leading-relaxed text-ink-muted">{{ $address }}</dd>
                            </div>
                        @endif

                        @if (filled($phone))
                            <div>
                                <dt class="text-xs font-semibold tracking-wide text-ink-subtle uppercase">
                                    {{ __('ui.contact.phone') }}
                                </dt>
                                <dd class="mt-2">
                                    <a href="tel:{{ $phone }}"
                                       class="text-ink-muted transition-colors hover:text-ink">{{ $phone }}</a>
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
                                       class="break-all text-ink-muted transition-colors hover:text-ink">{{ $email }}</a>
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
                                       class="text-ink-muted transition-colors hover:text-ink">{{ $whatsapp }}</a>
                                </dd>
                            </div>
                        @endif
                    </dl>
                </div>
            @endif
        </div>
    </section>
@endsection
