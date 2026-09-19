@php
    $companyName = \App\Models\Setting::get('company_name', 'Medquest Mitra Global');
    $otherLocale = $locale === 'id' ? 'en' : 'id';
    $navLinks = \App\Support\Navigation::links($locale);
@endphp

<header class="sticky top-0 z-40 border-b border-line bg-surface/85 backdrop-blur-md">
    {{--
        The mobile toggle is a checkbox rather than a script: the header is on
        every page and the menu must open with JS disabled. `peer` drives the
        panel, so no JavaScript is involved in the open/close state.
    --}}
    <input type="checkbox" id="nav-toggle" class="peer sr-only">

    <div class="mx-auto grid max-w-7xl grid-cols-[1fr_auto] items-center gap-4 px-4 py-3.5 md:grid-cols-3">
        {{-- Logo (left). No `logo` setting exists: this is a text wordmark.
             The accent rule under it is the one decorative mark in the
             header, and it ties the wordmark to the brand colour. --}}
        <a href="{{ route("{$locale}.home") }}"
           class="justify-self-start text-lg font-semibold tracking-tight text-ink">
            {{ $companyName }}
        </a>

        {{-- Menu (centered) --}}
        <nav class="hidden justify-center gap-7 text-sm font-medium text-ink-muted md:flex"
             aria-label="{{ __('ui.nav.primary') }}">
            @foreach ($navLinks as $link)
                <a href="{{ route($link['route']) }}"
                   @class([
                       'transition-colors hover:text-ink',
                       'text-ink' => request()->routeIs($link['route']),
                   ])>{{ __($link['label']) }}</a>
            @endforeach
        </nav>

        <div class="flex items-center justify-end gap-2">
            <a href="{{ \App\Support\LocaleUrls::switchTo(request(), $otherLocale) }}"
               rel="alternate"
               hreflang="{{ $otherLocale }}"
               class="rounded-control px-2 py-1.5 text-sm font-medium text-ink-subtle transition-colors hover:text-ink">
                {{ $locale === 'id' ? 'EN' : 'ID' }}
            </a>

            {{-- CTA (right) --}}
            <a href="{{ route("{$locale}.contact") }}"
               class="hidden rounded-control bg-accent px-4 py-2 text-sm font-semibold text-accent-ink transition-colors hover:bg-accent-hover md:inline-block">
                {{ __('ui.nav.cta') }}
            </a>

            {{-- Hamburger. Two rules that become an X via `peer-checked`, so
                 the control's state is legible without a script. --}}
            <label for="nav-toggle"
                   class="flex h-10 w-10 cursor-pointer items-center justify-center rounded-control text-ink md:hidden">
                <span class="sr-only">{{ __('ui.nav.toggle') }}</span>
                <span class="relative block h-3.5 w-5" aria-hidden="true">
                    <span class="hamburger-bar absolute inset-x-0 top-0 h-px bg-current"></span>
                    <span class="hamburger-bar absolute inset-x-0 top-[7px] h-px bg-current"></span>
                    <span class="hamburger-bar absolute inset-x-0 top-[14px] h-px bg-current"></span>
                </span>
            </label>
        </div>
    </div>

    {{-- Collapsed menu panel, below `md` only. The outer wrapper carries
         `md:hidden`, so the desktop layout is decided by one plain rule and
         the panel's own open/close state cannot leak past the breakpoint. --}}
    <div class="hidden border-t border-line bg-surface peer-checked:block md:hidden">
        <nav class="px-4 py-3" aria-label="{{ __('ui.nav.primary') }}">
            <ul class="flex flex-col text-sm font-medium text-ink-muted">
                @foreach ($navLinks as $link)
                    <li>
                        <a href="{{ route($link['route']) }}"
                           class="block py-2.5 transition-colors hover:text-ink">{{ __($link['label']) }}</a>
                    </li>
                @endforeach
                <li class="pt-2">
                    <a href="{{ route("{$locale}.contact") }}"
                       class="block rounded-control bg-accent px-4 py-2.5 text-center font-semibold text-accent-ink">
                        {{ __('ui.nav.cta') }}
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</header>
