@php
    $companyName = \App\Models\Setting::get('company_name', 'Medquest Mitra Global');
    $otherLocale = $locale === 'id' ? 'en' : 'id';
    $navLinks = \App\Support\Navigation::links($locale);
@endphp

<header class="sticky top-0 z-40 border-b border-slate-200 bg-white">
    {{--
        The mobile toggle is a checkbox rather than a script: the header is on
        every page and the menu must open with JS disabled. `peer` drives the
        panel, so no JavaScript is involved in the open/close state.
    --}}
    <input type="checkbox" id="nav-toggle" class="peer sr-only" aria-label="{{ __('ui.nav.toggle') }}">

    <div class="mx-auto grid max-w-7xl grid-cols-[1fr_auto] items-center gap-4 px-4 py-4 md:grid-cols-3">
        {{-- Logo (left). No `logo` setting exists: this is a text wordmark. --}}
        <a href="{{ route("{$locale}.home") }}" class="text-lg font-semibold tracking-tight text-slate-900">
            {{ $companyName }}
        </a>

        {{-- Menu (centered) --}}
        <nav class="hidden justify-center gap-6 text-sm font-medium text-slate-700 md:flex" aria-label="Primary">
            @foreach ($navLinks as $link)
                <a href="{{ route($link['route']) }}" class="hover:text-slate-900">{{ __($link['label']) }}</a>
            @endforeach
        </nav>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ \App\Support\LocaleUrls::switchTo(request(), $otherLocale) }}"
               rel="alternate"
               hreflang="{{ $otherLocale }}"
               class="text-sm font-medium text-slate-600 hover:text-slate-900">
                {{ $locale === 'id' ? 'EN' : 'ID' }}
            </a>

            {{-- CTA (right) --}}
            <a href="{{ route("{$locale}.contact") }}"
               class="hidden rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 md:inline-block">
                {{ __('ui.nav.cta') }}
            </a>

            <label for="nav-toggle" class="cursor-pointer md:hidden" aria-hidden="true">
                <span class="sr-only">{{ __('ui.nav.toggle') }}</span>
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/>
                </svg>
            </label>
        </div>
    </div>

    {{-- Collapsed menu panel, below `md` only. The outer wrapper carries
         `md:hidden`, so the desktop layout is decided by one plain rule and
         the panel's own open/close state cannot leak past the breakpoint. --}}
    <div class="hidden peer-checked:block md:hidden">
        <nav class="border-t border-slate-200 px-4 py-3" aria-label="Primary mobile">
            <ul class="flex flex-col gap-3 text-sm font-medium text-slate-700">
                @foreach ($navLinks as $link)
                    <li><a href="{{ route($link['route']) }}" class="hover:text-slate-900">{{ __($link['label']) }}</a></li>
                @endforeach
                <li>
                    <a href="{{ route("{$locale}.contact") }}" class="font-semibold text-slate-900">{{ __('ui.nav.cta') }}</a>
                </li>
            </ul>
        </nav>
    </div>
</header>
