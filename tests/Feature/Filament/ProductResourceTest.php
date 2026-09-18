<?php

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Products\RelationManagers\ImagesRelationManager;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->actingAs($this->admin);
});

it('creates a product with both locales populated', function () {
    Livewire::test(CreateProduct::class)
        ->fillForm([
            'slug' => 'enema-set',
            'name' => ['id' => 'Enema Set', 'en' => 'Enema Set'],
            'short_description' => [
                'id' => str_repeat('kata ', 45),
                'en' => str_repeat('word ', 45),
            ],
            'category_id' => Category::factory()->create()->id,
            'is_published' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $product = Product::where('slug', 'enema-set')->first();

    expect($product)->not->toBeNull()
        ->and($product->getTranslation('name', 'en'))->toBe('Enema Set')
        ->and($product->getTranslation('name', 'id'))->toBe('Enema Set')
        ->and($product->is_published)->toBeTrue();
});

it('stores the specs repeater as a key-value object', function () {
    Livewire::test(CreateProduct::class)
        ->fillForm([
            'slug' => 'spec-product',
            'name' => ['id' => 'Produk Spesifikasi', 'en' => 'Spec Product'],
            'category_id' => Category::factory()->create()->id,
            'specs' => [
                ['key' => 'Ukuran', 'value' => '10x15cm'],
                ['key' => 'Berat', 'value' => '2kg'],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $product = Product::where('slug', 'spec-product')->first();

    expect($product)->not->toBeNull()
        ->and($product->specs)->toBe(['Ukuran' => '10x15cm', 'Berat' => '2kg']);

    // The stored JSON must be an object, not a list, so the public product
    // page can render `specs` as a definition list keyed by label.
    $stored = json_decode($product->getRawOriginal('specs'), true);

    expect($stored)->toBe(['Ukuran' => '10x15cm', 'Berat' => '2kg'])
        ->and(array_is_list($stored))->toBeFalse();
});

it('pre-fills the specs repeater when editing a product that has specs', function () {
    $product = Product::factory()->create(['specs' => ['Ukuran' => '10x15cm']]);

    $page = Livewire::test(EditProduct::class, ['record' => $product->slug]);

    $specs = $page->instance()->form->getRawState()['specs'] ?? null;

    expect($specs)->toBeArray()
        ->and(array_values($specs))->toBe([['key' => 'Ukuran', 'value' => '10x15cm']]);
});

it('opens the edit form for a product whose certifications are null', function () {
    $product = Product::factory()->create(['certifications' => null]);

    Livewire::test(EditProduct::class, ['record' => $product->slug])
        ->assertSuccessful()
        ->assertFormSet(['slug' => $product->slug]);
});

it('lets an editor reach the product resource', function () {
    $editor = User::factory()->create();
    $editor->assignRole('editor');

    $this->actingAs($editor)->get('/admin/products')->assertOk();
});

it('demotes the previous cover when a second image is flagged through the relation manager', function () {
    // `FileUpload` drops hydrated paths whose file is missing from the disk, so
    // without real files the `path` field would hydrate empty and fail its
    // `required()` rule — a test artefact, not a product bug.
    Storage::fake('public');
    Storage::disk('public')->put('products/first.jpg', 'x');
    Storage::disk('public')->put('products/second.jpg', 'x');

    $product = Product::factory()->create();
    $first = ProductImage::factory()->cover()->create([
        'product_id' => $product->id,
        'path' => 'products/first.jpg',
    ]);
    $second = ProductImage::factory()->create([
        'product_id' => $product->id,
        'path' => 'products/second.jpg',
    ]);

    Livewire::test(ImagesRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->callTableAction('edit', $second, ['is_cover' => true])
        ->assertHasNoTableActionErrors();

    // The unique index `uniq_cover_per_product` is the backstop, so a failure
    // here would surface as a raw QueryException rather than a soft assertion.
    expect($second->fresh()->is_cover)->toBeTrue()
        ->and($first->fresh()->is_cover)->toBeFalse()
        ->and(ProductImage::where('product_id', $product->id)->where('is_cover', true)->count())->toBe(1);
});

it('registers the images relation manager on the product edit page', function () {
    $product = Product::factory()->create();

    expect(ProductResource::getRelations())->toContain(ImagesRelationManager::class);

    $this->get("/admin/products/{$product->slug}/edit")->assertOk();
});

it('stores an uploaded image as a path on the public disk with translated alt text', function () {
    Storage::fake('public');

    $product = Product::factory()->create();

    Livewire::test(ImagesRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->callTableAction('create', data: [
            'path' => [UploadedFile::fake()->image('foto.jpg')],
            'is_cover' => true,
            'alt' => ['id' => 'Foto enema', 'en' => 'Enema photo'],
            'sort_order' => 3,
        ])
        ->assertHasNoTableActionErrors();

    $image = ProductImage::where('product_id', $product->id)->sole();

    // `path` is a string column, so the FileUpload must store one path, not an
    // array — an array here would be written to MySQL as the literal "Array".
    expect($image->path)->toBeString()
        ->and($image->path)->toStartWith('products/')
        ->and($image->is_cover)->toBeTrue()
        ->and($image->sort_order)->toBe(3)
        ->and($image->getTranslation('alt', 'id', false))->toBe('Foto enema')
        ->and($image->getTranslation('alt', 'en', false))->toBe('Enema photo');

    Storage::disk('public')->assertExists($image->path);
});
