@php
    $companyName = \App\Models\Setting::get('company_name', 'Medquest Mitra Global');
    $address = \App\Models\Setting::get('address');
    $navLinks = [
        ['label' => __('ui.nav.products'), 'route' => "{$locale}.products.index"],
        ['label' => __('ui.nav.brands'), 'route' => "{$locale}.brands.index"],
        ['label' => __('ui.nav.about'), 'route' => "{$locale}.about"],
        ['label' => __('ui.nav.contact'), 'route' => "{$locale}.contact"],
    ];
@endphp

<footer class="mt-16 border-t border-slate-200 bg-slate-50">
    {{-- Row 1: logo + address (left), nav links (right). --}}
    <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 md:grid-cols-2">
        <div>
            <p class="text-lg font-semibold tracking-tight text-slate-900">{{ $companyName }}</p>
            @if (filled($address))
                <address class="mt-3 max-w-sm text-sm not-italic leading-relaxed text-slate-600">
                    {{ $address }}
                </address>
            @endif
        </div>

        <nav class="md:justify-self-end" aria-label="{{ __('ui.footer.nav_heading') }}">
            <h2 class="text-sm font-semibold text-slate-900">{{ __('ui.footer.nav_heading') }}</h2>
            <ul class="mt-3 flex flex-col gap-2 text-sm text-slate-600">
                @foreach ($navLinks as $link)
                    <li><a href="{{ route($link['route']) }}" class="hover:text-slate-900">{{ $link['label'] }}</a></li>
                @endforeach
            </ul>
        </nav>
    </div>

    {{-- Row 2: copyright icon + year - company name. The exact `" - "`
         separator is asserted by tests, so the string is built once here. --}}
    <div class="border-t border-slate-200">
        <p class="mx-auto max-w-7xl px-4 py-4 text-sm text-slate-600">
            &copy; {{ now()->year }} - {{ $companyName }}
        </p>
    </div>
</footer>
