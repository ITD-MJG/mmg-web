<?php

use App\Filament\Pages\ManageSettings;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

it('round-trips a json setting value', function () {
    Setting::set('socials', ['linkedin' => 'https://linkedin.com/company/x']);

    expect(Setting::get('socials'))->toBe(['linkedin' => 'https://linkedin.com/company/x']);
});

it('returns the default for a missing key', function () {
    expect(Setting::get('nope', 'fallback'))->toBe('fallback');
});

it('lets an admin reach the settings page', function () {
    $this->actingAs($this->admin)->get('/admin/settings')
        ->assertOk()
        // A 200 alone would pass even if the form never rendered, so assert
        // the fields staff actually edit are on the page.
        ->assertSee('Nama perusahaan')
        ->assertSee('Email kontak')
        ->assertSee('Media sosial');
});

it('blocks an editor from the settings page', function () {
    $editor = User::factory()->create();
    $editor->assignRole('editor');

    $this->actingAs($editor)->get('/admin/settings')->assertForbidden();

    // The route guard is `mountCanAuthorizeAccess()`, which is what a direct
    // HTTP request exercises. A Livewire round trip goes through
    // `hydrateCanAuthorizeAccess()` instead, so check that path too — an
    // editor who somehow held a rendered page must not be able to save.
    Livewire::test(ManageSettings::class)->assertForbidden();

    expect(Setting::get('company_name'))->toBeNull();
});

it('saves settings and pre-fills them on the next mount', function () {
    $this->actingAs($this->admin);

    Livewire::test(ManageSettings::class)
        ->fillForm([
            'company_name' => 'PT Medquest Mitra Global',
            'contact_email' => 'sales@mmg.co.id',
            'contact_phone' => '(021) 555 1234',
            'whatsapp' => '+62 812-3456-7890',
            'address' => 'Jl. Contoh No. 1, Jakarta',
            'default_meta_title' => 'Alat Kesehatan',
            'default_meta_description' => 'Distributor alat kesehatan.',
            'socials' => ['linkedin' => 'https://linkedin.com/company/mmg'],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Setting::get('company_name'))->toBe('PT Medquest Mitra Global')
        ->and(Setting::get('contact_email'))->toBe('sales@mmg.co.id')
        // Stored exactly as typed: Indonesian phone formats vary, so the
        // value must survive the round trip unnormalised.
        ->and(Setting::get('contact_phone'))->toBe('(021) 555 1234')
        ->and(Setting::get('whatsapp'))->toBe('+62 812-3456-7890')
        ->and(Setting::get('address'))->toBe('Jl. Contoh No. 1, Jakarta')
        ->and(Setting::get('default_meta_title'))->toBe('Alat Kesehatan')
        ->and(Setting::get('default_meta_description'))->toBe('Distributor alat kesehatan.')
        ->and(Setting::get('socials'))->toBe(['linkedin' => 'https://linkedin.com/company/mmg']);

    Livewire::test(ManageSettings::class)
        ->assertFormSet([
            'company_name' => 'PT Medquest Mitra Global',
            'contact_email' => 'sales@mmg.co.id',
            'contact_phone' => '(021) 555 1234',
            'whatsapp' => '+62 812-3456-7890',
            'socials' => ['linkedin' => 'https://linkedin.com/company/mmg'],
        ]);

    // And through a real HTTP request, so the pre-fill is proven on the page
    // the browser actually loads rather than only inside the Livewire harness.
    $this->get('/admin/settings')
        ->assertOk()
        ->assertSee('PT Medquest Mitra Global')
        ->assertSee('sales@mmg.co.id');
});

it('leaves settings the form does not expose untouched', function () {
    $this->actingAs($this->admin);

    Setting::set('unrelated_key', 'keep-me');

    Livewire::test(ManageSettings::class)
        ->fillForm(['company_name' => 'PT Baru'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Setting::get('unrelated_key'))->toBe('keep-me')
        ->and(Setting::get('company_name'))->toBe('PT Baru');
});

it('rejects an invalid contact email', function () {
    $this->actingAs($this->admin);

    Livewire::test(ManageSettings::class)
        ->fillForm(['contact_email' => 'bukan-email'])
        ->call('save')
        ->assertHasFormErrors(['contact_email']);

    expect(Setting::get('contact_email'))->toBeNull();
});
