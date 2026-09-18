<?php

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\QueryException;

it('allows exactly one cover per product', function () {
    $product = Product::factory()->create();

    ProductImage::create(['product_id' => $product->id, 'path' => 'a.jpg', 'is_cover' => true]);

    expect(fn () => ProductImage::create([
        'product_id' => $product->id, 'path' => 'b.jpg', 'is_cover' => true,
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
