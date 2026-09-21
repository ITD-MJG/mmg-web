<?php

use App\Models\Category;
use App\Models\Principal;
use App\Models\Product;

/*
|--------------------------------------------------------------------------
| Catalog index
|--------------------------------------------------------------------------
|
| Every exclusion assertion here is preceded by a positive control on the same
| response. The first draft of this file asserted only `assertDontSee(...)`,
| which passes when the excluded product's name appears in no rendered field at
| all — so it proved nothing about filtering. `it('filters by category')` in
| particular passed against a 500 response, which was verified by mutation.
|
| The excluded product's name is always seeded into the `<h3>` title, which is
| a field that genuinely renders, so the negative assertion has something to be
| wrong about.
|
*/

it('lists only published products', function () {
    Product::factory()->create([
        'is_published' => true,
        'name' => ['id' => 'Terbit', 'en' => 'Published'],
        'short_description' => ['id' => 'Terbit desc', 'en' => 'Published desc'],
    ]);
    Product::factory()->create([
        'is_published' => false,
        'name' => ['id' => 'Draf', 'en' => 'Draft'],
        'short_description' => ['id' => 'Draf desc', 'en' => 'Draft desc'],
    ]);

    $this->get('/produk')
        ->assertOk()
        ->assertSee('Terbit')
        ->assertDontSee('Draf');
});

it('filters by category', function () {
    $a = Category::factory()->create(['name' => ['id' => 'Kategori A', 'en' => 'Category A']]);
    $b = Category::factory()->create(['name' => ['id' => 'Kategori B', 'en' => 'Category B']]);

    Product::factory()->for($a)->create(['name' => ['id' => 'Produk A', 'en' => 'Product A']]);
    Product::factory()->for($b)->create(['name' => ['id' => 'Produk B', 'en' => 'Product B']]);

    // Positive control: without it this test passed on an error page.
    $this->get('/produk')
        ->assertOk()
        ->assertSee('Produk A')
        ->assertSee('Produk B');

    $this->get('/produk?category='.$a->slug)
        ->assertOk()
        ->assertSee('Produk A')
        ->assertDontSee('Produk B');
});

it('filters by principal', function () {
    $principal = Principal::factory()->create(['name' => 'OneMed']);
    Product::factory()->for($principal)->create(['name' => ['id' => 'Produk Bermerek', 'en' => 'Principaled']]);
    Product::factory()->create(['name' => ['id' => 'Tanpa Merek', 'en' => 'Unprincipaled']]);

    $this->get('/produk')
        ->assertOk()
        ->assertSee('Produk Bermerek')
        ->assertSee('Tanpa Merek');

    $this->get('/produk?principal='.$principal->slug)
        ->assertOk()
        ->assertSee('Produk Bermerek')
        ->assertDontSee('Tanpa Merek');
});

it('searches by name', function () {
    Product::factory()->create(['name' => ['id' => 'Enema Set Steril', 'en' => 'Sterile Enema Set']]);
    Product::factory()->create(['name' => ['id' => 'Kursi Roda', 'en' => 'Wheelchair']]);

    $this->get('/produk?q=Enema')
        ->assertOk()
        ->assertSee('Enema Set Steril')
        ->assertDontSee('Kursi Roda');
});

it('falls back to a like match for terms below the fulltext minimum', function () {
    // innodb_ft_min_token_size is 3 on this server, so `AB` is not in the index
    // at all and MATCH can never return it. Real products carry SKU-prefixed
    // names, so a two-character query is a legitimate thing to type.
    Product::factory()->create(['name' => ['id' => 'AB-100 Analyzer', 'en' => 'AB-100 Analyzer']]);
    Product::factory()->create(['name' => ['id' => 'Kursi Roda', 'en' => 'Wheelchair']]);

    $this->get('/produk?q=AB')
        ->assertOk()
        ->assertSee('AB-100 Analyzer')
        ->assertDontSee('Kursi Roda');
});

it('does not error on a query made only of fulltext operators', function () {
    // `AGAINST('+' IN BOOLEAN MODE)` is ERROR 1064 on MariaDB, not an empty
    // result set, so an unsanitised term would be a 500.
    Product::factory()->create(['name' => ['id' => 'Enema Set Steril', 'en' => 'Sterile Enema Set']]);

    foreach (['+', '-', '***', 'A-', 'vitamin+', '"', '~', '()'] as $term) {
        $this->get('/produk?q='.urlencode($term))->assertOk();
    }
});

it('searches the locale-appropriate name column', function () {
    // Both names are unique across the set, so a leak between columns shows in
    // either direction.
    Product::factory()->create(['name' => ['id' => 'Enema Set Steril', 'en' => 'Sterile Enema Set']]);

    $this->get('/produk?q=Enema')
        ->assertOk()
        ->assertSee('Enema Set Steril');

    $this->get('/produk?q=Sterile')
        ->assertOk()
        ->assertDontSee('Enema Set Steril');

    $this->get('/en/products?q=Sterile')
        ->assertOk()
        ->assertSee('Sterile Enema Set');
});

it('serves the english catalog under /en/products', function () {
    Product::factory()->create(['name' => ['id' => 'Nama Indonesia', 'en' => 'English Name']]);

    $this->get('/en/products')
        ->assertOk()
        ->assertSee('English Name')
        ->assertDontSee('Nama Indonesia');
});

