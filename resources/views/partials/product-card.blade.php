@php
    /**
     * Shared product card.
     *
     * Rendered by the home grid and (from Task 11) the catalog grid. Keep it
     * generic: catalog-specific chrome belongs in the caller, not here.
     *
     * @var \App\Models\Product $product
     */
    $cover = $product->coverImage();
    $imageUrl = $cover?->url();
    $alt = $cover?->getTranslation('alt', $locale) ?: $product->getTranslation('name', $locale);

    // 40-60 word excerpt. `Str::words` truncates at a word boundary and only
    // appends the ellipsis when it actually cut something.
    $excerpt = \Illuminate\Support\Str::words(
        trim(strip_tags($product->getTranslation('short_description', $locale) ?? '')),
        55,
        '...',
    );

    $url = route("{$locale}.products.show", $product);
@endphp

{{-- Cardless: the whole tile is one link, so the hover target is the entire
     surface rather than the title alone. The border is the only container
     chrome, and it strengthens on hover instead of the tile lifting, which
     keeps the grid visually quiet. --}}
<article class="group flex h-full flex-col">
    <a href="{{ $url }}"
       class="block aspect-4/3 overflow-hidden rounded-card border border-line bg-surface-muted">
        @if ($imageUrl)
            <img src="{{ $imageUrl }}"
                 alt="{{ $alt }}"
                 loading="lazy"
                 class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.03]">
        @else
            {{-- Neutral placeholder: a product without images must still
                 occupy the same box so the grid does not collapse. --}}
            <span class="flex h-full w-full items-center justify-center text-ink-subtle" aria-hidden="true">
                <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25">
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                    <circle cx="8.5" cy="8.5" r="1.5"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 15-5-5L5 21"/>
                </svg>
            </span>
        @endif
    </a>

    <div class="flex flex-1 flex-col pt-4">
        <h3 class="text-base font-semibold text-ink">
            <a href="{{ $url }}" class="transition-colors group-hover:text-accent">
                {{ $product->getTranslation('name', $locale) }}
            </a>
        </h3>

        @if ($excerpt !== '')
            <p class="mt-2 text-sm leading-relaxed text-ink-muted">{{ $excerpt }}</p>
        @endif

        {{-- Deliberately no price: the company distributes and does not
             publish pricing. See the `never renders a price` test. --}}
        <span class="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-accent">
            {{ __('ui.products.detail') }}
            <svg class="h-3.5 w-3.5 transition-transform duration-300 group-hover:translate-x-0.5"
                 viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8h10m0 0L9 4m4 4-4 4"/>
            </svg>
        </span>
    </div>
</article>
