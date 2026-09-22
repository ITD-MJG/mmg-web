<?php

use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Tags\Pages\CreateTag;
use App\Filament\Resources\Tags\Pages\EditTag;
use App\Filament\Resources\Tags\TagResource;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;

/**
 * Covers the tag vocabulary and the product form's tag field.
 *
 * Tags are a separate many-to-many from the single required category, so the
 * thing worth pinning is that the admin can create the vocabulary and attach
 * several tags to one product without disturbing the category.
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->actingAs($this->admin);
});

it('creates a tag with both locales', function () {
    Livewire::test(CreateTag::class)
        ->fillForm([
            'slug' => 'ultra-low-temperature-freezer',
            'name' => [
                'id' => 'Freezer Suhu Ultra Rendah',
                'en' => 'Ultra Low Temperature Freezer',
            ],
            'is_published' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $tag = Tag::where('slug', 'ultra-low-temperature-freezer')->first();

    expect($tag)->not->toBeNull()
        ->and($tag->getTranslation('name', 'id'))->toBe('Freezer Suhu Ultra Rendah')
        ->and($tag->getTranslation('name', 'en'))->toBe('Ultra Low Temperature Freezer');
});

it('rejects a duplicate tag slug', function () {
    Tag::factory()->create(['slug' => 'taken']);

    Livewire::test(CreateTag::class)
        ->fillForm([
            'slug' => 'taken',
            'name' => ['id' => 'Nama', 'en' => 'Name'],
        ])
        ->call('create')
        ->assertHasFormErrors(['slug']);
});

it('lists tags with their product count', function () {
    $tag = Tag::factory()->create(['name' => ['id' => 'Tag Uji', 'en' => 'Test Tag']]);
    Product::factory()->create()->tags()->attach($tag);

    $this->get(TagResource::getUrl('index'))
        ->assertOk()
        // The panel runs in the `id` locale, so the Indonesian translation is
        // what the list renders.
        ->assertSee('Tag Uji');
});

it('edits a tag', function () {
    $tag = Tag::factory()->create(['name' => ['id' => 'Lama', 'en' => 'Old']]);

    Livewire::test(EditTag::class, ['record' => $tag->slug])
        ->fillForm(['name' => ['id' => 'Baru', 'en' => 'New']])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($tag->fresh()->getTranslation('name', 'en'))->toBe('New');
});

it('attaches several tags to a product without touching its category', function () {
    $category = ProductCategory::factory()->create();
    $product = Product::factory()->for($category, 'category')->create();
    $tags = Tag::factory()->count(3)->create();

    Livewire::test(EditProduct::class, ['record' => $product->slug])
        ->fillForm(['tags' => $tags->pluck('id')->all()])
        ->call('save')
        ->assertHasNoFormErrors();

    $fresh = $product->fresh();

    expect($fresh->tags)->toHaveCount(3)
        ->and($fresh->category_id)->toBe($category->id);
});
