@php
    $companyName = \App\Models\Setting::get('company_name', 'Medquest Mitra Global');
    $address = \App\Models\Setting::get('address');
    $email = \App\Models\Setting::get('contact_email');
    $phone = \App\Models\Setting::get('contact_phone');
    $navLinks = \App\Support\Navigation::links($locale);
@endphp

<footer class="mt-24 border-t border-line bg-surface-muted">
    {{-- Row 1: wordmark + address (left), nav links and contact (right). --}}
    <div class="mx-auto grid max-w-shell gap-10 px-4 py-14 md:grid-cols-[1.5fr_1fr_1fr]">
        <div>
            @include('partials.logo', ['height' => 'h-20'])
            @if (filled($address))
                <address class="mt-3 max-w-xs text-sm not-italic leading-relaxed text-ink-muted">
                    {{ $address }}
                </address>
            @endif
        </div>

        <nav aria-label="{{ __('ui.footer.nav_heading') }}">
            <h2 class="text-xs font-semibold tracking-wide text-ink-subtle uppercase">
                {{ __('ui.footer.nav_heading') }}
            </h2>
            <ul class="mt-4 flex flex-col gap-2.5 text-sm text-ink-muted">
                @foreach ($navLinks as $link)
                    <li>
                        <a href="{{ route($link['route']) }}"
                           class="transition-colors hover:text-ink">{{ __($link['label']) }}</a>
                    </li>
                @endforeach
            </ul>
        </nav>

        @if (filled($email) || filled($phone))
            <div>
                <h2 class="text-xs font-semibold tracking-wide text-ink-subtle uppercase">
                    {{ __('ui.footer.contact_heading') }}
                </h2>
                <ul class="mt-4 flex flex-col gap-2.5 text-sm text-ink-muted">
                    @if (filled($phone))
                        <li>
                            <a href="tel:{{ $phone }}" class="transition-colors hover:text-ink">{{ $phone }}</a>
                        </li>
                    @endif
                    @if (filled($email))
                        <li>
                            <a href="mailto:{{ $email }}" class="break-all transition-colors hover:text-ink">{{ $email }}</a>
                        </li>
                    @endif
                </ul>
            </div>
        @endif
    </div>

    {{-- Row 2: copyright icon + year - company name. The exact `" - "`
         separator is asserted by tests, so the string is built once here. --}}
    <div class="border-t border-line">
        <div class="mx-auto flex max-w-shell flex-col gap-2 px-4 py-5 text-sm text-ink-subtle sm:flex-row sm:items-center sm:justify-between">
            <p>&copy; {{ now()->year }} - {{ $companyName }}</p>
            <p>{{ __('ui.footer.tagline') }}</p>
        </div>
    </div>
</footer>
