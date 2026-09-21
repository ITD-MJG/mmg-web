@php
    /**
     * Project paginator.
     *
     * Replaces Laravel's shipped `pagination::tailwind` view, which renders
     * `text-gray-*`, `bg-white`, `border-gray-*`, and `rounded-md` utilities.
     * DesignSystemTest bans raw palette utilities outright, and none of those
     * would follow the token layer's dark-mode swap, so the stock view would
     * have been both a test failure and a light-mode-only control set.
     *
     * Labels come from `ui.pagination.*` rather than Laravel's own
     * `pagination.*` keys: this project ships no `lang/en/pagination.php`, so
     * the stock labels would render as the raw key on /en.
     *
     * @var \Illuminate\Pagination\LengthAwarePaginator $paginator
     */
@endphp

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('ui.pagination.label') }}"
         class="mt-12 flex flex-wrap items-center justify-between gap-4 border-t border-line pt-6">
        <p class="text-sm text-ink-subtle">
            {{ __('ui.catalog.results', ['from' => $paginator->firstItem(), 'to' => $paginator->lastItem(), 'total' => $paginator->total()]) }}
        </p>

        <div class="flex items-center gap-2">
            @if ($paginator->onFirstPage())
                <span aria-disabled="true"
                      class="inline-flex items-center rounded-control border border-line px-4 py-2 text-sm font-medium text-ink-subtle opacity-50">
                    {{ __('ui.pagination.previous') }}
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                   class="inline-flex items-center rounded-control border border-line px-4 py-2 text-sm font-medium text-ink-muted transition-colors hover:border-line-strong hover:text-ink">
                    {{ __('ui.pagination.previous') }}
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                   class="inline-flex items-center rounded-control border border-line px-4 py-2 text-sm font-medium text-ink-muted transition-colors hover:border-line-strong hover:text-ink">
                    {{ __('ui.pagination.next') }}
                </a>
            @else
                <span aria-disabled="true"
                      class="inline-flex items-center rounded-control border border-line px-4 py-2 text-sm font-medium text-ink-subtle opacity-50">
                    {{ __('ui.pagination.next') }}
                </span>
            @endif
        </div>
    </nav>
@endif
