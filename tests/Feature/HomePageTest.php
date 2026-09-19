<?php

use App\Models\Category;
use App\Models\Principal;
use App\Models\Product;
use App\Models\Setting;

it('renders the home page in both locales', function () {
    $this->get('/')->assertOk();
    $this->get('/en')->assertOk();
});

it('shows the latest products', function () {
    $product = Product::factory()->create(['is_published' => true]);

    $this->get('/')->assertSee($product->getTranslation('name', 'id'));
});

it('never renders a price', function () {
    Product::factory()->create(['is_published' => true]);

    $response = $this->get('/');

    $response->assertDontSee('Rp', false);
    $response->assertDontSee('IDR');
});

it('shows at most six products, newest first', function () {
    $old = Product::factory()->create(['is_published' => true, 'name' => ['id' => 'Tertua']]);
    Product::factory()->count(6)->create(['is_published' => true]);

    $response = $this->get('/');

    $response->assertDontSee('Tertua');
});

it('renders the facilities marquee from config', function () {
    $first = config('site.facility_types')[0];

    $this->get('/')->assertSee($first);
});

it('renders the principal marquee from published principals only', function () {
    Principal::factory()->create(['is_published' => true, 'name' => 'Principal Tampil']);
    Principal::factory()->create(['is_published' => false, 'name' => 'Principal Tersembunyi']);

    $response = $this->get('/');

    $response->assertSee('Principal Tampil');
    $response->assertDontSee('Principal Tersembunyi');
});

it('shows the contact details from settings', function () {
    Setting::set('address', 'Jl. Contoh No. 1, Jakarta');

    $this->get('/')->assertSee('Jl. Contoh No. 1, Jakarta');
});

it('renders the footer copyright with the current year and company name', function () {
    Setting::set('company_name', 'Medquest Mitra Global');

    $this->get('/')->assertSee(now()->year.' - Medquest Mitra Global');
});

it('does not render a category grid on the home page', function () {
    Category::factory()->create(['is_published' => true, 'name' => ['id' => 'Kategori Tampil']]);

    $this->get('/')->assertDontSee('Kategori Tampil');
});
