<?php

use App\Models\Principal;
use App\Models\Product;
use App\Models\ProductCategory;

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
    $a = ProductCategory::factory()->create(['name' => ['id' => 'Kategori A', 'en' => 'Category A']]);
    $b = ProductCategory::factory()->create(['name' => ['id' => 'Kategori B', 'en' => 'Category B']]);

    Product::factory()->for($a, 'category')->create(['name' => ['id' => 'Produk A', 'en' => 'Product A']]);
    Product::factory()->for($b, 'category')->create(['name' => ['id' => 'Produk B', 'en' => 'Product B']]);

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
    $category = ProductCategory::factory()->create(['name' => ['id' => 'Kategori A', 'en' => 'Category A']]);

    Product::factory()->count(25)->for($category, 'category')->create([
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
    ProductCategory::factory()->create(['name' => ['id' => 'Kategori Terbit', 'en' => 'Published Category']]);
    ProductCategory::factory()->unpublished()->create(['name' => ['id' => 'Kategori Tersembunyi', 'en' => 'Hidden Category']]);
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

/*
|--------------------------------------------------------------------------
| Sort control
|--------------------------------------------------------------------------
|
| The sort bar sits between the filter form and the grid, so it needs its own
| form (it carries the active filters as hidden inputs) and the filter form
| needs a hidden `sort` input in return. Both directions are asserted below,
| because a control that silently drops the other control's state is the bug
| this shape invites.
|
| Order assertions compare positions of the product names in the rendered
| HTML rather than inspecting the paginator: the paginator is an
| implementation detail, and what a visitor sees is the order in the document.
|
*/

/** Position of the first occurrence of $needle, or false when absent. */
function catalogPosition(string $html, string $needle): int|false
{
    return strpos($html, $needle);
}

it('renders a sort control above the product grid', function () {
    Product::factory()->create(['name' => ['id' => 'Produk Satu', 'en' => 'Product One']]);

    $html = $this->get('/produk')->assertOk()->getContent();

    // Positive control: the grid has to render, or the assertions below would
    // pass on a page that rendered nothing at all.
    expect($html)->toContain('Produk Satu');

    expect($html)->toContain('name="sort"');
    expect($html)->toContain('id="sort-order"');

    // The control sits before the grid, which is what the requirement asks for.
    expect(catalogPosition($html, 'id="sort-order"'))->toBeLessThan(
        catalogPosition($html, 'Produk Satu'),
    );
});

it('marks the active sort option as selected', function () {
    Product::factory()->create(['name' => ['id' => 'Produk Satu', 'en' => 'Product One']]);

    $html = $this->get('/produk?sort=name_desc')->assertOk()->getContent();

    preg_match('/<option value="name_desc"[^>]*>/', $html, $match);
    expect($match)->not->toBeEmpty('name_desc option not rendered');
    expect($match[0])->toContain('selected');
});

it('sorts products by name ascending in the active locale', function () {
    Product::factory()->create(['name' => ['id' => 'Charlie', 'en' => 'Charlie']]);
    Product::factory()->create(['name' => ['id' => 'Alpha', 'en' => 'Alpha']]);
    Product::factory()->create(['name' => ['id' => 'Bravo', 'en' => 'Bravo']]);

    $html = $this->get('/produk?sort=name_asc')->assertOk()->getContent();

    $alpha = catalogPosition($html, 'Alpha');
    $bravo = catalogPosition($html, 'Bravo');
    $charlie = catalogPosition($html, 'Charlie');

    expect($alpha)->not->toBeFalse()
        ->and($bravo)->not->toBeFalse()
        ->and($charlie)->not->toBeFalse();

    expect($alpha)->toBeLessThan($bravo);
    expect($bravo)->toBeLessThan($charlie);
});

it('sorts products by name descending', function () {
    Product::factory()->create(['name' => ['id' => 'Charlie', 'en' => 'Charlie']]);
    Product::factory()->create(['name' => ['id' => 'Alpha', 'en' => 'Alpha']]);
    Product::factory()->create(['name' => ['id' => 'Bravo', 'en' => 'Bravo']]);

    $html = $this->get('/produk?sort=name_desc')->assertOk()->getContent();

    expect(catalogPosition($html, 'Charlie'))->toBeLessThan(catalogPosition($html, 'Bravo'));
    expect(catalogPosition($html, 'Bravo'))->toBeLessThan(catalogPosition($html, 'Alpha'));
});

it('sorts products newest first', function () {
    // created_at is second-precision, so the two rows are given distinct
    // explicit timestamps rather than relying on insertion order.
    //
    // The new product is inserted FIRST, so the default order (id desc) would
    // put the old one on top. Without that, this test passed against the
    // unimplemented controller because insertion order and recency happened to
    // agree.
    Product::factory()->create([
        'name' => ['id' => 'Produk Baru', 'en' => 'New Product'],
        'created_at' => now(),
    ]);
    Product::factory()->create([
        'name' => ['id' => 'Produk Lama', 'en' => 'Old Product'],
        'created_at' => now()->subDays(2),
    ]);

    $html = $this->get('/produk?sort=newest')->assertOk()->getContent();

    expect(catalogPosition($html, 'Produk Baru'))->toBeLessThan(catalogPosition($html, 'Produk Lama'));
});

it('sorts products oldest first', function () {
    Product::factory()->create([
        'name' => ['id' => 'Produk Lama', 'en' => 'Old Product'],
        'created_at' => now()->subDays(2),
    ]);
    Product::factory()->create([
        'name' => ['id' => 'Produk Baru', 'en' => 'New Product'],
        'created_at' => now(),
    ]);

    $html = $this->get('/produk?sort=oldest')->assertOk()->getContent();

    expect(catalogPosition($html, 'Produk Lama'))->toBeLessThan(catalogPosition($html, 'Produk Baru'));
});

it('ignores an unrecognised sort value', function () {
    // A whitelist, not a passthrough: an unknown value must fall back to the
    // default order rather than reaching the query builder, where it would be
    // a column name chosen by the visitor.
    //
    // The fixture is ordered so the default order (id desc) is the reverse of
    // name order, which is what an unsanitised `orderBy($request->sort)` would
    // produce for `sort=name_asc`. Falling back to the default is therefore
    // observable rather than coincidental.
    Product::factory()->create(['name' => ['id' => 'Alpha', 'en' => 'Alpha']]);
    Product::factory()->create(['name' => ['id' => 'Bravo', 'en' => 'Bravo']]);

    // `id` is a real column and `SORT_ORDER` is a real column name in caps:
    // both are exactly the values a passthrough would happily order by, so the
    // whitelist is what keeps them out.
    foreach (['id', 'name; DROP TABLE products', '', 'SORT_ORDER'] as $sort) {
        $html = $this->get('/produk?sort='.urlencode($sort))->assertOk()->getContent();

        // Default order is id desc, so Bravo comes first.
        expect(catalogPosition($html, 'Bravo'))->toBeLessThan(catalogPosition($html, 'Alpha'));
    }
});

it('keeps the sort in the pagination links', function () {
    // 25 rows is two pages at 24 per page. The timestamps are spread so the
    // newest product is on page one and the oldest is pushed onto page two:
    // that makes the assertion about which product is visible, not merely
    // about the query string surviving, which withQueryString() does on its
    // own.
    Product::factory()->count(24)->create([
        'name' => ['id' => 'Produk Tengah', 'en' => 'Middle Product'],
        'created_at' => now()->subDay(),
    ]);
    Product::factory()->create([
        'name' => ['id' => 'Produk Terbaru', 'en' => 'Newest Product'],
        'created_at' => now(),
    ]);
    Product::factory()->create([
        'name' => ['id' => 'Produk Tertua', 'en' => 'Oldest Product'],
        'created_at' => now()->subDays(30),
    ]);

    $first = $this->get('/produk?sort=newest')->assertOk();
    $first->assertSee('page=2');
    $first->assertSee('sort=newest');
    $first->assertSee('Produk Terbaru');
    $first->assertDontSee('Produk Tertua');

    $second = $this->get('/produk?sort=newest&page=2')->assertOk();
    $second->assertSee('Produk Tertua');
});

it('puts the sort control in the same form as the filters', function () {
    // One form, one Apply button. The alternative (a second form for the sort)
    // would have to re-post every filter as a hidden input and would need a
    // second submit, which is two places for the filter state to go wrong.
    // Co-location is the invariant that makes state loss impossible, so it is
    // what the test pins rather than the hidden-input plumbing that a
    // separate form would need.
    Product::factory()->create(['name' => ['id' => 'Produk Satu', 'en' => 'Product One']]);

    $html = $this->get('/produk?sort=newest')->assertOk()->getContent();

    preg_match('#<form\b.*?</form>#s', $html, $form);
    expect($form)->not->toBeEmpty('no filter form found');

    expect($form[0])->toContain('id="sort-order"')
        ->toContain('value="newest"');

    // Exactly one form on the page: a second one is the shape this replaced.
    expect(substr_count($html, '<form'))->toBe(1);
});

it('keeps the sort chrome translated', function () {
    Product::factory()->create(['name' => ['id' => 'Produk Satu', 'en' => 'Product One']]);

    $indonesian = $this->get('/produk')->assertOk()->getContent();

    expect($indonesian)->toContain('Urutkan');

    foreach (['Newest', 'Oldest', 'Name A-Z', 'Name Z-A'] as $english) {
        expect(str_contains($indonesian, $english))
            ->toBeFalse("untranslated sort chrome on /produk: {$english}");
    }
});

/*
|--------------------------------------------------------------------------
| Progressive enhancement (Alpine)
|--------------------------------------------------------------------------
|
| The catalog keeps the server as the authority for filtering, sorting, and
| paging. Alpine only removes the full page reload: it intercepts the form
| submit, fetches the same URL, and swaps the results region in place.
|
| That shape is deliberate. Filtering or sorting only the rows already on the
| page would be wrong rather than merely slower, because the catalog paginates
| at 24: "name A-Z" over the current page is not "name A-Z" over the catalog,
| and a client-side filter silently hides every match on page 2. The server
| keeps the query, the client keeps the URL in step.
|
| These tests therefore assert the two halves separately: the markup must be
| complete and functional with no JavaScript at all, and the script must carry
| the enhancement. That is the same split the principal carousel uses.
|
*/

it('ships a complete catalog that works with no JavaScript', function () {
    // Every product is rendered server-side, so a visitor without JavaScript
    // gets the full grid rather than a shell the script was meant to fill.
    // The enhancement must never be load-bearing.
    $products = Product::factory()->count(5)->create([
        'name' => ['id' => 'Produk Lengkap', 'en' => 'Complete Product'],
    ]);

    $html = $this->get('/produk')->assertOk()->getContent();

    expect(substr_count($html, 'Produk Lengkap'))->toBe(5, 'every product must render server-side');

    // A real form with a real submit, not a scripted control: with JavaScript
    // off this is what applies the filters, and it must stay that way.
    expect($html)->toContain('<form')
        ->toContain('method="get"')
        ->toContain('type="submit"');

    // A real select, not a styled button that only a script can drive.
    expect($html)->toContain('<select id="sort-order"');
});

it('marks the results region the enhancement swaps', function () {
    // The script needs one element that contains the result count, the grid,
    // and the pagination, so a single swap keeps all three in agreement. If the
    // count lived outside the swapped region it would go stale on every filter.
    Product::factory()->create(['name' => ['id' => 'Produk Satu', 'en' => 'Product One']]);

    $html = $this->get('/produk')->assertOk()->getContent();

    expect($html)->toContain('data-catalog-results')
        ->toContain('data-catalog-form');

    // The region is announced when it changes, and marked busy while the fetch
    // is in flight. Without these a screen reader user gets no signal that the
    // grid was replaced at all.
    preg_match('#<[^>]*data-catalog-results[^>]*>#', $html, $region);
    expect($region)->not->toBeEmpty('results region not found');
    expect($region[0])->toContain('aria-live="polite"')
        ->toContain('aria-busy="false"');
});

it('renders the empty state inside the swapped results region', function () {
    // The empty state is a different branch of the same region. If it sat
    // outside it, filtering down to zero results would swap the grid for
    // nothing and leave the previous products on screen.
    $html = $this->get('/produk')->assertOk()->getContent();

    expect($html)->toContain('data-catalog-results');
    expect($html)->toContain(__('ui.catalog.empty'));
});

it('ships the catalog enhancement in a bundle only the catalog loads', function () {
    // Alpine is here for the filter swap and nothing else on the public site
    // uses it. In the shared entry it would ship to every visitor on every page
    // to pay for one page's enhancement, so it has its own entry that the
    // catalog view pulls in with `@vite`.
    $js = file_get_contents(resource_path('js/catalog.js'));

    // Alpine is imported from the bundle rather than a CDN script tag, so the
    // asset stays in the Vite build and needs no runtime network fetch.
    expect($js)->toContain("import Alpine from 'alpinejs'")
        ->toContain('Alpine.start()');

    // The component is registered under the name the view binds with
    // `x-data`, so the two must agree or the markup is inert.
    expect($js)->toContain("Alpine.data('catalogResults'");

    $view = file_get_contents(resource_path('views/pages/catalog.blade.php'));
    expect($view)->toContain('x-data="catalogResults"')
        ->toContain("@vite('resources/js/catalog.js')");

    // The swap reads the region out of a fetched document, which is what keeps
    // the server authoritative for the result set.
    expect($js)->toContain('DOMParser')
        ->toContain('fetch(')
        ->toContain('data-catalog-results');

    // The shared entry must stay free of Alpine. Asserted rather than assumed:
    // a stray import there would silently put it back on every page and no
    // functional test would notice.
    $shared = file_get_contents(resource_path('js/app.js'));
    expect($shared)->not->toContain('alpinejs');
});

it('does not drive the catalog from an inline handler', function () {
    // The enhancement attaches its listeners from app.js. An inline `onchange`
    // would be the only script in a public view, would need `unsafe-inline` in
    // any future CSP, and would run before the module that owns the logic.
    $html = $this->get('/produk')->assertOk()->getContent();

    expect($html)->not->toContain('onchange=')
        ->not->toContain('onsubmit=')
        ->not->toContain('onclick=');
});

it('keeps the sort select working when the form is submitted without JavaScript', function () {
    // The no-JavaScript path is the form itself, so the sort must survive a
    // real submission and not depend on the script to be sent to the server.
    Product::factory()->create(['name' => ['id' => 'Alpha', 'en' => 'Alpha']]);
    Product::factory()->create(['name' => ['id' => 'Bravo', 'en' => 'Bravo']]);

    $html = $this->get('/produk?sort=name_desc')->assertOk()->getContent();

    expect(catalogPosition($html, 'Bravo'))->toBeLessThan(catalogPosition($html, 'Alpha'));
});
