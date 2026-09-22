<?php

use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Principals\Pages\CreatePrincipal;
use App\Filament\Resources\Principals\Pages\EditPrincipal;
use App\Filament\Resources\ProductCategories\Pages\CreateProductCategory;
use App\Filament\Resources\ProductCategories\Pages\EditProductCategory;
use App\Models\Page;
use App\Models\Principal;
use App\Models\ProductCategory;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Forms\Components\FileUpload;
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
    Livewire::test(CreateProductCategory::class)
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

    $category = ProductCategory::where('slug', 'alat-kesehatan')->first();

    expect($category)->not->toBeNull()
        ->and($category->getTranslation('name', 'id'))->toBe('Alat Kesehatan')
        ->and($category->getTranslation('name', 'en'))->toBe('Medical Devices')
        ->and($category->is_published)->toBeTrue();
});

it('rejects a category that is its own parent', function () {
    $category = ProductCategory::factory()->create();

    Livewire::test(EditProductCategory::class, ['record' => $category->slug])
        ->fillForm(['parent_id' => $category->id])
        ->call('save')
        ->assertHasFormErrors(['parent_id']);

    expect($category->fresh()->parent_id)->toBeNull();
});

it('creates a principal with a plain name and a translatable description', function () {
    Livewire::test(CreatePrincipal::class)
        // Principal names are not translated, so `name` is a single input and must
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
                'en' => 'Medical equipment principal',
            ],
            'is_published' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $principal = Principal::where('slug', 'medquest')->first();

    expect($principal)->not->toBeNull()
        ->and($principal->name)->toBe('MedQuest')
        ->and($principal->getTranslation('description', 'id'))->toBe('Merek peralatan medis')
        ->and($principal->getTranslation('description', 'en'))->toBe('Medical equipment principal');
});

it('crops a principal logo upload to a square', function () {
    Livewire::test(CreatePrincipal::class)
        ->assertFormFieldExists('logo', checkFieldUsing: function ($field): bool {
            // The public carousel frames every mark in a 1:1 box, so an upload
            // that keeps its own ratio would letterbox inside that frame and
            // read as a smaller logo than its neighbours. Both calls matter:
            // the ratio alone only sets the editor's default, while the crop
            // flag is what applies it without the uploader intervening.
            return $field instanceof FileUpload
                && $field->getImageAspectRatio() === '1:1'
                && $field->shouldAutomaticallyCropImagesToAspectRatio();
        });
});

it('stores the principal certifications repeater as a list of rows', function () {
    Livewire::test(CreatePrincipal::class)
        ->assertFormFieldExists(
            'certifications',
            checkFieldUsing: fn ($field): bool => $field instanceof Repeater,
        )
        ->fillForm([
            'slug' => 'certified-principal',
            'name' => 'Certified Principal',
            'certifications' => [
                ['type' => 'izin_edar', 'number' => 'AKL 123'],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $principal = Principal::where('slug', 'certified-principal')->first();

    expect($principal)->not->toBeNull()
        ->and($principal->certifications)->toBe([['type' => 'izin_edar', 'number' => 'AKL 123']]);
});

it('saves a principal whose certifications are null without error', function () {
    $principal = Principal::factory()->create(['certifications' => null]);

    Livewire::test(EditPrincipal::class, ['record' => $principal->slug])
        ->assertSuccessful()
        ->assertFormSet(['slug' => $principal->slug])
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
