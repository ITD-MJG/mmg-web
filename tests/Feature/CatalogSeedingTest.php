<?php

use App\Models\Principal;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Tag;
use Database\Seeders\ArctikoProductSeeder;
use Database\Seeders\PrincipalSeeder;
use Database\Seeders\ProductCategorySeeder;
use Database\Seeders\TagSeeder;
use Database\Seeders\TecanProductSeeder;

/**
 * Guards the seeded Tecan and Arctiko catalogue.
 *
 * These five-per-brand sets exist to make the catalogue look like a real one:
 * images, categories, tags, and a spread of `created_at` values. The tests
 * below pin each of those, because a seeder that silently loses its images or
 * collapses its dates degrades the public pages without failing anything.
 */
beforeEach(function () {
    $this->seed(PrincipalSeeder::class);
    $this->seed(ProductCategorySeeder::class);
    $this->seed(TagSeeder::class);
});

it('seeds five published products for each brand', function () {
    $this->seed(TecanProductSeeder::class);
    $this->seed(ArctikoProductSeeder::class);

    foreach (['tecan', 'arctiko'] as $slug) {
        $principal = Principal::where('slug', $slug)->firstOrFail();

        expect($principal->products()->count())->toBe(5)
            ->and($principal->products()->where('is_published', true)->count())->toBe(5);
    }

    expect(Product::count())->toBe(10);
});

it('gives every seeded product a cover image that exists on disk', function () {
    $this->seed(TecanProductSeeder::class);
    $this->seed(ArctikoProductSeeder::class);

    foreach (Product::with('images')->get() as $product) {
        $cover = $product->coverImage();

        // Seeded images are committed public assets, so the stored path is
        // relative to the public root and `public_path()` is what the browser
        // will actually request.
        expect($cover)->not->toBeNull("{$product->slug} has no cover image")
            ->and($cover->is_cover)->toBeTrue()
            ->and(public_path($cover->path))->toBeFile("{$product->slug} points at a missing file");
    }
});

it('carries the sales register product code through as the sku', function () {
    $this->seed(TecanProductSeeder::class);

    // The sales system's `product_code` is the traceability link back to the
    // register the row was entered in, so it must survive the import verbatim.
    $product = Product::where('slug', 'tecan-infinite-200-pro')->firstOrFail();

    expect($product->sku)->toBe('MMG-PRO-000042');
});

it('attaches tags and a category to every seeded product', function () {
    $this->seed(TecanProductSeeder::class);
    $this->seed(ArctikoProductSeeder::class);

    foreach (Product::with('tags')->get() as $product) {
        expect($product->category)->not->toBeNull("{$product->slug} has no category")
            ->and($product->tags)->not->toBeEmpty("{$product->slug} has no tags");
    }
});

it('spreads created_at so the newest and oldest sorts differ', function () {
    $this->seed(TecanProductSeeder::class);
    $this->seed(ArctikoProductSeeder::class);

    $newest = Product::published()->orderByDesc('created_at')->pluck('slug')->all();
    $oldest = Product::published()->orderBy('created_at')->pluck('slug')->all();

    // Ten distinct dates, so the two orderings must be exact reverses. A set
    // written in one pass would share a timestamp and make these identical,
    // which is the whole reason the dates are set explicitly.
    expect($newest)->toHaveCount(10)
        ->and(array_unique(Product::pluck('created_at')->all()))->toHaveCount(10)
        ->and($newest)->toBe(array_reverse($oldest));
});

it('replaces rather than duplicates images and tags on a re-run', function () {
    $this->seed(TecanProductSeeder::class);

    $images = ProductImage::count();
    $tags = DB::table('product_tags')->count();

    $this->seed(TecanProductSeeder::class);

    expect(Product::count())->toBe(5)
        ->and(ProductImage::count())->toBe($images)
        ->and(DB::table('product_tags')->count())->toBe($tags);
});

it('keeps every tag reachable from at least one product', function () {
    $this->seed(TecanProductSeeder::class);
    $this->seed(ArctikoProductSeeder::class);

    // A tag attached to nothing is a dead filter option on the catalogue.
    $orphans = Tag::whereDoesntHave('products')->pluck('slug');

    expect($orphans)->toBeEmpty();
});