it('paginates the catalog and keeps the filters in the page links', function () {
    $category = Category::factory()->create(['name' => ['id' => 'Kategori A', 'en' => 'Category A']]);

    Product::factory()->count(25)->for($category)->create([
        'name' => ['id' => 'Produk Halaman', 'en' => 'Paged Product'],
    ]);

    // 24 per page, so 25 rows is exactly two pages.
    $this->get('/produk?category='.$category->slug)
        ->assertOk()
        ->assertSee('page=2');

    // withQueryString() is what keeps the filter on page two; without it page
    // two is the unfiltered catalogue.
    $second = $this->get('/produk?category='.$category->slug.'&page=2')->assertOk();

    expect($second->getContent())->toContain('category='.$category->slug);
});

it('uses a fulltext token match, not a substring match', function () {
    // This pins WHICH search path runs, which no other test here does. The
    // mutation probe showed that replacing the FULLTEXT branch with LIKE left
    // the whole suite green, so without this the `MATCH(...)` call was
    // untested: LIKE is a substring test and FULLTEXT matches token prefixes,
    // and the two differ on a needle in the middle of a word.
    //
    // "Xenema Kit" contains the substring "enema" but has no token starting
    // with it, so FULLTEXT must not return it while LIKE would. The term is
    // four characters, above innodb_ft_min_token_size, so this really does
    // exercise the FULLTEXT branch rather than the short-term fallback.
    Product::factory()->create(['name' => ['id' => 'Xenema Kit', 'en' => 'Xenema Kit']]);
    Product::factory()->create(['name' => ['id' => 'Enema Set Steril', 'en' => 'Sterile Enema Set']]);

    $this->get('/produk?q=enema')
        ->assertOk()
        ->assertSee('Enema Set Steril')
        ->assertDontSee('Xenema Kit');
});

it('falls back to a substring match when a token is below the fulltext minimum', function () {
    // The complement of the test above: when ANY token is shorter than
    // innodb_ft_min_token_size the term must leave FULLTEXT entirely, and the
    // observable consequence is that the match becomes a plain substring test
    // over the whole raw term. "AB 100" is not a substring of "AB-100
    // Analyzer", so it must return nothing; a FULLTEXT expression built from
    // the same tokens (`ab* 100*`, OR-ed) WOULD have matched it. That
    // asymmetry is what makes this test able to tell the two branches apart.
    Product::factory()->create(['name' => ['id' => 'AB-100 Analyzer', 'en' => 'AB-100 Analyzer']]);

    $this->get('/produk?q=AB 100')
        ->assertOk()
        ->assertDontSee('AB-100 Analyzer');

    // The hyphenated form is a substring, so the same short token does match.
    $this->get('/produk?q=AB-100')
        ->assertOk()
        ->assertSee('AB-100 Analyzer');
});

it('leaves the catalog unfiltered when the term has no searchable tokens', function () {
    // A term of only FULLTEXT operators tokenises to nothing. Passing it to
    // AGAINST would be a 500, so the query has to be left untouched rather
    // than narrowed to nothing: an unsearchable term means "no search", not
    // "no results".
    Product::factory()->create(['name' => ['id' => 'Enema Set Steril', 'en' => 'Sterile Enema Set']]);

    $this->get('/produk?q=***')
        ->assertOk()
        ->assertSee('Enema Set Steril');
});

it('offers only published categories and principals as filters', function () {
    // The dropdowns are built from a separate query from the product grid, so
    // excluding unpublished rows from the grid says nothing about them. A
    // hidden category appearing in the filter would both leak the name and
    // offer a filter that can only ever return an empty grid.
    Category::factory()->create(['name' => ['id' => 'Kategori Terbit', 'en' => 'Published Category']]);
    Category::factory()->unpublished()->create(['name' => ['id' => 'Kategori Tersembunyi', 'en' => 'Hidden Category']]);
    Principal::factory()->create(['name' => 'Principal Terbit']);
    Principal::factory()->unpublished()->create(['name' => 'Principal Tersembunyi']);

    $html = $this->get('/produk')->assertOk()->getContent();

    // Scope the assertion to the filter form, so a stray mention elsewhere on
    // the page cannot make it pass for the wrong reason.
    preg_match('#<form\\b.*?</form>#s', $html, $form);
    expect($form)->not->toBeEmpty('no filter form found');
    $form = $form[0];

    expect($form)->toContain('Kategori Terbit')
        ->toContain('Principal Terbit');

    expect(str_contains($form, 'Kategori Tersembunyi'))->toBeFalse('unpublished category offered as a filter');
    expect(str_contains($form, 'Principal Tersembunyi'))->toBeFalse('unpublished principal offered as a filter');
});

it('keeps the catalog filter chrome translated', function () {
    // The catalog renders the same chrome in both locales, and R14 makes
    // hardcoded interface copy a spec violation. Asserting the English labels
    // are absent from the Indonesian page is the same check the home page uses.
    $indonesian = $this->get('/produk')->assertOk()->getContent();

    foreach (['Search', 'Filter by', 'Clear filters'] as $english) {
        expect(str_contains($indonesian, $english))
            ->toBeFalse("untranslated catalog chrome on /produk: {$english}");
    }
});
