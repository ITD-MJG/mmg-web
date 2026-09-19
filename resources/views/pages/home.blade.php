@extends('layouts.app')

@section('title', \App\Models\Setting::get('default_meta_title', 'Medquest Mitra Global'))

@section('content')
    {{-- 1. Hero ------------------------------------------------------------
         Split composition: the message holds the left column and the
         photograph the right, so the headline never sits over a busy area of
         the image. Top padding is capped at `pt-20` so the value proposition
         and its CTA are both above the fold on a laptop. --}}
    <section class="border-b border-line">
        <div class="mx-auto grid max-w-7xl items-center gap-10 px-4 pt-20 pb-16 lg:grid-cols-[1.1fr_1fr] lg:gap-16 lg:pt-24 lg:pb-24">
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

    {{-- 3. Principal marquee ------------------------------------------------
         The wireframe specifies a marquee here, and the wireframe is
         authoritative: this is the second and last marquee on the page. Both
         are kept because the client's design calls for them explicitly, and
         each carries a different signal (facility types served, then the
         manufacturers distributed for). --}}
    @if ($principals->isNotEmpty())
        <section class="border-b border-line py-10" aria-label="{{ __('ui.sections.principal') }}">
            <p class="text-center text-xs font-semibold tracking-wide text-ink-subtle uppercase">
                {{ __('ui.sections.principal') }}
            </p>

            <div class="marquee mt-6 overflow-hidden">
                <div class="flex w-max animate-marquee items-center gap-14 pr-14">
                    @foreach ([1, 2] as $copy)
                        <ul class="flex items-center gap-14" @if ($copy === 2) aria-hidden="true" @endif>
                            @foreach ($principals as $principal)
                                <li class="whitespace-nowrap text-lg font-medium tracking-tight text-ink-muted">
                                    {{ $principal->name }}
                                </li>
                            @endforeach
                        </ul>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- 4. Products: grid 3x2 + full-width CTA ----------------------------- --}}
    <section class="border-t border-line bg-surface py-16 lg:py-20" aria-labelledby="products-heading">
        <div class="mx-auto max-w-7xl px-4">
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
        <div class="mx-auto max-w-7xl px-4">
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
