<?php

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

it('lets the last cover created win instead of throwing', function () {
    $product = Product::factory()->create();

    $first = ProductImage::create(['product_id' => $product->id, 'path' => 'a.jpg', 'is_cover' => true]);
    $second = ProductImage::create(['product_id' => $product->id, 'path' => 'b.jpg', 'is_cover' => true]);

    expect($first->fresh()->is_cover)->toBeFalse()
        ->and($second->fresh()->is_cover)->toBeTrue();
});

it('still rejects a second cover written outside the model', function () {
    // The generated column is the backstop for writes that bypass Eloquent.
    // This is the test that protects the invariant against raw SQL, so it
    // must not go through the model.
    $product = Product::factory()->create();

    ProductImage::create(['product_id' => $product->id, 'path' => 'a.jpg', 'is_cover' => true]);

    expect(fn () => DB::table('product_images')->insert([
        'product_id' => $product->id,
        'path' => 'b.jpg',
        'is_cover' => true,
        'sort_order' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});

it('allows many gallery images alongside one cover', function () {
    $product = Product::factory()->create();

    ProductImage::create(['product_id' => $product->id, 'path' => 'a.jpg', 'is_cover' => true]);

    foreach (range(1, 5) as $i) {
        ProductImage::create(['product_id' => $product->id, 'path' => "g{$i}.jpg", 'is_cover' => false]);
    }

    expect($product->images()->count())->toBe(6);
});

it('demotes the previous cover when a new cover is set through the model', function () {
    $product = Product::factory()->create();

    $first = ProductImage::create(['product_id' => $product->id, 'path' => 'a.jpg', 'is_cover' => true]);
    $second = ProductImage::create(['product_id' => $product->id, 'path' => 'b.jpg', 'is_cover' => false]);

    $second->update(['is_cover' => true]);

    expect($first->fresh()->is_cover)->toBeFalse()
        ->and($second->fresh()->is_cover)->toBeTrue();
});

it('falls back to the first image when no cover is flagged', function () {
    $product = Product::factory()->create();

    ProductImage::create(['product_id' => $product->id, 'path' => 'a.jpg', 'is_cover' => false]);

    expect($product->fresh()->coverImage())->not->toBeNull();
});

it('leaves exactly one cover after a sequence of cover writes', function () {
    $product = Product::factory()->create();

    $images = collect(range(1, 4))->map(fn ($i) => ProductImage::create([
        'product_id' => $product->id,
        'path' => "c{$i}.jpg",
        'is_cover' => true,
    ]));

    $covers = ProductImage::where('product_id', $product->id)->where('is_cover', true)->count();

    expect($covers)->toBe(1)
        ->and($images->last()->fresh()->is_cover)->toBeTrue();
});

it('does not demote covers belonging to another product', function () {
    $a = Product::factory()->create();
    $b = Product::factory()->create();

    $coverA = ProductImage::create(['product_id' => $a->id, 'path' => 'a.jpg', 'is_cover' => true]);
    ProductImage::create(['product_id' => $b->id, 'path' => 'b.jpg', 'is_cover' => true]);

    expect($coverA->fresh()->is_cover)->toBeTrue();
});

it('resolves a seeded image path as a public asset', function () {
    $image = ProductImage::create([
        'product_id' => Product::factory()->create()->id,
        'path' => 'images/products/tecan-spark.jpg',
        'is_cover' => true,
    ]);

    // Seeded paths are public-root-relative, so they resolve with `asset()`
    // rather than through the `public` disk.
    expect($image->url())->toBe(asset('images/products/tecan-spark.jpg'));
});

it('resolves an uploaded image path through the public disk', function () {
    $image = ProductImage::create([
        'product_id' => Product::factory()->create()->id,
        'path' => 'products/uploaded.jpg',
        'is_cover' => true,
    ]);

    expect($image->url())->toBe(Storage::disk('public')->url('products/uploaded.jpg'));
});

it('returns no url when the path is blank', function () {
    $image = new ProductImage(['path' => null]);

    expect($image->url())->toBeNull();
});
