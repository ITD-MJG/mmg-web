<?php

namespace Database\Seeders\Concerns;

use App\Models\Product;
use App\Models\ProductImage;

/**
 * Attach the committed product image as a product's cover.
 *
 * Shared by the per-principal product seeders, which differ only in their
 * data. The path is a committed public asset (`images/products/...`), resolved
 * with `asset()` through `ProductImage::url()` rather than through the `public`
 * disk, because `storage/app/public` is gitignored and this project deploys by
 * pushing the repository.
 */
trait SeedsProductCover
{
    /**
     * Update the existing image row in place rather than adding a second
     * cover: `uniq_cover_per_product` permits only one per product, and
     * `ProductImage`'s saving hook demotes the old cover only when a new row
     * is being written.
     *
     * @param  array{image: string, name: array{id: string, en: string}}  $data
     */
    private function syncCoverImage(Product $product, array $data): void
    {
        $existing = $product->images()->where('is_cover', true)->first()
            ?? $product->images()->first();

        $attributes = [
            'path' => $data['image'],
            'alt' => $data['name'],
            'is_cover' => true,
            'sort_order' => 0,
        ];

        if ($existing) {
            $existing->update($attributes);

            return;
        }

        ProductImage::create($attributes + ['product_id' => $product->id]);
    }
}
