@extends('layouts.app')

@section('title', __('ui.catalog.heading').' - '.\App\Models\Setting::get('default_meta_title', 'Medquest Mitra Global'))

@section('content')
    {{-- Catalog index: a server-rendered grid with a plain GET form for
         filtering. No JavaScript is involved, so the page works with JS
         disabled and stays guest-cacheable, which is the whole reason the
         public site avoids Livewire.

         Filter state lives in the query string only. Every control renders its
         current value from `$activeCategory` / `$activePrincipal` / `$term`, so
         submitting one filter never silently drops another. --}}
    <section class="mx-auto max-w-shell px-4 py-12 lg:py-16">
        <header class="max-w-2xl">
            <h1 class="text-3xl font-semibold tracking-tight text-ink lg:text-4xl">
                {{ __('ui.catalog.heading') }}
            </h1>
            <p class="mt-3 text-sm leading-relaxed text-ink-muted">
                {{ __('ui.catalog.intro') }}
            </p>
        </header>

        {{-- `method="get"` with no `action` submits back to the current URL, so
             the same form serves both locales without a locale branch here. --}}
        <form method="get" class="mt-8 rounded-card border border-line bg-surface p-4 sm:p-5">
            <div class="grid gap-4 md:grid-cols-[1fr_1fr_1fr_auto] md:items-end">
                <div>
                    <label for="filter-category"
                           class="block text-xs font-semibold tracking-wide text-ink-subtle uppercase">
                        {{ __('ui.catalog.filter_category') }}
                    </label>
                    <select id="filter-category" name="category"
                            class="mt-2 w-full rounded-control border border-line bg-canvas px-3 py-2 text-sm text-ink">
                        <option value="">{{ __('ui.catalog.filter_all') }}</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->slug }}" @selected($activeCategory === $category->slug)>
                                {{ $category->getTranslation('name', $locale) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter-principal"
                           class="block text-xs font-semibold tracking-wide text-ink-subtle uppercase">
                        {{ __('ui.catalog.filter_principal') }}
                    </label>
                    <select id="filter-principal" name="principal"
                            class="mt-2 w-full rounded-control border border-line bg-canvas px-3 py-2 text-sm text-ink">
                        <option value="">{{ __('ui.catalog.filter_all') }}</option>
                        @foreach ($principals as $principal)
                            <option value="{{ $principal->slug }}" @selected($activePrincipal === $principal->slug)>
                                {{ $principal->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter-q"
                           class="block text-xs font-semibold tracking-wide text-ink-subtle uppercase">
                        {{ __('ui.catalog.search_label') }}
                    </label>
                    {{-- No `pattern` or `maxlength`: the server sanitises the term,
                         and an HTML constraint would reject input the server
                         handles fine. --}}
                    <input id="filter-q" type="search" name="q" value="{{ $term }}"
                           placeholder="{{ __('ui.catalog.search_placeholder') }}"
                           class="mt-2 w-full rounded-control border border-line bg-canvas px-3 py-2 text-sm text-ink placeholder:text-ink-subtle">
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit"
                            class="rounded-control bg-accent px-4 py-2 text-sm font-semibold text-accent-ink transition-colors hover:bg-accent-hover">
                        {{ __('ui.catalog.submit') }}
                    </button>
                    @if ($term !== '' || $activeCategory !== '' || $activePrincipal !== '')
                        <a href="{{ url()->current() }}"
                           class="rounded-control px-3 py-2 text-sm font-medium text-ink-muted transition-colors hover:text-ink">
                            {{ __('ui.catalog.clear') }}
                        </a>
                    @endif
                </div>
            </div>
        </form>

        @if ($products->isEmpty())
            {{-- Composed empty state, mirroring the home grid: it explains the
                 situation and offers the next step rather than stating a bare
                 fact. --}}
            <div class="mt-10 rounded-card border border-dashed border-line-strong bg-surface px-6 py-14 text-center">
                <p class="text-sm text-ink-muted">{{ __('ui.catalog.empty') }}</p>
                <a href="{{ url()->current() }}"
                   class="mt-4 inline-block rounded-control bg-accent px-5 py-2.5 text-sm font-semibold text-accent-ink transition-colors hover:bg-accent-hover">
                    {{ __('ui.catalog.clear') }}
                </a>
            </div>
        @else
            <div class="mt-10 grid grid-cols-1 gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($products as $product)
                    @include('partials.product-card', ['product' => $product])
                @endforeach
            </div>

            {{ $products->links() }}
        @endif
    </section>
@endsection
