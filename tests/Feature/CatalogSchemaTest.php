<?php

use App\Models\Principal;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tag;
use Illuminate\Database\UniqueConstraintViolationException;

it('stores translatable fields as json and resolves per locale', function () {
    $category = ProductCategory::factory()->create([
        'name' => ['id' => 'Alat Kesehatan', 'en' => 'Medical Devices'],
    ]);

    app()->setLocale('id');
    expect($category->fresh()->name)->toBe('Alat Kesehatan');

    app()->setLocale('en');
    expect($category->fresh()->name)->toBe('Medical Devices');
});

it('relates products to a category and principal', function () {
    $product = Product::factory()
        ->for(ProductCategory::factory(), 'category')
        ->for(Principal::factory())
        ->create();

    expect($product->category)->toBeInstanceOf(ProductCategory::class)
        ->and($product->principal)->toBeInstanceOf(Principal::class);
});

it('returns only published products', function () {
    Product::factory()->create(['is_published' => true]);
    Product::factory()->create(['is_published' => false]);

    expect(Product::published()->count())->toBe(1);
});

it('scopes published categories, principals, and pages', function () {
    ProductCategory::factory()->create(['is_published' => true]);
    ProductCategory::factory()->create(['is_published' => false]);
    Principal::factory()->create(['is_published' => true]);
    Principal::factory()->create(['is_published' => false]);

    expect(ProductCategory::published()->count())->toBe(1)
        ->and(Principal::published()->count())->toBe(1);
});

it('stores specs and certifications as arrays', function () {
    $product = Product::factory()->create([
        'specs' => ['Ukuran' => '10x15cm', 'Kemasan' => '50 pcs/box'],
        'certifications' => [[
            'type' => 'izin_edar',
            'number' => 'AKL 12345678901',
            'issuer' => 'Kemenkes RI',
        ]],
    ]);

    $fresh = $product->fresh();

    expect($fresh->specs)->toBe(['Ukuran' => '10x15cm', 'Kemasan' => '50 pcs/box'])
        ->and($fresh->certifications)->toBeArray()
        ->and($fresh->certifications[0]['number'])->toBe('AKL 12345678901');
});

it('accepts a null certifications value', function () {
    $product = Product::factory()->create(['certifications' => null]);

    expect($product->fresh()->certifications)->toBeNull();
});

it('falls back to the indonesian translation when a locale is missing', function () {
    $category = ProductCategory::factory()->create([
        'name' => ['id' => 'Hanya Indonesia'],
    ]);

    app()->setLocale('en');

    expect($category->fresh()->name)->toBe('Hanya Indonesia');
});

it('relates products to tags through the product_tags pivot', function () {
    $product = Product::factory()->create();
    $tags = Tag::factory()->count(3)->create();

    $product->tags()->attach($tags);

    expect($product->fresh()->tags)->toHaveCount(3)
        ->and($product->tags->first())->toBeInstanceOf(Tag::class);
});

it('stores translatable tag names per locale', function () {
    $tag = Tag::factory()->create([
        'name' => ['id' => 'Freezer Ultra Rendah', 'en' => 'Ultra Low Freezer'],
    ]);

    app()->setLocale('id');
    expect($tag->fresh()->name)->toBe('Freezer Ultra Rendah');

    app()->setLocale('en');
    expect($tag->fresh()->name)->toBe('Ultra Low Freezer');
});

it('scopes published tags', function () {
    Tag::factory()->create(['is_published' => true]);
    Tag::factory()->create(['is_published' => false]);

    expect(Tag::published()->count())->toBe(1);
});

it('rejects attaching the same tag to a product twice', function () {
    $product = Product::factory()->create();
    $tag = Tag::factory()->create();

    $product->tags()->attach($tag);

    // The unique index on (product_id, tag_id) is what makes a double attach
    // fail loudly instead of silently creating a duplicate pivot row, which
    // would render the tag twice on the product page.
    expect(fn () => $product->tags()->attach($tag))
        ->toThrow(UniqueConstraintViolationException::class);

    expect($product->fresh()->tags)->toHaveCount(1);
});
