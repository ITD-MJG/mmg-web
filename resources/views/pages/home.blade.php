@extends('layouts.app')

@section('title', \App\Models\Setting::get('default_meta_title', 'Medquest Mitra Global'))

@section('content')
    {{-- 1. Hero ------------------------------------------------------------
         Split composition: the message holds the left column and the
         photograph the right, so the headline never sits over a busy area of
         the image. Top padding is capped at `pt-20` so the value proposition
         and its CTA are both above the fold on a laptop. --}}
    <section class="border-b border-line">
        <div class="mx-auto grid max-w-shell items-center gap-10 px-4 pt-20 pb-16 lg:grid-cols-[1.1fr_1fr] lg:gap-16 lg:pt-24 lg:pb-24">
            <div>
                <h1 class="text-4xl font-semibold tracking-tight text-ink text-balance lg:text-5xl">
                    {{ __('ui.hero.heading') }}
                </h1>
                <p class="mt-5 max-w-xl text-lg leading-relaxed text-ink-muted">
                    {{ __('ui.hero.subtext') }}
                </p>

                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <a href="{{ route("{$locale}.contact") }}"
                       class="rounded-control bg-accent px-5 py-3 text-sm font-semibold text-accent-ink transition-colors hover:bg-accent-hover">
                        {{ __('ui.hero.cta') }}
                    </a>
                    <a href="{{ route("{$locale}.products.index") }}"
                       class="rounded-control border border-line-strong px-5 py-3 text-sm font-semibold text-ink transition-colors hover:bg-surface-muted">
                        {{ __('ui.hero.secondary_cta') }}
                    </a>
                </div>
            </div>

            {{-- The photograph is a real <img> rather than a CSS background so
                 it can carry alt text and be lazy-decoded. The placeholder
                 artwork stands in until the client supplies a photograph. --}}
            <div class="overflow-hidden rounded-card border border-line bg-surface-muted">
                <img src="{{ asset('images/hero.svg') }}"
                     alt="{{ __('ui.hero.image_alt') }}"
                     width="1600"
                     height="900"
                     fetchpriority="high"
                     class="h-full w-full object-cover">
            </div>
        </div>
    </section>

    {{-- 2. Facilities marquee -----------------------------------------------
         Breadth without weight: these are the facility types the company
         serves, and a marquee reads them as a category signal rather than a
         list to be studied. --}}
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
        <section class="border-b border-line py-10" aria-label="{{ __('ui.sections.principal') }}">
            <div class="mx-auto max-w-shell px-4">
                <p class="text-center text-xs font-semibold tracking-wide text-ink-subtle uppercase">
                    {{ __('ui.sections.principal') }}
                </p>

                <div class="mt-8" data-carousel>
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
                        <button type="button"
                                data-carousel-prev
                                hidden
                                class="shrink-0 rounded-control border border-line p-2.5 text-ink-muted transition-colors hover:border-line-strong hover:text-ink disabled:pointer-events-none disabled:opacity-40"
                                aria-label="{{ __('ui.carousel.previous') }}">
                            <svg class="h-6 w-6" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75"
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
                                            class="grid aspect-square w-full max-w-28 place-items-center sm:max-w-32 md:max-w-36 lg:max-w-40 xl:max-w-44">
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
                                class="shrink-0 rounded-control border border-line p-2.5 text-ink-muted transition-colors hover:border-line-strong hover:text-ink disabled:pointer-events-none disabled:opacity-40"
                                aria-label="{{ __('ui.carousel.next') }}">
                            <svg class="h-6 w-6" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75"
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
