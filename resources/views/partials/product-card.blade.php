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
    $imageUrl = $cover ? \Illuminate\Support\Facades\Storage::disk('public')->url($cover->path) : null;
    $alt = $cover?->getTranslation('alt', $locale) ?: $product->getTranslation('name', $locale);

    // 40–60 word excerpt. `Str::words` truncates at a word boundary and only
    // appends the ellipsis when it actually cut something.
    $excerpt = \Illuminate\Support\Str::words(
        trim(strip_tags($product->getTranslation('short_description', $locale) ?? '')),
        55,
        '…',
    );

    $url = route("{$locale}.products.show", $product);
@endphp

<article class="flex h-full flex-col overflow-hidden rounded-lg border border-slate-200 bg-white transition hover:shadow-md">
    <a href="{{ $url }}" class="block aspect-4/3 overflow-hidden bg-slate-100">
        @if ($imageUrl)
            <img src="{{ $imageUrl }}"
                 alt="{{ $alt }}"
                 loading="lazy"
                 class="h-full w-full object-cover">
        @else
            {{-- Neutral placeholder: a product without images must still
                 occupy the same box so the grid does not collapse. --}}
            <span class="flex h-full w-full items-center justify-center text-slate-300" aria-hidden="true">
                <svg class="h-12 w-12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                    <circle cx="8.5" cy="8.5" r="1.5"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 15-5-5L5 21"/>
                </svg>
            </span>
        @endif
    </a>

    <div class="flex flex-1 flex-col p-4">
        <h3 class="text-base font-semibold text-slate-900">
            <a href="{{ $url }}" class="hover:underline">{{ $product->getTranslation('name', $locale) }}</a>
        </h3>

        @if ($excerpt !== '')
            <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $excerpt }}</p>
        @endif

        {{-- Deliberately no price: the company distributes and does not
             publish pricing. See the `never renders a price` test. --}}
        <a href="{{ $url }}" class="mt-4 text-sm font-semibold text-slate-900 hover:underline">
            {{ __('ui.products.detail') }}
        </a>
    </div>
</article>
