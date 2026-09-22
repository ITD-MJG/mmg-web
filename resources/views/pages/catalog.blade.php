@extends('layouts.app')

@section('title', __('ui.catalog.heading').' - '.\App\Models\Setting::get('default_meta_title', 'Medquest Mitra Global'))

@section('content')
    {{-- Alpine is loaded by this page only, not by the layout. It is here for
         the catalog's filter swap and nothing else on the public site uses it,
         so putting it in the shared entry would ship ~21 KB gzipped to every
         visitor to pay for one page's enhancement. `@push('scripts')` keeps it
         scoped to the pages that need it. --}}
    @push('scripts')
        @vite('resources/js/catalog.js')
    @endpush
    {{-- Catalog index: a server-rendered grid with a plain GET form for
         filtering. The form is the whole mechanism: with JavaScript off it
         submits and the server renders the next page, which is also what keeps
         the page guest-cacheable.

         `app.js` layers on an enhancement that intercepts that same submit and
         swaps the results region in place. The server stays authoritative for
         the result set, because the catalog paginates at 24 and filtering or
         sorting only the rows already on screen would hide every match on the
         next page.

         Filter state lives in the query string only. Every control renders its
         current value from `$activeCategory` / `$activePrincipal` / `$term`, so
         submitting one filter never silently drops another. --}}
    {{-- `x-data` scopes the whole section, so the form and the results region
         are in one component and can talk to each other without a global. The
         component is registered in `app.js`; with JavaScript off the attribute
         is inert and everything below is an ordinary form and grid. --}}
    <section x-data="catalogResults" class="mx-auto max-w-shell px-4 py-12 lg:py-16">
        <header class="max-w-2xl">
            <h1 class="text-3xl font-semibold tracking-tight text-ink lg:text-4xl">
                {{ __('ui.catalog.heading') }}
            </h1>
            <p class="mt-3 text-sm leading-relaxed text-ink-muted">
                {{ __('ui.catalog.intro') }}
            </p>
        </header>

        {{-- `method="get"` with no `action` submits back to the current URL, so
             the same form serves both locales without a locale branch here.

             `data-catalog-form` is the hook `app.js` reads to find the form,
             and `@submit` is the handler. The form keeps its native submit:
             the handler calls `preventDefault` only once it has committed to
             handling the request itself, so a modified click still opens a new
             tab. --}}
        <form method="get" data-catalog-form @submit="submit"
              class="mt-8 rounded-card border border-line bg-surface p-4 sm:p-5">
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
                    @if ($term !== '' || $activeCategory !== '' || $activePrincipal !== '' || $activeSort !== 'default')
                        <a href="{{ url()->current() }}"
                           class="rounded-control px-3 py-2 text-sm font-medium text-ink-muted transition-colors hover:text-ink">
                            {{ __('ui.catalog.clear') }}
                        </a>
                    @endif
                </div>
            </div>

            {{-- Sort lives in the same form as the filters, not in a second one.
                 A separate form would have to re-post every filter as a hidden
                 input and carry its own submit button, which is two places for
                 the filter state to go missing. Co-located, submitting the
                 sort cannot drop the filters and vice versa.

                 It is a plain select submitted by the form's Apply button, not
                 an `onchange` submit. The enhancement in `app.js` listens for
                 the form's submit event and swaps the results, so changing the
                 sort needs no separate handler and no inline script. --}}
            <div class="mt-4 flex flex-col gap-2 border-t border-line pt-4 sm:flex-row sm:items-center sm:justify-between">
                <label for="sort-order"
                       class="text-xs font-semibold tracking-wide text-ink-subtle uppercase">
                    {{ __('ui.catalog.sort_label') }}
                </label>
                <select id="sort-order" name="sort"
                        class="w-full rounded-control border border-line bg-canvas px-3 py-2 text-sm text-ink sm:w-auto sm:min-w-48">
                    @foreach (['default', 'newest', 'oldest', 'name_asc', 'name_desc'] as $sortKey)
                        <option value="{{ $sortKey }}" @selected($activeSort === $sortKey)>
                            {{ __('ui.catalog.sort_'.$sortKey) }}
                        </option>
                    @endforeach
                </select>
            </div>
        </form>

        {{-- One region holds the result count, the grid, and the pagination, so
             a single swap keeps all three in agreement.

             `aria-live` announces the replacement and `aria-busy` marks the
             in-flight fetch; without them a screen reader user is never told the
             grid changed. `aria-busy` is server-rendered as `false` so the
             attribute exists in the parsed HTML before any script runs;
             `x-bind` then owns it.

             `x-ref` and the delegated `@click` sit on this element rather than
             on its contents, because only the contents are replaced. Replacing
             the region itself would detach the ref, the listener, and the
             `aria-busy` binding along with it.

             The element is focusable so a swap can move focus to it, which is
             what makes the change announced. It carries no visible focus ring:
             focus arrives here programmatically and never by Tab, so a ring
             would flash on every filter change while marking no Tab stop. The
             `aria-live` announcement is the feedback, and every Tab stop
             inside keeps its own ring. --}}
        <div x-ref="results" @click="page"
             data-catalog-results aria-live="polite" aria-busy="false"
             x-bind:aria-busy="loading ? 'true' : 'false'"
             tabindex="-1">
            @if ($products->isEmpty())
                {{-- Composed empty state, mirroring the home grid: it explains the
                     situation and offers the next step rather than stating a bare
                     fact. It lives inside the region because it is the same
                     branch a swap can land on. --}}
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

                {{-- Marked so the delegated click handler can tell a pagination
                     link from a product link. Product cards are real links to
                     other pages and must navigate normally. --}}
                <div data-catalog-pagination>
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
