@extends('layouts.app')

@section('title', \App\Models\Setting::get('default_meta_title', 'Medquest Mitra Global'))

@section('content')
    {{-- 1. Hero ---------------------------------------------------------- --}}
    <section class="relative isolate overflow-hidden bg-slate-900 text-white">
        {{-- Background image, applied as CSS rather than an <img> so a deploy
             that has not yet replaced the placeholder degrades to the
             gradient instead of showing a broken-image icon. The scrim keeps
             the copy readable over any photo. Replace public/images/hero.svg
             with the client's photograph before launch. --}}
        <div class="absolute inset-0 -z-10 bg-slate-800 bg-cover bg-center opacity-60"
             style="background-image: url('{{ asset('images/hero.svg') }}')"
             aria-hidden="true"></div>
        <div class="absolute inset-0 -z-10 bg-gradient-to-r from-slate-900/90 to-slate-900/40" aria-hidden="true"></div>

        <div class="mx-auto max-w-7xl px-4 py-24 md:py-32">
            <h1 class="max-w-3xl text-3xl font-bold tracking-tight md:text-5xl">
                {{ __('ui.hero.heading') }}
            </h1>
            <p class="mt-6 max-w-2xl text-lg text-slate-200">
                {{ __('ui.hero.subtext') }}
            </p>
            <a href="{{ route("{$locale}.contact") }}"
               class="mt-8 inline-block rounded-md bg-white px-6 py-3 text-sm font-semibold text-slate-900 hover:bg-slate-200">
                {{ __('ui.hero.cta') }}
            </a>
        </div>
    </section>

    {{-- 2. Facilities marquee -------------------------------------------- --}}
    @if (filled($facilities))
        <section class="border-y border-slate-200 bg-slate-50 py-6" aria-label="Facility types">
            <div class="marquee overflow-hidden">
                <div class="flex w-max animate-marquee items-center gap-12 pr-12">
                    @foreach ([1, 2] as $copy)
                        <ul class="flex items-center gap-12" @if ($copy === 2) aria-hidden="true" @endif>
                            @foreach ($facilities as $facility)
                                <li class="whitespace-nowrap text-sm font-medium tracking-wide text-slate-600">
                                    {{ $facility }}
                                </li>
                            @endforeach
                        </ul>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- 3. Principal marquee -------------------------------------------- --}}
    @if ($principals->isNotEmpty())
        <section class="py-10" aria-label="{{ __('ui.sections.principal') }}">
            <p class="text-center text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                {{ __('ui.sections.principal') }}
            </p>

            <div class="marquee mt-6 overflow-hidden">
                <div class="flex w-max animate-marquee items-center gap-16 pr-16">
                    @foreach ([1, 2] as $copy)
                        <ul class="flex items-center gap-16" @if ($copy === 2) aria-hidden="true" @endif>
                            @foreach ($principals as $principal)
                                <li class="whitespace-nowrap text-lg font-semibold text-slate-700">
                                    {{ $principal->name }}
                                </li>
                            @endforeach
                        </ul>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- 4. Products: grid 3x2 + full-width CTA -------------------------- --}}
    <section class="mx-auto max-w-7xl px-4 py-16">
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">{{ __('ui.sections.products') }}</h2>

        @if ($products->isEmpty())
            <p class="mt-6 text-slate-600">{{ __('ui.products.empty') }}</p>
        @else
            <div class="mt-8 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($products as $product)
                    @include('partials.product-card', ['product' => $product])
                @endforeach
            </div>
        @endif

        <a href="{{ route("{$locale}.products.index") }}"
           class="mt-10 block w-full rounded-md bg-slate-900 px-6 py-3 text-center text-sm font-semibold text-white hover:bg-slate-700">
            {{ __('ui.products.cta') }}
        </a>
    </section>

    {{-- 5. Contact Us: map (left) + details (right) --------------------- --}}
    @php
        $address = \App\Models\Setting::get('address');
        $phone = \App\Models\Setting::get('contact_phone');
        $email = \App\Models\Setting::get('contact_email');
        $whatsapp = \App\Models\Setting::get('whatsapp');
        // Digits only: wa.me rejects spaces, dashes, and a leading '+'.
        $whatsappDigits = $whatsapp ? preg_replace('/\D+/', '', $whatsapp) : null;
    @endphp

    <section class="border-t border-slate-200 bg-slate-50 py-16">
        <div class="mx-auto max-w-7xl px-4">
            <h2 class="text-2xl font-bold tracking-tight text-slate-900">{{ __('ui.sections.contact') }}</h2>

            <div class="mt-8 grid gap-8 md:grid-cols-2">
                {{-- Map is guarded: an unconfigured install renders the details
                     column alone rather than a broken iframe. Plain embed URL,
                     no API key and no JS SDK. --}}
                @if (filled($address))
                    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
                        <iframe
                            title="{{ __('ui.contact.map_title') }}"
                            src="https://www.google.com/maps?q={{ urlencode($address) }}&amp;output=embed"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            class="h-80 w-full border-0"></iframe>
                    </div>
                @endif

                <dl class="space-y-6 text-sm">
                    @if (filled($address))
                        <div>
                            <dt class="font-semibold text-slate-900">{{ __('ui.contact.address') }}</dt>
                            <dd class="mt-1 text-slate-600">{{ $address }}</dd>
                        </div>
                    @endif

                    @if (filled($phone))
                        <div>
                            <dt class="font-semibold text-slate-900">{{ __('ui.contact.phone') }}</dt>
                            <dd class="mt-1">
                                <a href="tel:{{ $phone }}" class="text-slate-600 hover:text-slate-900">{{ $phone }}</a>
                            </dd>
                        </div>
                    @endif

                    @if (filled($email))
                        <div>
                            <dt class="font-semibold text-slate-900">{{ __('ui.contact.email') }}</dt>
                            <dd class="mt-1">
                                <a href="mailto:{{ $email }}" class="text-slate-600 hover:text-slate-900">{{ $email }}</a>
                            </dd>
                        </div>
                    @endif

                    @if (filled($whatsappDigits))
                        <div>
                            <dt class="font-semibold text-slate-900">{{ __('ui.contact.whatsapp') }}</dt>
                            <dd class="mt-1">
                                <a href="https://wa.me/{{ $whatsappDigits }}"
                                   class="text-slate-600 hover:text-slate-900">{{ $whatsapp }}</a>
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>
    </section>
@endsection
