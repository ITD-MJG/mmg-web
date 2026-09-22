@php
    $companyName = \App\Models\Setting::get('company_name', 'Medquest Mitra Global');
    $address = \App\Models\Setting::get('address');
    $email = \App\Models\Setting::get('contact_email');
    $phone = \App\Models\Setting::get('contact_phone');
    $navLinks = \App\Support\Navigation::links($locale);
@endphp

{{-- A dark band, in both schemes. The footer is the end of the page and the
     one surface a visitor reaches without having been sent there, so it is the
     one place the site can afford a solid block: it closes the page instead of
     continuing the canvas. See the Footer block in `resources/css/app.css` for
     the tokens, which are identical in light and dark.

     The colours come from `.site-footer` rather than from utilities, because
     the token swap has to reach the links and the muted copy inside it, and a
     utility per element would be the same decision written four times. --}}
<footer class="site-footer mt-24">
    {{-- Row 1: wordmark + address (left), nav links and contact (right). --}}
    <div class="mx-auto grid max-w-shell gap-10 px-4 py-16 md:grid-cols-[1.5fr_1fr_1fr]">
        <div>
            @include('partials.logo', ['height' => 'h-20'])
            @if (filled($address))
                <address class="site-footer-muted mt-4 max-w-xs text-base leading-relaxed not-italic">
                    {{ $address }}
                </address>
            @endif
        </div>

        <nav aria-label="{{ __('ui.footer.nav_heading') }}">
            <h2 class="text-sm font-semibold tracking-wide uppercase">
                {{ __('ui.footer.nav_heading') }}
            </h2>
            {{-- `text-base` rather than `text-sm`. The footer links are real
                 destinations, not fine print, and at the smaller size they were
                 the same weight as the copyright line below them. --}}
            <ul class="mt-5 flex flex-col gap-3 text-base">
                @foreach ($navLinks as $link)
                    <li>
                        <a href="{{ route($link['route']) }}">{{ __($link['label']) }}</a>
                    </li>
                @endforeach
            </ul>
        </nav>

        @if (filled($email) || filled($phone))
            <div>
                <h2 class="text-sm font-semibold tracking-wide uppercase">
                    {{ __('ui.footer.contact_heading') }}
                </h2>
                <ul class="mt-5 flex flex-col gap-3 text-base">
                    @if (filled($phone))
                        <li>
                            <a href="tel:{{ $phone }}">{{ $phone }}</a>
                        </li>
                    @endif
                    @if (filled($email))
                        <li>
                            <a href="mailto:{{ $email }}" class="break-all">{{ $email }}</a>
                        </li>
                    @endif
                </ul>
            </div>
        @endif
    </div>

    {{-- Row 2: copyright + tagline. The exact `" - "` separator is asserted by
         tests, so the string is built once here.

         `text-sm` and not `text-xs`: this is still copy a visitor may read, and
         the dark band gives it more contrast than the muted canvas did, so the
         smaller size it used to carry is no longer needed to keep it quiet. --}}
    <div class="site-footer-divider">
        <div class="mx-auto flex max-w-shell flex-col gap-2 px-4 py-6 text-sm sm:flex-row sm:items-center sm:justify-between">
            <p class="site-footer-muted">&copy; {{ now()->year }} - {{ $companyName }}</p>
            <p class="site-footer-muted">{{ __('ui.footer.tagline') }}</p>
        </div>
    </div>
</footer>
