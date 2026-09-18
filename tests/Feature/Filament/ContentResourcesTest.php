<?php

use App\Filament\Resources\Brands\Pages\CreateBrand;
use App\Filament\Resources\Brands\Pages\EditBrand;
use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->actingAs($this->admin);
});

it('creates a category with both locales', function () {
    Livewire::test(CreateCategory::class)
        // The two locales live in separate tabs, so the fields are nested
        // under the locale key rather than being one flat input.
        ->assertFormFieldExists('name.id')
        ->assertFormFieldExists('name.en')
        ->assertFormFieldExists('description.id')
        ->assertFormFieldExists('description.en')
        ->fillForm([
            'slug' => 'alat-kesehatan',
            'name' => ['id' => 'Alat Kesehatan', 'en' => 'Medical Devices'],
            'is_published' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $category = Category::where('slug', 'alat-kesehatan')->first();

    expect($category)->not->toBeNull()
        ->and($category->getTranslation('name', 'id'))->toBe('Alat Kesehatan')
        ->and($category->getTranslation('name', 'en'))->toBe('Medical Devices')
        ->and($category->is_published)->toBeTrue();
});

it('rejects a category that is its own parent', function () {
    $category = Category::factory()->create();

    Livewire::test(EditCategory::class, ['record' => $category->slug])
        ->fillForm(['parent_id' => $category->id])
        ->call('save')
        ->assertHasFormErrors(['parent_id']);

    expect($category->fresh()->parent_id)->toBeNull();
});

it('creates a brand with a plain name and a translatable description', function () {
    Livewire::test(CreateBrand::class)
        // Brand names are not translated, so `name` is a single input and must
        // not be nested under a locale key like the other resources.
        ->assertFormFieldExists('name')
        ->assertFormFieldDoesNotExist('name.id')
        ->assertFormFieldExists('description.id')
        ->assertFormFieldExists('description.en')
        ->fillForm([
            'slug' => 'medquest',
            'name' => 'MedQuest',
            'description' => [
                'id' => 'Merek peralatan medis',
                'en' => 'Medical equipment brand',
            ],
            'is_published' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $brand = Brand::where('slug', 'medquest')->first();

    expect($brand)->not->toBeNull()
        ->and($brand->name)->toBe('MedQuest')
        ->and($brand->getTranslation('description', 'id'))->toBe('Merek peralatan medis')
        ->and($brand->getTranslation('description', 'en'))->toBe('Medical equipment brand');
});

it('stores the brand certifications repeater as a list of rows', function () {
    Livewire::test(CreateBrand::class)
        ->assertFormFieldExists(
            'certifications',
            checkFieldUsing: fn ($field): bool => $field instanceof Repeater,
        )
        ->fillForm([
            'slug' => 'certified-brand',
            'name' => 'Certified Brand',
            'certifications' => [
                ['type' => 'izin_edar', 'number' => 'AKL 123'],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $brand = Brand::where('slug', 'certified-brand')->first();

    expect($brand)->not->toBeNull()
        ->and($brand->certifications)->toBe([['type' => 'izin_edar', 'number' => 'AKL 123']]);
});

it('saves a brand whose certifications are null without error', function () {
    $brand = Brand::factory()->create(['certifications' => null]);

    Livewire::test(EditBrand::class, ['record' => $brand->slug])
        ->assertSuccessful()
        ->assertFormSet(['slug' => $brand->slug])
        ->call('save')
        ->assertHasNoFormErrors();
});

it('creates a page with both locales and round-trips the body', function () {
    Livewire::test(CreatePage::class)
        ->assertFormFieldExists(
            'body.id',
            checkFieldUsing: fn ($field): bool => $field instanceof RichEditor,
        )
        ->assertFormFieldExists('title.en')
        ->fillForm([
            'slug' => 'tentang-kami',
            'title' => ['id' => 'Tentang Kami', 'en' => 'About Us'],
            'body' => [
                'id' => '<h2>Sejarah</h2><p>Berdiri sejak 2010.</p>',
                'en' => '<h2>History</h2><p>Founded in 2010.</p>',
            ],
            'is_published' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $page = Page::where('slug', 'tentang-kami')->first();

    expect($page)->not->toBeNull()
        ->and($page->getTranslation('title', 'id'))->toBe('Tentang Kami')
        ->and($page->getTranslation('title', 'en'))->toBe('About Us')
        ->and($page->getTranslation('body', 'id'))->toBe('<h2>Sejarah</h2><p>Berdiri sejak 2010.</p>')
        ->and($page->getTranslation('body', 'en'))->toBe('<h2>History</h2><p>Founded in 2010.</p>');
});
