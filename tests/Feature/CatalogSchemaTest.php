<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;

it('stores translatable fields as json and resolves per locale', function () {
    $category = Category::factory()->create([
        'name' => ['id' => 'Alat Kesehatan', 'en' => 'Medical Devices'],
    ]);

    app()->setLocale('id');
    expect($category->fresh()->name)->toBe('Alat Kesehatan');

    app()->setLocale('en');
    expect($category->fresh()->name)->toBe('Medical Devices');
});

it('relates products to a category and brand', function () {
    $product = Product::factory()
        ->for(Category::factory())
        ->for(Brand::factory())
        ->create();

    expect($product->category)->toBeInstanceOf(Category::class)
        ->and($product->brand)->toBeInstanceOf(Brand::class);
});

it('returns only published products', function () {
    Product::factory()->create(['is_published' => true]);
    Product::factory()->create(['is_published' => false]);

    expect(Product::published()->count())->toBe(1);
});

it('scopes published categories, brands, and pages', function () {
    Category::factory()->create(['is_published' => true]);
    Category::factory()->create(['is_published' => false]);
    Brand::factory()->create(['is_published' => true]);
    Brand::factory()->create(['is_published' => false]);

    expect(Category::published()->count())->toBe(1)
        ->and(Brand::published()->count())->toBe(1);
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
    $category = Category::factory()->create([
        'name' => ['id' => 'Hanya Indonesia'],
    ]);

    app()->setLocale('en');

    expect($category->fresh()->name)->toBe('Hanya Indonesia');
});
