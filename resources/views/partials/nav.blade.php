@php
    $otherLocale = $locale === 'id' ? 'en' : 'id';
    $navLinks = \App\Support\Navigation::links($locale);

    /*
    | Overlay mode: the header sits on the hero photograph rather than on the
    | canvas, so its type has to invert. The home page is the only route whose
    | first screen is a photograph, and it is the only one that pushes `overlay`.
    | See the Header block in `resources/css/app.css` for what the two data
    | attributes mean.
    |
    | `$__env->hasSection()` rather than the `View` facade: `$__env` is the
    | compiler's own factory instance and is available in every Blade view,
    | where the facade would depend on the alias being registered. It is what
    | makes this safe to include from the layout on every page: the section is
    | only defined where a page pushed it.
    */
    $overlay = $__env->hasSection('header-overlay');
@endphp

{{-- `data-scrolled` ships as `false`, which is the state a page loaded at the
     top is already in, so the first paint is correct. `app.js` owns the
     attribute from there. `data-overlay` is the page's own choice and never
     changes. --}}
<header data-site-header
        data-overlay="{{ $overlay ? 'true' : 'false' }}"
        data-scrolled="false"
        class="site-header sticky top-0 z-40 border-b">

    {{--
        The mobile toggle is a checkbox rather than a script: the header is on
        every page and the menu must open with JS disabled. `peer` drives the
        panel, so no JavaScript is involved in the open/close state.
    --}}
    <input type="checkbox" id="nav-toggle" class="peer sr-only">

    <div class="mx-auto grid max-w-shell grid-cols-[1fr_auto] items-center gap-4 px-4 py-3.5 md:grid-cols-3">
        {{-- Logo (left), or the company name when no logo is configured. --}}
        <a href="{{ route("{$locale}.home") }}" class="site-header-logo justify-self-start">
            @include('partials.logo', ['height' => 'h-16'])
        </a>

        {{-- Menu (centered). The colour comes from `--header-fg`, which the
             stylesheet decides, so the overlay variant inverts without this
             element naming a colour of its own. --}}
        <nav class="hidden justify-center gap-7 text-sm font-medium md:flex"
             style="color: var(--header-fg)"
             aria-label="{{ __('ui.nav.primary') }}">
            @foreach ($navLinks as $link)
                <a href="{{ route($link['route']) }}"
                   @class([
                       'transition-colors hover:text-(--header-fg-strong)',
                       'text-(--header-fg-strong)' => request()->routeIs($link['route']),
                   ])>{{ __($link['label']) }}</a>
            @endforeach
        </nav>

        <div class="flex items-center justify-end gap-2">
            <a href="{{ \App\Support\LocaleUrls::switchTo(request(), $otherLocale) }}"
               rel="alternate"
               hreflang="{{ $otherLocale }}"
               style="color: var(--header-fg)"
               class="rounded-control px-2 py-1.5 text-sm font-medium transition-colors hover:text-(--header-fg-strong)">
                {{ $locale === 'id' ? 'EN' : 'ID' }}
            </a>

            {{-- CTA (right) --}}
            <a href="{{ route("{$locale}.contact") }}"
               class="hidden rounded-control bg-accent px-4 py-2 text-sm font-semibold text-accent-ink transition-colors hover:bg-accent-hover md:inline-block">
                {{ __('ui.nav.cta') }}
            </a>

            {{-- Hamburger. Three rules that become an X via `peer-checked`, so
                 the control's state is legible without a script. --}}
            <label for="nav-toggle"
                   style="color: var(--header-fg-strong)"
                   class="flex h-10 w-10 cursor-pointer items-center justify-center rounded-control md:hidden">
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
         the panel's own open/close state cannot leak past the breakpoint.

         It carries its own opaque background because the header above it may be
         transparent: a menu that opened over the hero with no surface of its
         own would be unreadable. --}}
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

{{-- Without JavaScript the attribute can never change, so the transparent
     state would be permanent: white type over the hero, and invisible type on
     every other page once the hero was scrolled past. The header is pinned
     solid instead, which is the safe half of the behaviour rather than a
     degraded one. --}}
<noscript>
    <style>
        .site-header[data-scrolled='false'] {
            background-color: var(--surface);
            border-color: var(--line);
        }

        .site-header[data-overlay='true'][data-scrolled='false'] {
            --header-fg: var(--ink-muted);
            --header-fg-strong: var(--ink);
        }

        .site-header[data-overlay='true'][data-scrolled='false'] .site-header-logo {
            background-color: transparent;
            padding: 0;
        }
    </style>
</noscript>
