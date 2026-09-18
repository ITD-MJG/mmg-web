# MMG Web v2 — Phase 1 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the bilingual (ID/EN) Medquest Mitra Global product catalog with a Filament admin CMS, an RFQ inquiry flow, and an SEO + AEO/GEO layer, deployable to Domainesia shared hosting.

**Architecture:** Laravel 13 monolith. Public pages are server-rendered Blade with Alpine.js for interactive islands — no Livewire outside Filament, so every public page is guest-cacheable and survives shared-hosting Entry Process limits. Filament 5 provides the entire admin at `/admin`. Locale is resolved from a URL prefix via a thin custom middleware (`''` = ID, `'en'` = EN); one slug is shared across locales. Product specs and certifications are JSON columns. Agent-facing artifacts (`/llms.txt`, `/katalog.md`) are generated from the same Eloquent models as the HTML.

**Tech Stack:** PHP 8.4, Laravel 13, Filament 5, Tailwind 4, Alpine.js, MySQL 8/MariaDB, `spatie/laravel-translatable`, `spatie/laravel-permission`, Pest 5.

**Spec:** `docs/superpowers/specs/2026-09-18-mmg-web-v2-design.md`

## Global Constraints

- **PHP 8.4** — Laravel 13 and Filament 5 require `^8.2`; Pest 5 requires PHP 8.4+.
- **No Node.js runtime on the server.** Vite builds run locally/CI only. `public/build` is produced before deploy and is never built on the host.
- **No Livewire on public routes.** Livewire is confined to the Filament panel. Public interactivity uses Alpine.js only.
- **Two locales only:** `id` (default, empty URL prefix) and `en` (prefix `en`). No other locale may be added without revisiting the hreflang architecture.
- **One slug per record, shared across locales.** Never add a per-locale slug column.
- **No prices are published anywhere** — not in HTML, not in JSON-LD, not in markdown artifacts.
- **`certifications` is nullable and may be empty.** Nothing may fail or emit empty schema nodes when it is empty.
- **Translatable fields are JSON columns**, never `*_id`/`*_en` column pairs.
- **Do not store raw IP addresses.** `inquiries.ip_hash` is `sha256(ip . APP_KEY)` and is used only for rate limiting.
- **`Vary: Accept` is mandatory** on every content-negotiated response, including `304`s.
- **hreflang must be self-referencing, bidirectional, and fully-qualified.**
- Currency/format: dates render in `Asia/Jakarta`; Indonesian date format for `id`, ISO-ish for `en`.

---

## File Structure

| Path | Responsibility |
|---|---|
| `config/app.php` (modify) | `locales` map, `fallback_locale` |
| `app/Http/Middleware/SetLocale.php` | Resolve + validate locale from the route group, set app locale, share with views |
| `app/Support/LocaleUrls.php` | Build hreflang alternates + locale switcher URLs from the current route name |
| `app/Models/{Category,Brand,Product,ProductImage,Inquiry,Page,Setting}.php` | Eloquent models, one per table |
| `app/Enums/{InquiryStatus,CertificationType}.php` | Backed enums |
| `app/Http/Requests/StoreInquiryRequest.php` | RFQ validation + honeypot + rate limit rules |
| `app/Http/Controllers/{Home,Catalog,Product,Brand,Page,Inquiry,Sitemap,AgentDocument}Controller.php` | One controller per route family |
| `app/Services/AgentDocumentBuilder.php` | Renders `/llms.txt`, `/llms-full.txt`, `/katalog.md` from models |
| `app/Support/MarkdownRenderer.php` | Converts rich-text JSON to markdown for agent artifacts |
| `app/Support/WantsMarkdown.php` | Single source of truth for `Accept: text/markdown` preference (q-values) |
| `app/Observers/{Product,Category,Brand,Page}Observer.php` | Purge cache + regenerate agent docs on save |
| `app/Providers/Filament/AdminPanelProvider.php` | Filament panel config |
| `app/Filament/Resources/*` | Admin CRUD, one resource per model |
| `resources/views/layouts/app.blade.php` | Public layout: meta, hreflang, JSON-LD slot, nav, footer |
| `resources/views/components/seo/*.blade.php` | Meta, hreflang, canonical partials |
| `resources/views/components/schema/*.blade.php` | JSON-LD partials |
| `resources/views/pages/*` | One view per public page |
| `resources/views/agent/*` | Markdown templates for agent artifacts |
| `routes/web.php` | Locale-prefixed route groups |
| `routes/console.php` | Scheduler entries |
| `database/migrations/*` | Schema |
| `tests/Feature/*`, `tests/Unit/*` | Pest tests |

---

## Phase 0 — Foundation

### Task 1: Scaffold Laravel, Pest, Tailwind, and the admin panel

**Files:**
- Create: whole Laravel skeleton, `pest.xml`, `app/Providers/Filament/AdminPanelProvider.php`
- Modify: `composer.json`, `package.json`, `vite.config.js`, `.gitignore`

**Interfaces:**
- Consumes: nothing
- Produces: a booting Laravel 13 app with Pest runnable and a reachable `/admin` login page

- [ ] **Step 1: Create the Laravel 13 project in place**

The repo currently contains only `docs/` and `.gitignore`. Create the skeleton into a temp dir and move it in, so the existing git history and `docs/` survive.

```bash
cd /home/rizkydhani/www/projects/mmg-web-v2
composer create-project laravel/laravel:^13.0 /tmp/mmg-skel --no-interaction
shopt -s dotglob
cp -rn /tmp/mmg-skel/* . 2>/dev/null
rm -rf /tmp/mmg-skel
```

- [ ] **Step 2: Verify the app boots and PHP version is adequate**

```bash
php artisan --version
php -r 'echo PHP_VERSION, PHP_EOL;'
```

Expected: `Laravel Framework 13.x` and `8.4.x`. If PHP is below 8.4, stop — Pest 5 requires 8.4.

- [ ] **Step 3: Install Pest and initialize it**

```bash
composer remove phpunit/phpunit --dev --no-interaction
composer require pestphp/pest pestphp/pest-plugin-laravel --dev --with-all-dependencies --no-interaction
./vendor/bin/pest --init
```

- [ ] **Step 4: Verify Pest runs the default tests**

Run: `./vendor/bin/pest`
Expected: PASS, 2 tests. If `tests/Feature/ExampleTest.php` still uses the PHPUnit base class, Pest's `--init` handles conversion; if it fails, delete `tests/Feature/ExampleTest.php` and `tests/Unit/ExampleTest.php` — they are placeholders.

- [ ] **Step 5: Install Filament 5 and create the admin panel**

```bash
composer require filament/filament --no-interaction
php artisan filament:install --panels --no-interaction
```

- [ ] **Step 6: Verify the admin panel responds**

Run: `php artisan route:list --path=admin`
Expected: routes including `filament.admin.auth.login` and `filament.admin.pages.dashboard`.

- [ ] **Step 7: Configure Vite/Tailwind for a build that is committed to the repo**

Modify `vite.config.js` to use the Tailwind 4 Vite plugin (already present in the Laravel 13 skeleton). Confirm `resources/css/app.css` begins with `@import 'tailwindcss';`.

- [ ] **Step 8: Build assets and commit**

```bash
npm install
npm run build
git add -A
git commit -m "chore: scaffold Laravel 13, Pest 5, Filament 5"
```

Note: `.gitignore` from the design spec ignores `/public/build`. For this project the built assets **must** ship, because the host has no Node runtime. Change that line to keep the build output tracked:

```bash
sed -i 's|^/public/build$|# /public/build is committed: the host has no Node runtime|' .gitignore
git add .gitignore public/build
git commit -m "chore: commit built assets (host has no Node runtime)"
```

---

### Task 2: Locale middleware, route groups, and hreflang URL helper

**Files:**
- Create: `config/app.php` (modify), `app/Http/Middleware/SetLocale.php`, `app/Support/LocaleUrls.php`, `tests/Unit/LocaleUrlsTest.php`, `tests/Feature/LocaleRoutingTest.php`
- Modify: `routes/web.php`, `bootstrap/app.php`

**Interfaces:**
- Consumes: nothing
- Produces:
  - `config('app.locales')` → `['id' => '', 'en' => 'en']`
  - `SetLocale` middleware, parameterised: `->middleware('locale:en')`
  - `LocaleUrls::alternates(Request $request): array` → `['id' => 'https://.../produk/x', 'en' => 'https://.../en/products/x']`
  - `LocaleUrls::switchTo(Request $request, string $locale): string`
  - Route naming convention: `{locale}.{name}`, e.g. `id.products.show`, `en.products.show`

- [ ] **Step 1: Write the failing test for locale config and route groups**

`tests/Feature/LocaleRoutingTest.php`:

```php
<?php

it('resolves the indonesian home page at the root', function () {
    $this->get('/')->assertOk();
    expect(app()->getLocale())->toBe('id');
});

it('resolves the english home page under /en', function () {
    $this->get('/en')->assertOk();
    expect(app()->getLocale())->toBe('en');
});

it('exposes both locales in config', function () {
    expect(config('app.locales'))->toBe(['id' => '', 'en' => 'en']);
});

it('404s on an unknown locale prefix', function () {
    $this->get('/de')->assertNotFound();
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `./vendor/bin/pest tests/Feature/LocaleRoutingTest.php`
Expected: FAIL — `config('app.locales')` is null and `/en` 404s.

- [ ] **Step 3: Add the locale map to config**

In `config/app.php`, add above `'locale'`:

```php
'locales' => [
    'id' => '',
    'en' => 'en',
],
```

And ensure:

```php
'locale' => env('APP_LOCALE', 'id'),
'fallback_locale' => env('APP_FALLBACK_LOCALE', 'id'),
```

- [ ] **Step 4: Write the middleware**

`app/Http/Middleware/SetLocale.php`:

```php
<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next, string $locale): Response
    {
        abort_unless(array_key_exists($locale, config('app.locales')), 404);

        app()->setLocale($locale);
        Carbon::setLocale($locale);
        View::share('locale', $locale);

        return $next($request);
    }
}
```

- [ ] **Step 5: Register the middleware alias**

In `bootstrap/app.php`, inside `->withMiddleware(function (Middleware $middleware) {`:

```php
$middleware->alias([
    'locale' => \App\Http\Middleware\SetLocale::class,
]);
```

- [ ] **Step 6: Write the locale URL helper**

`app/Support/LocaleUrls.php`:

```php
<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LocaleUrls
{
    /**
     * Absolute URL of the current page in every locale.
     *
     * Route names are "{locale}.{name}", so the alternate for a given locale
     * is the same route name with the locale segment swapped. This is the only
     * place that mapping happens — callers never build hreflang by hand.
     *
     * @return array<string, string>
     */
    public static function alternates(Request $request): array
    {
        $route = $request->route();

        if (! $route || ! $route->getName()) {
            return [];
        }

        $bare = Str::after($route->getName(), '.');
        $params = $route->parameters();

        $urls = [];

        foreach (array_keys(config('app.locales')) as $locale) {
            $name = "{$locale}.{$bare}";

            if (app('router')->has($name)) {
                $urls[$locale] = route($name, $params);
            }
        }

        return $urls;
    }

    public static function switchTo(Request $request, string $locale): string
    {
        return self::alternates($request)[$locale] ?? route("{$locale}.home");
    }
}
```

- [ ] **Step 7: Write the locale-prefixed route groups**

Replace `routes/web.php`:

```php
<?php

use Illuminate\Support\Facades\Route;

foreach (config('app.locales') as $locale => $prefix) {
    Route::prefix($prefix)
        ->middleware("locale:{$locale}")
        ->name("{$locale}.")
        ->group(function () {
            Route::view('/', 'pages.home')->name('home');
        });
}
```

- [ ] **Step 8: Create the minimal home view**

`resources/views/pages/home.blade.php`:

```blade
<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <title>Medquest Mitra Global</title>
</head>
<body>
    <h1>Medquest Mitra Global</h1>
</body>
</html>
```

- [ ] **Step 9: Write the failing test for hreflang alternates**

`tests/Unit/LocaleUrlsTest.php`:

```php
<?php

use App\Support\LocaleUrls;

it('builds alternates for both locales from a route name', function () {
    $request = \Illuminate\Http\Request::create('/en');
    $route = new \Illuminate\Routing\Route(['GET'], 'en', fn () => '');
    $route->name('en.home');
    $request->setRouteResolver(fn () => $route);

    $alternates = LocaleUrls::alternates($request);

    expect($alternates)->toHaveKeys(['id', 'en'])
        ->and($alternates['id'])->toEndWith('/')
        ->and($alternates['en'])->toContain('/en');
});
```

- [ ] **Step 10: Run all tests and commit**

Run: `./vendor/bin/pest`
Expected: PASS.

```bash
git add -A
git commit -m "feat(i18n): locale middleware, prefixed route groups, hreflang helper"
```

---

## Phase 1 — Data Layer

### Task 3: Migrations, enums, and models

**Files:**
- Create: `app/Enums/InquiryStatus.php`, `app/Enums/CertificationType.php`, `database/migrations/*_create_catalog_tables.php`, `database/migrations/*_create_inquiries_table.php`, `database/migrations/*_create_pages_and_settings_tables.php`, `app/Models/{Category,Brand,Product,ProductImage,Inquiry,Page,Setting}.php`, `database/factories/*`, `tests/Feature/CatalogSchemaTest.php`
- Modify: `database/seeders/DatabaseSeeder.php`

**Interfaces:**
- Consumes: nothing
- Produces: models with the following public surface —
  - All of `Category`, `Brand`, `Product`, `Page` use `Spatie\Translatable\HasTranslations`
  - `Product::$translatable = ['name', 'short_description', 'description']`
  - `Product::coverImage(): ?ProductImage`
  - `Product::scopePublished($query)`, `Brand::scopePublished($query)`, `Category::scopePublished($query)`, `Page::scopePublished($query)`
  - `ProductImage::$translatable = ['alt']`
  - `Setting::get(string $key, mixed $default = null): mixed` and `Setting::set(string $key, mixed $value): void`
  - `InquiryStatus` enum cases: `New`, `Read`, `Replied`

- [ ] **Step 1: Write the failing schema test**

`tests/Feature/CatalogSchemaTest.php`:

```php
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
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `./vendor/bin/pest tests/Feature/CatalogSchemaTest.php`
Expected: FAIL — classes and tables do not exist.

- [ ] **Step 3: Install the two supporting packages**

```bash
composer require spatie/laravel-translatable spatie/laravel-permission --no-interaction
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan vendor:publish --provider="Spatie\Translatable\TranslatableServiceProvider"
```

- [ ] **Step 4: Create the enums**

`app/Enums/InquiryStatus.php`:

```php
<?php

namespace App\Enums;

enum InquiryStatus: string
{
    case New = 'new';
    case Read = 'read';
    case Replied = 'replied';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Baru',
            self::Read => 'Dibaca',
            self::Replied => 'Dibalas',
        };
    }
}
```

`app/Enums/CertificationType.php`:

```php
<?php

namespace App\Enums;

enum CertificationType: string
{
    case IzinEdar = 'izin_edar';
    case Akl = 'akl';
    case Iso13485 = 'iso_13485';
    case Iso9001 = 'iso_9001';
    case DistributorLicence = 'distributor_licence';
    case Other = 'other';
}
```

- [ ] **Step 5: Write the catalog migration**

`database/migrations/2026_09_18_000001_create_catalog_tables.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('slug')->unique();
            $table->json('name');
            $table->json('description')->nullable();
            $table->string('image')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->index(['is_published', 'sort_order']);
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('logo')->nullable();
            $table->json('description')->nullable();
            $table->json('certifications')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->index(['is_published', 'sort_order']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('slug')->unique();
            $table->string('sku')->nullable();
            $table->json('name');
            $table->json('short_description')->nullable();
            $table->json('description')->nullable();
            $table->json('specs')->nullable();
            $table->json('certifications')->nullable();
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_published', 'sort_order']);
            $table->index(['category_id', 'is_published']);
            $table->index(['brand_id', 'is_published']);
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_cover')->default(false);
            $table->string('path');
            $table->json('alt')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'sort_order']);
        });

        // Exactly one cover image per product, enforced by the database.
        // MySQL/MariaDB have no partial unique indexes, so a stored generated
        // column collapses the cover row to its product_id and leaves gallery
        // rows NULL (multiple NULLs are permitted in a unique index).
        Schema::table('product_images', function (Blueprint $table) {
            $table->unsignedBigInteger('cover_key')
                ->nullable()
                ->storedAs('IF(is_cover, product_id, NULL)');
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->unique('cover_key', 'uniq_cover_per_product');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('products');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('categories');
    }
};
```

- [ ] **Step 6: Write the inquiries, pages, and settings migration**

`database/migrations/2026_09_18_000002_create_inquiries_pages_settings_tables.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('company')->nullable();
            $table->string('email');
            $table->string('phone');
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->text('message');
            $table->string('locale', 5)->default('id');
            $table->string('status')->default('new');
            // sha256(ip . APP_KEY) — rate limiting only. Raw IPs are never stored.
            $table->string('ip_hash', 64)->nullable();
            $table->string('source_path')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('ip_hash');
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('title');
            $table->json('body')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->json('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('inquiries');
    }
};
```

- [ ] **Step 7: Run the migrations**

```bash
php artisan migrate
```

Expected: all migrations run without error. If the `storedAs` column fails, the SQLite/MySQL grammar is at fault — check `php artisan migrate --pretend` for the emitted DDL.

- [ ] **Step 8: Write the models**

`app/Models/Product.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Product extends Model
{
    use HasFactory;
    use HasTranslations;

    public array $translatable = ['name', 'short_description', 'description'];

    protected $fillable = [
        'category_id', 'brand_id', 'slug', 'sku',
        'name', 'short_description', 'description',
        'specs', 'certifications',
        'is_published', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'specs' => 'array',
            'certifications' => 'array',
            'is_published' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function coverImage(): ?ProductImage
    {
        return $this->images->firstWhere('is_cover', true)
            ?? $this->images->first();
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
}
```

`app/Models/Category.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Category extends Model
{
    use HasFactory;
    use HasTranslations;

    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'parent_id', 'slug', 'name', 'description',
        'image', 'sort_order', 'is_published',
    ];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
}
```

`app/Models/Brand.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Brand extends Model
{
    use HasFactory;
    use HasTranslations;

    public array $translatable = ['description'];

    protected $fillable = [
        'slug', 'name', 'logo', 'description', 'certifications',
        'sort_order', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'certifications' => 'array',
            'is_published' => 'boolean',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
}
```

`app/Models/ProductImage.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class ProductImage extends Model
{
    use HasFactory;
    use HasTranslations;

    public array $translatable = ['alt'];

    protected $fillable = ['product_id', 'is_cover', 'path', 'alt', 'sort_order'];

    protected function casts(): array
    {
        return ['is_cover' => 'boolean'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
```

`app/Models/Inquiry.php`:

```php
<?php

namespace App\Models;

use App\Enums\InquiryStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inquiry extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'company', 'email', 'phone', 'product_id',
        'message', 'locale', 'status', 'ip_hash', 'source_path',
    ];

    protected function casts(): array
    {
        return ['status' => InquiryStatus::class];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
```

`app/Models/Page.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Page extends Model
{
    use HasFactory;
    use HasTranslations;

    public array $translatable = ['title', 'body'];

    protected $fillable = ['slug', 'title', 'body', 'is_published'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
}
```

`app/Models/Setting.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return ['value' => 'array'];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::query()->find($key)?->value ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
```

- [ ] **Step 9: Write the factories**

`database/factories/ProductFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'category_id' => Category::factory(),
            'slug' => Str::slug($name).'-'.Str::random(5),
            'name' => ['id' => $name, 'en' => $name],
            'short_description' => ['id' => fake()->sentence(), 'en' => fake()->sentence()],
            'description' => ['id' => fake()->paragraph(), 'en' => fake()->paragraph()],
            'specs' => ['Ukuran' => '10x15cm'],
            'is_published' => true,
        ];
    }
}
```

`database/factories/CategoryFactory.php`:

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'slug' => Str::slug($name).'-'.Str::random(5),
            'name' => ['id' => $name, 'en' => $name],
            'is_published' => true,
        ];
    }
}
```

`database/factories/BrandFactory.php`:

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BrandFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'slug' => Str::slug($name).'-'.Str::random(5),
            'name' => $name,
            'is_published' => true,
        ];
    }
}
```

- [ ] **Step 10: Run the tests and commit**

Run: `./vendor/bin/pest`
Expected: PASS.

```bash
git add -A
git commit -m "feat(db): catalog, inquiry, page, settings schema with models and factories"
```

---

### Task 4: Enforce one cover image per product

**Files:**
- Create: `tests/Feature/ProductCoverImageTest.php`
- Modify: `app/Models/ProductImage.php`

**Interfaces:**
- Consumes: `product_images` table with the `cover_key` generated column and `uniq_cover_per_product` index
- Produces: `ProductImage::booted()` hook that unsets sibling covers in the same transaction

- [ ] **Step 1: Write the failing test**

`tests/Feature/ProductCoverImageTest.php`:

```php
<?php

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\QueryException;

it('allows exactly one cover per product', function () {
    $product = Product::factory()->create();

    ProductImage::create(['product_id' => $product->id, 'path' => 'a.jpg', 'is_cover' => true]);

    expect(fn () => ProductImage::create([
        'product_id' => $product->id, 'path' => 'b.jpg', 'is_cover' => true,
    ]))->toThrow(QueryException::class);
});

it('allows many gallery images alongside one cover', function () {
    $product = Product::factory()->create();

    ProductImage::create(['product_id' => $product->id, 'path' => 'a.jpg', 'is_cover' => true]);

    foreach (range(1, 5) as $i) {
        ProductImage::create(['product_id' => $product->id, 'path' => "g{$i}.jpg", 'is_cover' => false]);
    }

    expect($product->images()->count())->toBe(6);
});

it('demotes the previous cover when a new cover is set through the model', function () {
    $product = Product::factory()->create();

    $first = ProductImage::create(['product_id' => $product->id, 'path' => 'a.jpg', 'is_cover' => true]);
    $second = ProductImage::create(['product_id' => $product->id, 'path' => 'b.jpg', 'is_cover' => false]);

    $second->update(['is_cover' => true]);

    expect($first->fresh()->is_cover)->toBeFalse()
        ->and($second->fresh()->is_cover)->toBeTrue();
});

it('falls back to the first image when no cover is flagged', function () {
    $product = Product::factory()->create();

    ProductImage::create(['product_id' => $product->id, 'path' => 'a.jpg', 'is_cover' => false]);

    expect($product->fresh()->coverImage())->not->toBeNull();
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `./vendor/bin/pest tests/Feature/ProductCoverImageTest.php`
Expected: FAIL on the third and fourth tests. The first two should already pass — the DB constraint from Task 3 is doing its job.

- [ ] **Step 3: Add the model hook**

In `app/Models/ProductImage.php`, add inside the class:

```php
    protected static function booted(): void
    {
        static::saving(function (self $image) {
            if (! $image->is_cover) {
                return;
            }

            // Demote any existing cover for this product first, otherwise the
            // uniq_cover_per_product index rejects the write. The generated
            // column is the real guarantee; this keeps the common path from
            // surfacing a constraint violation to the editor.
            static::query()
                ->where('product_id', $image->product_id)
                ->whereKeyNot($image->getKey())
                ->where('is_cover', true)
                ->update(['is_cover' => false]);
        });
    }
```

- [ ] **Step 4: Run the tests to verify they pass**

Run: `./vendor/bin/pest tests/Feature/ProductCoverImageTest.php`
Expected: PASS, 4 tests.

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "feat(media): enforce one cover image per product at DB and model level"
```

---

## Phase 2 — Admin Panel

### Task 5: Roles, permissions, and panel access

**Files:**
- Create: `database/seeders/RoleSeeder.php`, `app/Filament/Resources/UserResource.php`, `tests/Feature/AdminAccessTest.php`
- Modify: `app/Models/User.php`, `app/Providers/Filament/AdminPanelProvider.php`, `database/seeders/DatabaseSeeder.php`

**Interfaces:**
- Consumes: `spatie/laravel-permission`
- Produces: roles `admin` and `editor`; `User::canAccessPanel(Panel $panel): bool`

- [ ] **Step 1: Write the failing test**

`tests/Feature/AdminAccessTest.php`:

```php
<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

it('creates the admin and editor roles', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    expect(Role::pluck('name')->all())->toContain('admin', 'editor');
});

it('lets an admin reach the panel', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user)->get('/admin')->assertOk();
});

it('lets an editor reach the panel', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('editor');

    $this->actingAs($user)->get('/admin')->assertOk();
});

it('blocks a user with no role', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/admin')->assertForbidden();
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `./vendor/bin/pest tests/Feature/AdminAccessTest.php`
Expected: FAIL — roles do not exist and the panel allows anyone authenticated.

- [ ] **Step 3: Write the role seeder**

`database/seeders/RoleSeeder.php`:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::findOrCreate('admin');
        Role::findOrCreate('editor');
    }
}
```

Register it in `database/seeders/DatabaseSeeder.php`:

```php
public function run(): void
{
    $this->call([RoleSeeder::class]);
}
```

- [ ] **Step 4: Gate panel access**

In `app/Models/User.php`, add `use Spatie\Permission\Traits\HasRoles;` to the class and the trait to the class body, then add:

```php
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    // ...
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole(['admin', 'editor']);
    }
}
```

- [ ] **Step 5: Run the tests to verify they pass**

Run: `./vendor/bin/pest tests/Feature/AdminAccessTest.php`
Expected: PASS, 4 tests.

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "feat(admin): roles and panel access control"
```

---

### Task 6: Product admin resource

**Files:**
- Create: `app/Filament/Resources/ProductResource.php`, `app/Filament/Resources/ProductResource/Pages/{ListProducts,CreateProduct,EditProduct}.php`, `app/Filament/Resources/ProductResource/RelationManagers/ImagesRelationManager.php`, `tests/Feature/Filament/ProductResourceTest.php`

**Interfaces:**
- Consumes: `Product`, `ProductImage`, `Category`, `Brand`
- Produces: `/admin/products` CRUD with ID/EN tabs, a specs repeater, a certifications repeater, and an images relation manager with an `is_cover` toggle

- [ ] **Step 1: Generate the resource and relation manager**

```bash
php artisan make:filament-resource Product --generate --no-interaction
php artisan make:filament-relation-manager ProductResource images path --no-interaction
```

- [ ] **Step 2: Write the failing test**

`tests/Feature/Filament/ProductResourceTest.php`:

```php
<?php

use App\Filament\Resources\ProductResource\Pages\CreateProduct;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
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
            'category_id' => \App\Models\Category::factory()->create()->id,
            'is_published' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $product = Product::where('slug', 'enema-set')->first();

    expect($product)->not->toBeNull()
        ->and($product->getTranslation('name', 'en'))->toBe('Enema Set');
});
```

- [ ] **Step 3: Run the test to verify it fails**

Run: `./vendor/bin/pest tests/Feature/Filament/ProductResourceTest.php`
Expected: FAIL — the generated form has flat fields, not translatable tabs.

- [ ] **Step 4: Configure the form schema**

In `app/Filament/Resources/ProductResource.php`, set the form schema. Translatable fields use `Tabs` with a tab per locale; the specs and certifications repeaters use the JSON column shapes defined in the spec.

```php
use App\Enums\CertificationType;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

public static function form(Schema $schema): Schema
{
    return $schema->components([
        Tabs::make('Translations')->tabs([
            Tabs\Tab::make('Indonesia')->schema([
                TextInput::make('name.id')->label('Nama')->required(),
                Textarea::make('short_description.id')
                    ->label('Ringkasan (40–60 kata)')
                    ->helperText(fn (?string $state) => $state
                        ? str_word_count(strip_tags($state)).' kata'
                        : 'Kosong')
                    ->rows(3),
                Textarea::make('description.id')->label('Deskripsi')->rows(8),
            ]),
            Tabs\Tab::make('English')->schema([
                TextInput::make('name.en')->label('Name')->required(),
                Textarea::make('short_description.en')
                    ->label('Summary (40–60 words)')
                    ->rows(3),
                Textarea::make('description.en')->label('Description')->rows(8),
            ]),
        ])->columnSpanFull(),

        Select::make('category_id')
            ->relationship('category', 'slug')
            ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
            ->required(),

        Select::make('brand_id')
            ->relationship('brand', 'name')
            ->searchable(),

        TextInput::make('slug')->required()->unique(ignoreRecord: true),
        TextInput::make('sku'),

        Repeater::make('specs')
            ->label('Spesifikasi')
            ->schema([
                TextInput::make('key')->label('Nama')->required(),
                TextInput::make('value')->label('Nilai')->required(),
            ])
            ->formatStateUsing(fn ($state) => collect($state ?? [])
                ->map(fn ($v, $k) => ['key' => $k, 'value' => $v])
                ->values()
                ->all())
            ->dehydrateStateUsing(fn ($state) => collect($state ?? [])
                ->filter(fn ($row) => filled($row['key'] ?? null))
                ->mapWithKeys(fn ($row) => [$row['key'] => $row['value']])
                ->all())
            ->columnSpanFull(),

        Repeater::make('certifications')
            ->label('Sertifikasi / Izin Edar')
            ->helperText('Kosongkan jika belum ada data yang boleh dipublikasikan.')
            ->schema([
                Select::make('type')->options(
                    collect(CertificationType::cases())
                        ->mapWithKeys(fn ($c) => [$c->value => $c->name])
                        ->all()
                )->required(),
                TextInput::make('number'),
                TextInput::make('issuer'),
                \Filament\Forms\Components\DatePicker::make('valid_until'),
                TextInput::make('url')->url(),
            ])
            ->columnSpanFull(),

        Toggle::make('is_published')->label('Terbitkan'),
        TextInput::make('sort_order')->numeric()->default(0),
    ]);
}
```

- [ ] **Step 5: Configure the table**

In the same file, `table()` should list `name`, `category.name`, `brand.name`, `is_published`, with a `SelectFilter` for category and brand and a `TernaryFilter` for published state. All searches operate on `name->id` via `->searchable(['name->id', 'name->en'])`.

- [ ] **Step 6: Configure the images relation manager**

In `ImagesRelationManager`, the form gets:

```php
\Filament\Forms\Components\FileUpload::make('path')
    ->image()
    ->imageEditor()
    ->directory('products')
    ->required(),
\Filament\Forms\Components\Toggle::make('is_cover')
    ->label('Foto utama')
    ->helperText('Menyalakan ini akan mematikan foto utama lainnya.'),
TextInput::make('alt.id')->label('Alt text (ID)'),
TextInput::make('alt.en')->label('Alt text (EN)'),
TextInput::make('sort_order')->numeric()->default(0),
```

Table columns: image preview, `is_cover` icon column, `sort_order`, reorderable via `->reorderable('sort_order')`.

- [ ] **Step 7: Run the tests to verify they pass**

Run: `./vendor/bin/pest tests/Feature/Filament/ProductResourceTest.php`
Expected: PASS.

- [ ] **Step 8: Manually verify the resource in a browser**

```bash
php artisan serve
```

Visit `/admin/products`, create a product with images, flag a cover, flag a second image as cover, and confirm the first is demoted. Stop the server.

- [ ] **Step 9: Commit**

```bash
git add -A
git commit -m "feat(admin): product resource with translations, specs, certifications, images"
```

---

### Task 7: Category, Brand, and Page admin resources

**Files:**
- Create: `app/Filament/Resources/{CategoryResource,BrandResource,PageResource}.php` + their page classes, `tests/Feature/Filament/ContentResourcesTest.php`

**Interfaces:**
- Consumes: `Category`, `Brand`, `Page`
- Produces: `/admin/categories`, `/admin/brands`, `/admin/pages`

- [ ] **Step 1: Generate the resources**

```bash
php artisan make:filament-resource Category --generate --no-interaction
php artisan make:filament-resource Brand --generate --no-interaction
php artisan make:filament-resource Page --generate --no-interaction
```

- [ ] **Step 2: Write the failing test**

`tests/Feature/Filament/ContentResourcesTest.php`:

```php
<?php

use App\Filament\Resources\CategoryResource\Pages\CreateCategory;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->actingAs($this->admin);
});

it('creates a category with both locales', function () {
    Livewire::test(CreateCategory::class)
        ->fillForm([
            'slug' => 'alat-kesehatan',
            'name' => ['id' => 'Alat Kesehatan', 'en' => 'Medical Devices'],
            'is_published' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(\App\Models\Category::where('slug', 'alat-kesehatan')->exists())->toBeTrue();
});
```

- [ ] **Step 3: Run the test to verify it fails**

Run: `./vendor/bin/pest tests/Feature/Filament/ContentResourcesTest.php`
Expected: FAIL — generated form has a flat `name` field.

- [ ] **Step 4: Apply the same translatable Tabs pattern to all three resources**

Use the `Tabs` structure from Task 6 Step 4 for `Category` (`name`, `description`), `Brand` (`description` only — `name` is a plain string), and `Page` (`title`, `body`).

`CategoryResource` additionally gets `Select::make('parent_id')->relationship('parent', 'slug')` and a reorderable table.

- [ ] **Step 5: Run the tests to verify they pass**

Run: `./vendor/bin/pest tests/Feature/Filament/ContentResourcesTest.php`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "feat(admin): category, brand, and page resources"
```

---

### Task 8: Inquiry inbox and email notification

**Files:**
- Create: `app/Mail/InquiryReceived.php`, `resources/views/mail/inquiry-received.blade.php`, `app/Filament/Resources/InquiryResource.php` + page classes, `app/Filament/Resources/InquiryResource/Widgets/NewInquiryCount.php`, `tests/Feature/InquiryAdminTest.php`

**Interfaces:**
- Consumes: `Inquiry`, `InquiryStatus`, `Setting::get('contact_email')`
- Produces: `/admin/inquiries` with a `new → read → replied` workflow, plus `InquiryReceived` mailable

- [ ] **Step 1: Write the failing test**

`tests/Feature/InquiryAdminTest.php`:

```php
<?php

use App\Enums\InquiryStatus;
use App\Models\Inquiry;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->actingAs($this->admin);
});

it('lists inquiries with status filter', function () {
    Inquiry::factory()->create(['status' => InquiryStatus::New]);

    Livewire::test(\App\Filament\Resources\InquiryResource\Pages\ListInquiries::class)
        ->assertCanSeeTableRecords(Inquiry::all());
});

it('marks an inquiry as replied', function () {
    $inquiry = Inquiry::factory()->create(['status' => InquiryStatus::New]);

    Livewire::test(\App\Filament\Resources\InquiryResource\Pages\ListInquiries::class)
        ->callAction(TestAction::make('markReplied')->table($inquiry));

    expect($inquiry->fresh()->status)->toBe(InquiryStatus::Replied);
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `./vendor/bin/pest tests/Feature/InquiryAdminTest.php`
Expected: FAIL — resource and factory do not exist.

- [ ] **Step 3: Create the factory**

`database/factories/InquiryFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Enums\InquiryStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class InquiryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'company' => fake()->company(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'message' => fake()->paragraph(),
            'locale' => 'id',
            'status' => InquiryStatus::New,
            'ip_hash' => hash('sha256', fake()->ipv4().config('app.key')),
            'source_path' => '/produk/contoh',
        ];
    }
}
```

- [ ] **Step 4: Create the mailable**

```bash
php artisan make:mail InquiryReceived --no-interaction
```

`app/Mail/InquiryReceived.php`:

```php
<?php

namespace App\Mail;

use App\Models\Inquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InquiryReceived extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Inquiry $inquiry) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Permintaan Penawaran baru — '.$this->inquiry->name,
            replyTo: [$this->inquiry->email],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.inquiry-received');
    }
}
```

`resources/views/mail/inquiry-received.blade.php`:

```blade
<h1>Permintaan Penawaran Baru</h1>

<p><strong>Nama:</strong> {{ $inquiry->name }}</p>
<p><strong>Perusahaan:</strong> {{ $inquiry->company ?: '-' }}</p>
<p><strong>Email:</strong> {{ $inquiry->email }}</p>
<p><strong>Telepon:</strong> {{ $inquiry->phone }}</p>
@if ($inquiry->product)
    <p><strong>Produk:</strong> {{ $inquiry->product->name }}</p>
@endif
<p><strong>Pesan:</strong></p>
<p>{!! nl2br(e($inquiry->message)) !!}</p>

<p>Lihat di admin: {{ url('/admin/inquiries/'.$inquiry->id) }}</p>
```

- [ ] **Step 5: Create the resource with status actions**

```bash
php artisan make:filament-resource Inquiry --no-interaction
```

Table columns: `name`, `company`, `email`, `phone`, `product.name`, `status` badge, `created_at`. Filters: status select, date range. Form: read-only display of the inquiry. Add table actions:

```php
\Filament\Actions\Action::make('markRead')
    ->label('Tandai dibaca')
    ->visible(fn (Inquiry $record) => $record->status === InquiryStatus::New)
    ->action(fn (Inquiry $record) => $record->update(['status' => InquiryStatus::Read])),

\Filament\Actions\Action::make('markReplied')
    ->label('Tandai dibalas')
    ->visible(fn (Inquiry $record) => $record->status !== InquiryStatus::Replied)
    ->action(fn (Inquiry $record) => $record->update(['status' => InquiryStatus::Replied])),
```

Disable record creation (`canCreate(): bool { return false; }`).

- [ ] **Step 6: Add the dashboard widget**

```bash
php artisan make:filament-widget NewInquiryCount --stats-overview --no-interaction
```

Query: `Inquiry::where('status', InquiryStatus::New)->count()`.

- [ ] **Step 7: Run the tests to verify they pass**

Run: `./vendor/bin/pest tests/Feature/InquiryAdminTest.php`
Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add -A
git commit -m "feat(admin): inquiry inbox with status workflow and email notification"
```

---

### Task 9: Settings page

**Files:**
- Create: `app/Filament/Pages/ManageSettings.php`, `resources/views/filament/pages/manage-settings.blade.php`, `tests/Feature/SettingsTest.php`

**Interfaces:**
- Consumes: `Setting`
- Produces: `/admin/settings` writing keys `company_name`, `contact_email`, `contact_phone`, `whatsapp`, `address`, `default_meta_title`, `default_meta_description`, `socials` (array)

- [ ] **Step 1: Write the failing test**

`tests/Feature/SettingsTest.php`:

```php
<?php

use App\Models\Setting;

it('round-trips a json setting value', function () {
    Setting::set('socials', ['linkedin' => 'https://linkedin.com/company/x']);

    expect(Setting::get('socials'))->toBe(['linkedin' => 'https://linkedin.com/company/x']);
});

it('returns the default for a missing key', function () {
    expect(Setting::get('nope', 'fallback'))->toBe('fallback');
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `./vendor/bin/pest tests/Feature/SettingsTest.php`
Expected: FAIL — `settings` table exists from Task 3 but the model's casts may not round-trip. If it already passes, proceed; this task's real work is the admin page.

- [ ] **Step 3: Create the Filament settings page**

```bash
php artisan make:filament-page ManageSettings --no-interaction
```

In `ManageSettings`, extend `Filament\Pages\Page`, implement `HasForms`, and define a form with `TextInput`/`Textarea`/`Repeater` fields for each setting key. `mount()` loads from `Setting::get()`; `save()` writes back via `Setting::set()`. Restrict access with `canAccess()` requiring the `admin` role.

- [ ] **Step 4: Verify in the browser**

```bash
php artisan serve
```

Visit `/admin/settings`, save values, reload, confirm they persist. Stop the server.

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "feat(admin): settings page for company and contact details"
```

---

## Phase 3 — Public Site

> **PAUSED pending wireframes (ruling R1).** The home-page wireframe has been
> received and is transcribed below; the remaining page wireframes are still
> outstanding, so Tasks 10–15 must not be dispatched until they arrive. When
> they do, correct each task's view code against them before dispatch.
>
> ### Home page layout (from `wireframe_home.png`)
>
> 1. **Header** — 3-column layout: logo (left), menu (centered), CTA button (right).
> 2. **Hero** — heading, subtext, CTA, background image.
> 3. **Facilities marquee** — a CSS marquee of healthcare facility types
>    (Rumah Sakit, Klinik, Puskesmas, Laboratorium, Apotek, …). Static list in
>    `config/site.php` under `facility_types`; no database table (human answer 1c).
> 4. **Principal marquee** — a CSS marquee of the brands distributed for. Reuses
>    the `brands` table, displayed under the label "Principal" (human answer 2a).
> 5. **Products** — grid 3×2 (six items), sorted by latest created, with a
>    full-width CTA below linking to the catalog page (human answer 3a).
> 6. **Contact Us** — 2-column layout: map embed (left), contact details (right)
>    (human answer 4a).
> 7. **Footer** — 2 rows. Row 1: 2-column, logo + company address (left) and nav
>    links (right). Row 2: 1-column, copyright icon + current year + company name,
>    with year and company name separated by "-".

### Task 10: Public layout, navigation, and home page

**Files:**
- Create: `resources/views/layouts/app.blade.php`, `resources/views/partials/{nav,footer}.blade.php`, `app/Http/Controllers/HomeController.php`, `resources/views/pages/home.blade.php` (replace), `tests/Feature/HomePageTest.php`
- Modify: `routes/web.php`, `resources/css/app.css`

**Interfaces:**
- Consumes: `LocaleUrls`, `Setting`, `Product::published()`
- Produces: `layouts/app.blade.php` with slots `@yield('title')`, `@yield('meta')`, `@yield('schema')`, `@yield('content')`

- [ ] **Step 1: Write the failing test**

`tests/Feature/HomePageTest.php`:

```php
<?php

use App\Models\Product;

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
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `./vendor/bin/pest tests/Feature/HomePageTest.php`
Expected: FAIL — the placeholder view has no featured products and the layout does not exist.

- [ ] **Step 3: Write the layout**

`resources/views/layouts/app.blade.php`:

```blade
<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', Setting::get('default_meta_title', 'Medquest Mitra Global'))</title>
    <meta name="description" content="@yield('meta_description', Setting::get('default_meta_description'))">
    @include('components.seo.hreflang')
    @include('components.seo.canonical')
    @stack('schema')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased text-slate-900">
    @include('partials.nav')
    <main>@yield('content')</main>
    @include('partials.footer')
</body>
</html>
```

- [ ] **Step 4: Write the hreflang and canonical components**

`resources/views/components/seo/hreflang.blade.php`:

```blade
@foreach (\App\Support\LocaleUrls::alternates(request()) as $altLocale => $url)
    <link rel="alternate" hreflang="{{ $altLocale }}" href="{{ $url }}">
@endforeach
<link rel="alternate" hreflang="x-default" href="{{ \App\Support\LocaleUrls::switchTo(request(), 'id') }}">
```

`resources/views/components/seo/canonical.blade.php`:

```blade
<link rel="canonical" href="{{ url()->current() }}">
```

- [ ] **Step 5: Write the nav with a locale switcher**

`resources/views/partials/nav.blade.php` — links to catalog, brands, about, contact using `route("{$locale}.products.index")` etc., plus:

```blade
<a href="{{ \App\Support\LocaleUrls::switchTo(request(), $locale === 'id' ? 'en' : 'id') }}"
   rel="alternate"
   hreflang="{{ $locale === 'id' ? 'en' : 'id' }}">
    {{ $locale === 'id' ? 'EN' : 'ID' }}
</a>
```

- [ ] **Step 6: Write the controller and home view**

`app/Http/Controllers/HomeController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('pages.home', [
            'products' => Product::published()
                ->with(['category', 'brand', 'images'])
                ->orderByDesc('created_at')
                ->take(6)
                ->get(),
            'categories' => Category::published()->orderBy('sort_order')->take(6)->get(),
            'principals' => Brand::published()->orderBy('sort_order')->take(12)->get(),
            'facilities' => config('site.facility_types'),
        ]);
    }
}
```

`resources/views/pages/home.blade.php` extends the layout, renders a hero, the category grid, the featured product grid, the brand strip, and an RFQ CTA. Each product card includes a 40–60 word excerpt from `short_description` and links to `route("{$locale}.products.show", $product)`.

- [ ] **Step 7: Point the route at the controller**

In `routes/web.php`, replace the `Route::view` line with:

```php
Route::get('/', \App\Http\Controllers\HomeController::class)->name('home');
```

- [ ] **Step 8: Run the tests to verify they pass**

Run: `./vendor/bin/pest tests/Feature/HomePageTest.php`
Expected: PASS.

- [ ] **Step 9: Commit**

```bash
git add -A
git commit -m "feat(public): layout, navigation, locale switcher, and home page"
```

---

### Task 11: Catalog index with filters and search

**Files:**
- Create: `app/Http/Controllers/CatalogController.php`, `resources/views/pages/catalog.blade.php`, `resources/views/partials/product-card.blade.php`, `database/migrations/*_add_product_fulltext_index.php`, `tests/Feature/CatalogTest.php`
- Modify: `routes/web.php`

**Interfaces:**
- Consumes: `Product::published()`, `Category::published()`, `Brand::published()`
- Produces: `GET /produk` and `GET /en/products` accepting `?category=`, `?brand=`, `?q=`, `?page=`

- [ ] **Step 1: Write the failing test**

`tests/Feature/CatalogTest.php`:

```php
<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;

it('lists only published products', function () {
    Product::factory()->create(['is_published' => true, 'name' => ['id' => 'Terbit', 'en' => 'Published']]);
    Product::factory()->create(['is_published' => false, 'name' => ['id' => 'Draf', 'en' => 'Draft']]);

    $this->get('/produk')
        ->assertSee('Terbit')
        ->assertDontSee('Draf');
});

it('filters by category', function () {
    $a = Category::factory()->create(['name' => ['id' => 'Kategori A', 'en' => 'Category A']]);
    $b = Category::factory()->create(['name' => ['id' => 'Kategori B', 'en' => 'Category B']]);

    Product::factory()->for($a)->create(['name' => ['id' => 'Produk A', 'en' => 'Product A']]);
    Product::factory()->for($b)->create(['name' => ['id' => 'Produk B', 'en' => 'Product B']]);

    $this->get('/produk?category='.$a->slug)
        ->assertSee('Produk A')
        ->assertDontSee('Produk B');
});

it('filters by brand', function () {
    $brand = Brand::factory()->create(['name' => 'OneMed']);
    Product::factory()->for($brand)->create(['name' => ['id' => 'Produk Bermerek', 'en' => 'Branded']]);
    Product::factory()->create(['name' => ['id' => 'Tanpa Merek', 'en' => 'Unbranded']]);

    $this->get('/produk?brand='.$brand->slug)
        ->assertSee('Produk Bermerek')
        ->assertDontSee('Tanpa Merek');
});

it('searches by name', function () {
    Product::factory()->create(['name' => ['id' => 'Enema Set Steril', 'en' => 'Sterile Enema Set']]);
    Product::factory()->create(['name' => ['id' => 'Kursi Roda', 'en' => 'Wheelchair']]);

    $this->get('/produk?q=Enema')
        ->assertSee('Enema Set Steril')
        ->assertDontSee('Kursi Roda');
});

it('serves the english catalog under /en/products', function () {
    Product::factory()->create(['name' => ['id' => 'Nama Indonesia', 'en' => 'English Name']]);

    $this->get('/en/products')
        ->assertOk()
        ->assertSee('English Name')
        ->assertDontSee('Nama Indonesia');
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `./vendor/bin/pest tests/Feature/CatalogTest.php`
Expected: FAIL — the route does not exist.

- [ ] **Step 3: Add the FULLTEXT index migration**

`database/migrations/2026_09_18_000003_add_product_fulltext_index.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Generated columns give a stable, indexable text target for both
        // locales. JSON path extraction is not directly FULLTEXT-indexable.
        DB::statement('ALTER TABLE products ADD COLUMN name_id_text TEXT
            GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(name, "$.id"))) STORED');
        DB::statement('ALTER TABLE products ADD COLUMN name_en_text TEXT
            GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(name, "$.en"))) STORED');

        DB::statement('ALTER TABLE products ADD FULLTEXT products_fulltext (name_id_text, name_en_text)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE products DROP INDEX products_fulltext');
        DB::statement('ALTER TABLE products DROP COLUMN name_id_text');
        DB::statement('ALTER TABLE products DROP COLUMN name_en_text');
    }
};
```

- [ ] **Step 4: Run the migration**

```bash
php artisan migrate
```

Expected: success. If MariaDB rejects `JSON_UNQUOTE(JSON_EXTRACT(...))` in a generated column, replace with `name->>"$.id"` syntax and re-run.

- [ ] **Step 5: Write the controller**

`app/Http/Controllers/CatalogController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function __invoke(Request $request): View
    {
        $products = Product::query()
            ->published()
            ->with(['category', 'brand', 'images'])
            ->when($request->string('category')->toString(), fn ($q, $slug) => $q
                ->whereHas('category', fn ($c) => $c->where('slug', $slug)))
            ->when($request->string('brand')->toString(), fn ($q, $slug) => $q
                ->whereHas('brand', fn ($b) => $b->where('slug', $slug)))
            ->when($request->string('q')->toString(), function ($q, $term) {
                $column = app()->getLocale() === 'en' ? 'name_en_text' : 'name_id_text';

                return $q->whereRaw("MATCH({$column}) AGAINST (? IN BOOLEAN MODE)", [$term.'*']);
            })
            ->orderBy('sort_order')
            ->paginate(24)
            ->withQueryString();

        return view('pages.catalog', [
            'products' => $products,
            'categories' => Category::published()->orderBy('sort_order')->get(),
            'brands' => Brand::published()->orderBy('sort_order')->get(),
        ]);
    }
}
```

- [ ] **Step 6: Write the view**

`resources/views/pages/catalog.blade.php` extends the layout. Filters are a plain `<form method="get">` with `<select name="category">`, `<select name="brand">`, and `<input name="q">` — no JavaScript required, so the page works with JS disabled and stays cacheable. Product cards come from `partials/product-card.blade.php`.

- [ ] **Step 7: Register the route**

```php
Route::get('/produk', \App\Http\Controllers\CatalogController::class)->name('products.index');
```

with the English path `'/products'` in the `en` group. Because the loop shares one closure, branch on `$locale`:

```php
Route::get($locale === 'en' ? '/products' : '/produk', CatalogController::class)->name('products.index');
```

- [ ] **Step 8: Run the tests to verify they pass**

Run: `./vendor/bin/pest tests/Feature/CatalogTest.php`
Expected: PASS, 5 tests.

- [ ] **Step 9: Commit**

```bash
git add -A
git commit -m "feat(public): catalog index with category, brand, and fulltext search filters"
```

---

### Task 12: Product detail page

**Files:**
- Create: `app/Http/Controllers/ProductController.php`, `resources/views/pages/product.blade.php`, `tests/Feature/ProductPageTest.php`
- Modify: `routes/web.php`

**Interfaces:**
- Consumes: `Product::coverImage()`, `images`, `specs`, `certifications`
- Produces: `GET /produk/{slug}` and `GET /en/products/{slug}`

- [ ] **Step 1: Write the failing test**

`tests/Feature/ProductPageTest.php`:

```php
<?php

use App\Models\Product;

it('renders a published product in both locales', function () {
    $product = Product::factory()->create([
        'is_published' => true,
        'name' => ['id' => 'Enema Set', 'en' => 'Enema Set'],
        'slug' => 'enema-set',
    ]);

    $this->get('/produk/enema-set')->assertOk();
    $this->get('/en/products/enema-set')->assertOk();
});

it('404s an unpublished product', function () {
    Product::factory()->create(['is_published' => false, 'slug' => 'draft-product']);

    $this->get('/produk/draft-product')->assertNotFound();
});

it('renders specs as a real table', function () {
    Product::factory()->create([
        'is_published' => true,
        'slug' => 'spec-product',
        'specs' => ['Ukuran' => '10x15cm', 'Kemasan' => '50 pcs/box'],
    ]);

    $response = $this->get('/produk/spec-product');

    $response->assertSee('<table', false);
    $response->assertSee('10x15cm');
    $response->assertSee('Kemasan');
});

it('renders without failing when certifications are empty', function () {
    Product::factory()->create([
        'is_published' => true,
        'slug' => 'no-cert',
        'certifications' => null,
    ]);

    $this->get('/produk/no-cert')->assertOk();
});

it('shows an RFQ call to action instead of a price', function () {
    Product::factory()->create(['is_published' => true, 'slug' => 'cta-product']);

    $this->get('/produk/cta-product')
        ->assertSee('Minta Penawaran')
        ->assertDontSee('Rp');
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `./vendor/bin/pest tests/Feature/ProductPageTest.php`
Expected: FAIL — the route does not exist.

- [ ] **Step 3: Write the controller**

`app/Http/Controllers/ProductController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __invoke(string $slug): View
    {
        $product = Product::query()
            ->published()
            ->with(['category', 'brand', 'images'])
            ->where('slug', $slug)
            ->firstOrFail();

        return view('pages.product', [
            'product' => $product,
            'related' => Product::published()
                ->where('category_id', $product->category_id)
                ->whereKeyNot($product->getKey())
                ->with(['category', 'brand', 'images'])
                ->take(4)
                ->get(),
        ]);
    }
}
```

- [ ] **Step 4: Write the view**

`resources/views/pages/product.blade.php` renders, in order: breadcrumb, gallery (cover + thumbnails), H1 with the product name, the `short_description` paragraph, a specs `<table>`, the `description` body, a certifications block guarded by `@if (filled($product->certifications))`, an RFQ CTA linking to `route("{$locale}.contact", ['product' => $product->slug])`, and a related-products grid. Include `Terakhir diperbarui: {{ $product->updated_at->translatedFormat('d F Y') }}`.

- [ ] **Step 5: Register the routes**

```php
Route::get($locale === 'en' ? '/products/{slug}' : '/produk/{slug}', ProductController::class)->name('products.show');
```

- [ ] **Step 6: Run the tests to verify they pass**

Run: `./vendor/bin/pest tests/Feature/ProductPageTest.php`
Expected: PASS, 5 tests.

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "feat(public): product detail page with specs table and RFQ call to action"
```

---

### Task 13: Brand index and detail pages

**Files:**
- Create: `app/Http/Controllers/BrandController.php`, `resources/views/pages/{brands,brand}.blade.php`, `tests/Feature/BrandPageTest.php`
- Modify: `routes/web.php`

**Interfaces:**
- Consumes: `Brand::published()`
- Produces: `GET /brand`, `GET /brand/{slug}` (+ `/en/brands`, `/en/brands/{slug}`)

- [ ] **Step 1: Write the failing test**

`tests/Feature/BrandPageTest.php`:

```php
<?php

use App\Models\Brand;
use App\Models\Product;

it('lists published brands', function () {
    Brand::factory()->create(['name' => 'OneMed', 'is_published' => true]);
    Brand::factory()->create(['name' => 'HiddenBrand', 'is_published' => false]);

    $this->get('/brand')->assertSee('OneMed')->assertDontSee('HiddenBrand');
});

it('shows a brand with its products', function () {
    $brand = Brand::factory()->create(['name' => 'Mindray', 'slug' => 'mindray']);
    Product::factory()->for($brand)->create(['name' => ['id' => 'Monitor Pasien', 'en' => 'Patient Monitor']]);

    $this->get('/brand/mindray')->assertOk()->assertSee('Monitor Pasien');
});

it('404s an unpublished brand', function () {
    Brand::factory()->create(['slug' => 'draft-brand', 'is_published' => false]);

    $this->get('/brand/draft-brand')->assertNotFound();
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `./vendor/bin/pest tests/Feature/BrandPageTest.php`
Expected: FAIL — routes do not exist.

- [ ] **Step 3: Write the controller**

`app/Http/Controllers/BrandController.php` with two methods: `index()` returning published brands, and `show(string $slug)` returning the brand with `Product::published()->where('brand_id', ...)` paginated.

- [ ] **Step 4: Write the views and routes**

`resources/views/pages/brands.blade.php` (grid of logos + names) and `resources/views/pages/brand.blade.php` (brand header, description, certifications block if present, product grid). Routes:

```php
Route::get($locale === 'en' ? '/brands' : '/brand', [BrandController::class, 'index'])->name('brands.index');
Route::get($locale === 'en' ? '/brands/{slug}' : '/brand/{slug}', [BrandController::class, 'show'])->name('brands.show');
```

- [ ] **Step 5: Run the tests to verify they pass**

Run: `./vendor/bin/pest tests/Feature/BrandPageTest.php`
Expected: PASS, 3 tests.

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "feat(public): brand index and detail pages"
```

---

### Task 14: RFQ form with honeypot, rate limiting, and notification

**Files:**
- Create: `app/Http/Requests/StoreInquiryRequest.php`, `app/Http/Controllers/{ContactController,InquiryController}.php`, `resources/views/pages/contact.blade.php`, `tests/Feature/InquirySubmissionTest.php`
- Modify: `routes/web.php`

**Interfaces:**
- Consumes: `Inquiry`, `InquiryReceived` mailable, `Setting::get('contact_email')`
- Produces: `GET /kontak`, `POST /kontak` (+ EN variants); `StoreInquiryRequest` rules

- [ ] **Step 1: Write the failing test**

`tests/Feature/InquirySubmissionTest.php`:

```php
<?php

use App\Mail\InquiryReceived;
use App\Models\Inquiry;
use App\Models\Product;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
});

it('stores a valid inquiry and sends a notification', function () {
    $this->post('/kontak', [
        'name' => 'Budi',
        'company' => 'RS Sehat',
        'email' => 'budi@example.com',
        'phone' => '08123456789',
        'message' => 'Mohon penawaran untuk 100 unit.',
    ])->assertRedirect();

    expect(Inquiry::count())->toBe(1);
    Mail::assertQueued(InquiryReceived::class);
});

it('records the product context when submitted from a product page', function () {
    $product = Product::factory()->create(['is_published' => true, 'slug' => 'enema-set']);

    $this->post('/kontak', [
        'name' => 'Budi',
        'email' => 'budi@example.com',
        'phone' => '08123456789',
        'message' => 'Mohon penawaran.',
        'product' => 'enema-set',
    ]);

    expect(Inquiry::first()->product_id)->toBe($product->id);
});

it('rejects a submission with the honeypot filled', function () {
    $this->post('/kontak', [
        'name' => 'Spam',
        'email' => 'spam@example.com',
        'phone' => '08123456789',
        'message' => 'spam',
        'website' => 'http://spam.example',
    ])->assertSessionHasErrors();

    expect(Inquiry::count())->toBe(0);
});

it('validates required fields', function () {
    $this->post('/kontak', [])->assertSessionHasErrors(['name', 'email', 'phone', 'message']);
});

it('never stores a raw ip address', function () {
    $this->post('/kontak', [
        'name' => 'Budi',
        'email' => 'budi@example.com',
        'phone' => '08123456789',
        'message' => 'Mohon penawaran.',
    ]);

    $hash = Inquiry::first()->ip_hash;

    expect($hash)->toHaveLength(64)
        ->and($hash)->not->toContain('127.0.0.1');
});

it('rate limits repeated submissions from the same ip', function () {
    $payload = [
        'name' => 'Budi',
        'email' => 'budi@example.com',
        'phone' => '08123456789',
        'message' => 'Mohon penawaran.',
    ];

    foreach (range(1, 5) as $i) {
        $this->post('/kontak', $payload);
    }

    $this->post('/kontak', $payload)->assertStatus(429);
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `./vendor/bin/pest tests/Feature/InquirySubmissionTest.php`
Expected: FAIL — the POST route does not exist.

- [ ] **Step 3: Write the form request**

`app/Http/Requests/StoreInquiryRequest.php`:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'message' => ['required', 'string', 'max:5000'],
            'product' => ['nullable', 'string', 'exists:products,slug'],
            // Honeypot: must stay empty. Bots fill every field they find.
            'website' => ['nullable', 'prohibited'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama',
            'email' => 'email',
            'phone' => 'telepon',
            'message' => 'pesan',
        ];
    }
}
```

- [ ] **Step 4: Write the controllers**

`app/Http/Controllers/InquiryController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInquiryRequest;
use App\Mail\InquiryReceived;
use App\Models\Inquiry;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

class InquiryController extends Controller
{
    public function __invoke(StoreInquiryRequest $request): RedirectResponse
    {
        $product = $request->filled('product')
            ? Product::published()->where('slug', $request->string('product'))->first()
            : null;

        $inquiry = Inquiry::create([
            'name' => $request->string('name'),
            'company' => $request->string('company') ?: null,
            'email' => $request->string('email'),
            'phone' => $request->string('phone'),
            'product_id' => $product?->id,
            'message' => $request->string('message'),
            'locale' => app()->getLocale(),
            'source_path' => $request->headers->get('referer'),
            // Raw IPs are never persisted — only a keyed hash for rate limiting.
            'ip_hash' => hash('sha256', $request->ip().config('app.key')),
        ]);

        $recipient = Setting::get('contact_email');

        if ($recipient) {
            Mail::to($recipient)->queue(new InquiryReceived($inquiry));
        }

        return back()->with('status', 'Terima kasih. Permintaan Anda telah kami terima.');
    }
}
```

`app/Http/Controllers/ContactController.php` returns the contact view with the optional pre-selected product.

- [ ] **Step 5: Register the routes with throttling and the honeypot**

```php
Route::get($locale === 'en' ? '/contact' : '/kontak', ContactController::class)->name('contact');
Route::post($locale === 'en' ? '/contact' : '/kontak', InquiryController::class)
    ->middleware('throttle:5,1')
    ->name('contact.store');
```

- [ ] **Step 6: Write the contact view**

`resources/views/pages/contact.blade.php` renders company details from `Setting`, a WhatsApp link, and the RFQ form. The honeypot field must be visually hidden but not `display:none` (some bots skip those), and must carry `tabindex="-1"` and `autocomplete="off"` so real users never fill it:

```blade
<div class="absolute -left-[9999px]" aria-hidden="true">
    <label for="website">Website</label>
    <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
</div>
```

- [ ] **Step 7: Run the tests to verify they pass**

Run: `./vendor/bin/pest tests/Feature/InquirySubmissionTest.php`
Expected: PASS, 6 tests. The rate-limit test needs the `throttle` middleware active in the test environment — confirm `bootstrap/app.php` does not disable it.

- [ ] **Step 8: Commit**

```bash
git add -A
git commit -m "feat(public): RFQ form with honeypot, rate limiting, and email notification"
```

---

### Task 15: About and static pages

**Files:**
- Create: `app/Http/Controllers/PageController.php`, `resources/views/pages/{about,page}.blade.php`, `tests/Feature/StaticPageTest.php`
- Modify: `routes/web.php`

**Interfaces:**
- Consumes: `Page::published()`, `Setting`
- Produces: `GET /tentang-kami`, `GET /halaman/{slug}` (+ EN variants)

- [ ] **Step 1: Write the failing test**

```php
<?php

use App\Models\Page;

it('renders the about page', function () {
    $this->get('/tentang-kami')->assertOk();
    $this->get('/en/about')->assertOk();
});

it('renders a published static page', function () {
    Page::create([
        'slug' => 'kebijakan-privasi',
        'title' => ['id' => 'Kebijakan Privasi', 'en' => 'Privacy Policy'],
        'is_published' => true,
    ]);

    $this->get('/halaman/kebijakan-privasi')->assertOk()->assertSee('Kebijakan Privasi');
});

it('404s an unpublished static page', function () {
    Page::create(['slug' => 'draf', 'title' => ['id' => 'Draf', 'en' => 'Draft'], 'is_published' => false]);

    $this->get('/halaman/draf')->assertNotFound();
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `./vendor/bin/pest tests/Feature/StaticPageTest.php`
Expected: FAIL — routes do not exist.

- [ ] **Step 3: Write the controller and views, then register the routes**

```php
Route::get($locale === 'en' ? '/about' : '/tentang-kami', [PageController::class, 'about'])->name('about');
Route::get($locale === 'en' ? '/pages/{slug}' : '/halaman/{slug}', [PageController::class, 'show'])->name('pages.show');
```

- [ ] **Step 4: Run the tests to verify they pass**

Run: `./vendor/bin/pest tests/Feature/StaticPageTest.php`
Expected: PASS, 3 tests.

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "feat(public): about and static content pages"
```

---

## Phase 4 — SEO

### Task 16: Per-page meta, canonical, and the hreflang integrity test

**Files:**
- Create: `resources/views/components/seo/meta.blade.php`, `tests/Feature/HreflangIntegrityTest.php`
- Modify: `resources/views/layouts/app.blade.php`, `database/migrations/*_add_meta_columns.php`, and all four models — `app/Models/Product.php`, `app/Models/Category.php`, `app/Models/Brand.php`, `app/Models/Page.php` (adding `meta_title` / `meta_description` to each `$translatable` array and adding the `seoTitle()` / `seoDescription()` accessors)

**Interfaces:**
- Consumes: `LocaleUrls::alternates()`
- Produces: `meta_title` / `meta_description` translatable columns on `products`, `categories`, `brands`, `pages`, plus `seoTitle(): string` and `seoDescription(): string` on each of those models

- [ ] **Step 1: Write the failing test**

`tests/Feature/HreflangIntegrityTest.php`:

```php
<?php

use App\Models\Product;
use App\Support\LocaleUrls;

it('emits self-referencing, bidirectional, fully-qualified hreflang on every public route', function () {
    $product = Product::factory()->create(['is_published' => true, 'slug' => 'enema-set']);

    $paths = ['/', '/produk', '/produk/enema-set', '/brand', '/tentang-kami', '/kontak'];

    foreach ($paths as $path) {
        $response = $this->get($path)->assertOk();
        $html = $response->getContent();

        preg_match_all('/<link rel="alternate" hreflang="([^"]+)" href="([^"]+)"/', $html, $matches, PREG_SET_ORDER);

        expect($matches)->not->toBeEmpty("No hreflang emitted for {$path}");

        foreach ($matches as $match) {
            [$full, $hreflang, $href] = $match;

            expect($href)->toStartWith('http', "hreflang {$hreflang} on {$path} is not fully-qualified");

            // Every emitted alternate must resolve. This is the slug-parity
            // trap guard: a drifted slug makes hreflang point at a 404 and
            // Google silently discards the whole annotation pair.
            $alternatePath = parse_url($href, PHP_URL_PATH);
            $this->get($alternatePath)->assertOk();
        }
    }
});

it('includes a self-referencing hreflang for the current locale', function () {
    $this->get('/produk')
        ->assertSee('hreflang="id"', false)
        ->assertSee('hreflang="en"', false)
        ->assertSee('hreflang="x-default"', false);
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `./vendor/bin/pest tests/Feature/HreflangIntegrityTest.php`
Expected: FAIL — hreflang is emitted but product/category/brand routes are missing from the alternate map because their route names differ.

- [ ] **Step 3: Add meta columns**

`database/migrations/2026_09_18_000004_add_meta_columns.php` adds nullable `meta_title` and `meta_description` JSON columns to `products`, `categories`, `brands`, and `pages`.

- [ ] **Step 4: Add fallback accessors**

On each model, add `$translatable` entries for `meta_title`, `meta_description`, and a method:

```php
public function seoTitle(): string
{
    return $this->meta_title ?: $this->name;
}

public function seoDescription(): string
{
    return $this->meta_description ?: (string) $this->short_description;
}
```

`Brand` differs: it has no `short_description` column, so it must override the
description accessor rather than inherit the body above.

```php
public function seoTitle(): string
{
    return $this->meta_title ?: $this->name;
}

public function seoDescription(): string
{
    return $this->meta_description ?: $this->name;
}
```

`Brand::$translatable` also needs `meta_title` and `meta_description` added
alongside its existing `description` key.

- [ ] **Step 5: Write the meta component and wire it into the layout**

`resources/views/components/seo/meta.blade.php` emits `<title>`, `<meta name="description">`, and OG/Twitter tags from variables passed by each view. Update `layouts/app.blade.php` to `@include` it and to keep `@include('components.seo.hreflang')` and the canonical partial.

- [ ] **Step 6: Fix any route-name mismatches surfaced by the test**

The integrity test will fail for any route whose `{locale}.{name}` pairing is missing. Add the missing English or Indonesian route for each failure. Do not weaken the test.

- [ ] **Step 7: Run the tests to verify they pass**

Run: `./vendor/bin/pest tests/Feature/HreflangIntegrityTest.php`
Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add -A
git commit -m "feat(seo): per-page meta, canonical, and automated hreflang integrity test"
```

---

### Task 17: Sitemap and robots.txt

**Files:**
- Create: `app/Console/Commands/GenerateSitemap.php`, `app/Http/Controllers/SitemapController.php`, `resources/views/sitemap.blade.php`, `public/robots.txt`, `tests/Feature/SitemapTest.php`
- Modify: `routes/web.php`, `routes/console.php`

**Interfaces:**
- Consumes: `Product::published()`, `Category::published()`, `Brand::published()`, `Page::published()`
- Produces: `GET /sitemap.xml`, `php artisan mmg:sitemap`

- [ ] **Step 1: Write the failing test**

`tests/Feature/SitemapTest.php`:

```php
<?php

use App\Models\Product;

it('includes published products in the sitemap and excludes drafts', function () {
    Product::factory()->create(['is_published' => true, 'slug' => 'published-one']);
    Product::factory()->create(['is_published' => false, 'slug' => 'draft-one']);

    $response = $this->get('/sitemap.xml')->assertOk();

    $response->assertSee('/produk/published-one', false);
    $response->assertSee('/en/products/published-one', false);
    $response->assertDontSee('draft-one', false);
});

it('declares the sitemap in robots.txt', function () {
    expect(file_get_contents(public_path('robots.txt')))->toContain('Sitemap:');
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `./vendor/bin/pest tests/Feature/SitemapTest.php`
Expected: FAIL — no sitemap route and no robots.txt.

- [ ] **Step 3: Write the sitemap controller and view**

`SitemapController` collects all published URLs for both locales using `LocaleUrls`-style route generation, and returns `view('sitemap', ...)` with `Content-Type: application/xml`. The view emits a standard `<urlset>` with `<xhtml:link rel="alternate" hreflang="...">` entries per URL.

- [ ] **Step 4: Write the command and schedule it**

`php artisan make:command GenerateSitemap --no-interaction` with signature `mmg:sitemap` and description "Warm the sitemap cache". The command calls `Cache::forget('sitemap')` then regenerates. In `routes/console.php`:

```php
Schedule::command('mmg:sitemap')->dailyAt('03:00');
```

- [ ] **Step 5: Write robots.txt**

`public/robots.txt`:

```
User-agent: *
Allow: /
Disallow: /admin
Disallow: /storage

# AI search and citation crawlers — explicitly allowed so these engines can
# cite the catalog. Blocking them prevents citation, not just training.
User-agent: GPTBot
Allow: /
User-agent: ChatGPT-User
Allow: /
User-agent: OAI-SearchBot
Allow: /
User-agent: PerplexityBot
Allow: /
User-agent: ClaudeBot
Allow: /
User-agent: anthropic-ai
Allow: /
User-agent: Google-Extended
Allow: /
User-agent: Bingbot
Allow: /

# Training-only crawler. Blocking this does not affect citation eligibility.
User-agent: CCBot
Disallow: /

Sitemap: https://example.com/sitemap.xml
```

Replace `https://example.com` with the production domain once known. Note in the commit message that this must be updated before launch.

- [ ] **Step 6: Run the tests to verify they pass**

Run: `./vendor/bin/pest tests/Feature/SitemapTest.php`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "feat(seo): sitemap generation and robots.txt with AI crawler policy"
```

---

### Task 18: JSON-LD structured data

**Files:**
- Create: `resources/views/components/schema/{organization,product,breadcrumb,itemlist,wholesalestore}.blade.php`, `app/Support/SchemaBuilder.php`, `tests/Feature/StructuredDataTest.php`
- Modify: `resources/views/layouts/app.blade.php`, each public view

**Interfaces:**
- Consumes: `Setting`, `Product`, `Category`, `Brand`
- Produces: `SchemaBuilder::organization(): array`, `SchemaBuilder::product(Product $p): array`, `SchemaBuilder::manufacturer(Brand $b): array`, `SchemaBuilder::breadcrumb(array $crumbs): array`, `SchemaBuilder::itemList(array $items, string $name): array`, `SchemaBuilder::wholesaleStore(): array`

- [ ] **Step 1: Write the failing test**

`tests/Feature/StructuredDataTest.php`:

```php
<?php

use App\Models\Brand;
use App\Models\Product;

it('emits a valid Organization node with a stable id', function () {
    $this->get('/')->assertSee('"@type":"Organization"', false)->assertSee('"@id"', false);
});

it('emits Product schema with MedicalDevice and specs as additionalProperty', function () {
    Product::factory()->create([
        'is_published' => true,
        'slug' => 'enema-set',
        'specs' => ['Ukuran' => '10x15cm'],
    ]);

    $response = $this->get('/produk/enema-set');

    $response->assertSee('"@type":"Product"', false);
    $response->assertSee('MedicalDevice', false);
    $response->assertSee('additionalProperty', false);
    $response->assertSee('10x15cm', false);
});

it('references the same manufacturer id from the product page', function () {
    $brand = Brand::factory()->create(['name' => 'OneMed', 'slug' => 'onemed']);
    Product::factory()->for($brand)->create(['is_published' => true, 'slug' => 'branded']);

    $this->get('/produk/branded')->assertSee('#manufacturer-onemed', false);
});

it('omits the certification node entirely when certifications are empty', function () {
    Product::factory()->create([
        'is_published' => true,
        'slug' => 'no-cert',
        'certifications' => null,
    ]);

    $this->get('/produk/no-cert')->assertDontSee('hasCertification', false);
});

it('never emits a price in structured data', function () {
    Product::factory()->create(['is_published' => true, 'slug' => 'no-price']);

    $response = $this->get('/produk/no-price');

    $response->assertDontSee('"offers"', false);
    $response->assertDontSee('"price"', false);
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `./vendor/bin/pest tests/Feature/StructuredDataTest.php`
Expected: FAIL — no JSON-LD is emitted.

- [ ] **Step 3: Write the schema builder**

`app/Support/SchemaBuilder.php`:

```php
<?php

namespace App\Support;

use App\Models\Brand;
use App\Models\Product;
use App\Models\Setting;

class SchemaBuilder
{
    public static function organization(): array
    {
        $node = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            '@id' => url('/').'#organization',
            'name' => Setting::get('company_name', 'PT Medquest Mitra Global'),
            'url' => url('/'),
        ];

        if ($logo = Setting::get('logo')) {
            $node['logo'] = asset($logo);
        }

        if ($email = Setting::get('contact_email')) {
            $node['email'] = $email;
        }

        if ($phone = Setting::get('contact_phone')) {
            $node['telephone'] = $phone;
        }

        if ($address = Setting::get('address')) {
            $node['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => $address,
                'addressLocality' => 'Jakarta',
                'addressCountry' => 'ID',
            ];
        }

        if ($socials = Setting::get('socials')) {
            $node['sameAs'] = array_values(array_filter($socials));
        }

        return $node;
    }

    public static function product(Product $product): array
    {
        $node = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            '@id' => url()->current().'#product',
            'name' => $product->name,
            'additionalType' => 'https://schema.org/MedicalDevice',
            'url' => url()->current(),
            'category' => $product->category?->name,
        ];

        if ($product->short_description) {
            $node['description'] = strip_tags($product->short_description);
        }

        if ($product->sku) {
            $node['sku'] = $product->sku;
        }

        if ($product->brand) {
            $node['manufacturer'] = self::manufacturer($product->brand);
            $node['brand'] = ['@type' => 'Brand', 'name' => $product->brand->name];
        }

        if (filled($product->specs)) {
            $node['additionalProperty'] = collect($product->specs)
                ->map(fn ($value, $key) => [
                    '@type' => 'PropertyValue',
                    'name' => $key,
                    'value' => $value,
                ])
                ->values()
                ->all();
        }

        // Emitted only when data exists. An empty certifications array must
        // not produce an empty node.
        if (filled($product->certifications)) {
            $node['hasCertification'] = collect($product->certifications)
                ->map(fn ($cert) => array_filter([
                    '@type' => 'Certification',
                    'name' => $cert['type'] ?? null,
                    'certificationIdentification' => $cert['number'] ?? null,
                    'issuedBy' => filled($cert['issuer'] ?? null)
                        ? ['@type' => 'Organization', 'name' => $cert['issuer']]
                        : null,
                ]))
                ->values()
                ->all();
        }

        return $node;
    }

    public static function manufacturer(Brand $brand): array
    {
        return array_filter([
            '@type' => 'Organization',
            '@id' => url('/').'#manufacturer-'.$brand->slug,
            'name' => $brand->name,
            'url' => route(app()->getLocale().'.brands.show', $brand),
            'logo' => $brand->logo ? asset($brand->logo) : null,
        ]);
    }

    public static function breadcrumb(array $crumbs): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($crumbs)
                ->values()
                ->map(fn ($crumb, $i) => [
                    '@type' => 'ListItem',
                    'position' => $i + 1,
                    'name' => $crumb['name'],
                    'item' => $crumb['url'],
                ])
                ->all(),
        ];
    }

    public static function itemList(array $items, string $name): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => $name,
            'itemListElement' => collect($items)
                ->values()
                ->map(fn ($item, $i) => [
                    '@type' => 'ListItem',
                    'position' => $i + 1,
                    'url' => $item['url'],
                    'name' => $item['name'],
                ])
                ->all(),
        ];
    }
}
```

- [ ] **Step 4: Write the Blade partials and push them into the layout stack**

Each partial is a `<script type="application/ld+json">{!! json_encode($node, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>`. Each public view pushes its nodes.

Home page:

```blade
@push('schema')
    <x-schema.organization :node="\App\Support\SchemaBuilder::organization()" />
@endpush
```

Product page:

```blade
@push('schema')
    <x-schema.organization :node="\App\Support\SchemaBuilder::organization()" />
    <x-schema.product :node="\App\Support\SchemaBuilder::product($product)" />
    <x-schema.breadcrumb :node="\App\Support\SchemaBuilder::breadcrumb([
        ['name' => __('Beranda'), 'url' => route($locale.'.home')],
        ['name' => $product->category->name, 'url' => route($locale.'.products.index', ['category' => $product->category->slug])],
        ['name' => $product->name, 'url' => url()->current()],
    ])" />
@endpush
```

Catalog page — `ItemList` over the paginated result, plus breadcrumb:

```blade
@push('schema')
    <x-schema.breadcrumb :node="\App\Support\SchemaBuilder::breadcrumb([
        ['name' => __('Beranda'), 'url' => route($locale.'.home')],
        ['name' => __('Produk'), 'url' => url()->current()],
    ])" />
    <x-schema.itemlist :node="\App\Support\SchemaBuilder::itemList(
        $products->map(fn ($p) => ['name' => $p->name, 'url' => route($locale.'.products.show', $p)])->all(),
        __('Katalog Produk')
    )" />
@endpush
```

Brand pages — `ItemList` over the brand's products, plus breadcrumb.

About and contact pages — `WholesaleStore` (the distributor type) merged with `LocalBusiness` properties, plus breadcrumb:

```blade
@push('schema')
    <x-schema.wholesalestore :node="\App\Support\SchemaBuilder::wholesaleStore()" />
@endpush
```

Add `SchemaBuilder::wholesaleStore(): array` to `app/Support/SchemaBuilder.php`:

```php
public static function wholesaleStore(): array
{
    return array_merge(self::organization(), [
        '@type' => ['WholesaleStore', 'LocalBusiness'],
        'priceRange' => 'Hubungi kami',
    ]);
}
```

- [ ] **Step 5: Run the tests to verify they pass**

Run: `./vendor/bin/pest tests/Feature/StructuredDataTest.php`
Expected: PASS, 5 tests.

- [ ] **Step 6: Validate against the schema.org validator**

Deploy to a staging URL or expose via `php artisan serve` with a tunnel, then run the product URL through `https://validator.schema.org/`. Fix any reported errors before committing.

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "feat(seo): JSON-LD for Organization, Product, Breadcrumb, and ItemList"
```

---

## Phase 5 — AEO / GEO

### Task 19: Agent-facing documents (`/llms.txt`, `/llms-full.txt`, `/katalog.md`)

**Files:**
- Create: `app/Services/AgentDocumentBuilder.php`, `app/Support/MarkdownRenderer.php`, `app/Http/Controllers/AgentDocumentController.php`, `resources/views/agent/{llms,llms-full,katalog}.blade.php`, `app/Console/Commands/GenerateAgentDocs.php`, `tests/Feature/AgentDocumentsTest.php`
- Modify: `routes/web.php`, `routes/console.php`

**Interfaces:**
- Consumes: `Product::published()`, `Category::published()`, `Brand::published()`, `Setting`, `SchemaBuilder`
- Produces:
  - `AgentDocumentBuilder::llms(): string`
  - `AgentDocumentBuilder::llmsFull(): string`
  - `AgentDocumentBuilder::catalog(): string`
  - `MarkdownRenderer::fromRichText(?string $json): string`
  - Routes `/llms.txt`, `/llms-full.txt`, `/katalog.md`

- [ ] **Step 1: Write the failing test**

`tests/Feature/AgentDocumentsTest.php`:

```php
<?php

use App\Models\Brand;
use App\Models\Product;

it('serves llms.txt with company context and links', function () {
    $this->get('/llms.txt')
        ->assertOk()
        ->assertSee('Medquest Mitra Global')
        ->assertSee('/katalog.md');
});

it('serves katalog.md with every published product', function () {
    Product::factory()->create([
        'is_published' => true,
        'name' => ['id' => 'Enema Set', 'en' => 'Enema Set'],
        'specs' => ['Ukuran' => '10x15cm'],
    ]);

    $this->get('/katalog.md')
        ->assertOk()
        ->assertSee('Enema Set')
        ->assertSee('10x15cm');
});

it('never leaks unpublished products into agent documents', function () {
    Product::factory()->create([
        'is_published' => false,
        'name' => ['id' => 'Produk Rahasia', 'en' => 'Secret Product'],
    ]);

    $this->get('/katalog.md')->assertDontSee('Produk Rahasia');
    $this->get('/llms-full.txt')->assertDontSee('Produk Rahasia');
});

it('gives agents a quote request path instead of a dead end', function () {
    Product::factory()->create(['is_published' => true]);

    $this->get('/katalog.md')
        ->assertSee('penawaran', false)
        ->assertSee('/kontak');
});

it('never publishes a price', function () {
    Product::factory()->create(['is_published' => true]);

    $this->get('/katalog.md')
        ->assertDontSee('Rp', false)
        ->assertDontSee('IDR');
});

it('includes certifications in the catalog when present', function () {
    Product::factory()->create([
        'is_published' => true,
        'name' => ['id' => 'Certified', 'en' => 'Certified'],
        'certifications' => [[
            'type' => 'izin_edar',
            'number' => 'AKL 12345678901',
            'issuer' => 'Kemenkes RI',
        ]],
    ]);

    $this->get('/katalog.md')->assertSee('AKL 12345678901');
});

it('omits the certification section when there are none', function () {
    Product::factory()->create([
        'is_published' => true,
        'name' => ['id' => 'Plain', 'en' => 'Plain'],
        'certifications' => null,
    ]);

    $this->get('/katalog.md')->assertOk();
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `./vendor/bin/pest tests/Feature/AgentDocumentsTest.php`
Expected: FAIL — routes do not exist.

- [ ] **Step 3: Write the markdown renderer**

`app/Support/MarkdownRenderer.php` converts the stored rich-text JSON (Filament's `RichEditor` shape: an array of `{type, content}` blocks) into markdown. Handle `heading` (level 2/3 only, matching the editor config), `paragraph`, `bulletList`, `orderedList`, and `table`. Unknown node types fall through to plain text so nothing is silently dropped.

- [ ] **Step 4: Write the document builder**

`app/Services/AgentDocumentBuilder.php`:

```php
<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Support\MarkdownRenderer;
use Illuminate\Support\Facades\Cache;

class AgentDocumentBuilder
{
    public function llms(): string
    {
        return Cache::remember('agent.llms', now()->addHours(6), fn () => view('agent.llms', [
            'company' => Setting::get('company_name', 'PT Medquest Mitra Global'),
            'description' => Setting::get('default_meta_description'),
            'categories' => Category::published()->orderBy('sort_order')->get(),
            'productCount' => Product::published()->count(),
        ])->render());
    }

    public function llmsFull(): string
    {
        return Cache::remember('agent.llms-full', now()->addHours(6), fn () => view('agent.llms-full', [
            'company' => Setting::get('company_name', 'PT Medquest Mitra Global'),
            'description' => Setting::get('default_meta_description'),
            'categories' => Category::published()->with('products')->orderBy('sort_order')->get(),
            'brands' => Brand::published()->orderBy('sort_order')->get(),
            'contact' => Setting::get('contact_email'),
            'phone' => Setting::get('contact_phone'),
        ])->render());
    }

    public function catalog(): string
    {
        return Cache::remember('agent.katalog', now()->addHours(6), fn () => view('agent.katalog', [
            'products' => Product::published()
                ->with(['category', 'brand'])
                ->orderBy('sort_order')
                ->get(),
            'markdown' => app(MarkdownRenderer::class),
            'contactUrl' => url('/kontak'),
        ])->render());
    }

    public function forget(): void
    {
        Cache::forget('agent.llms');
        Cache::forget('agent.llms-full');
        Cache::forget('agent.katalog');
    }
}
```

- [ ] **Step 5: Write the markdown views**

`resources/views/agent/katalog.blade.php` — for each product emit an H2 with the name, a line for brand and category, a markdown table of specs, a certifications line guarded by `@if (filled($product->certifications))`, and a closing line: `Minta penawaran: [formulir permintaan penawaran]({contactUrl}?product={slug})`. Include a frontmatter-style header with the generation date so freshness is machine-readable.

- [ ] **Step 6: Write the controller and routes**

```php
Route::get('/llms.txt', [AgentDocumentController::class, 'llms'])->name('agent.llms');
Route::get('/llms-full.txt', [AgentDocumentController::class, 'llmsFull'])->name('agent.llmsFull');
Route::get('/katalog.md', [AgentDocumentController::class, 'catalog'])->name('agent.katalog');
```

These three routes sit **outside** the locale loop and respond with `text/plain` / `text/markdown`. Register them before the locale group so they are not prefixed. They carry no `locale:` middleware — the locale is irrelevant to these documents, which are bilingual by construction.

- [ ] **Step 7: Write the regeneration command and schedule it**

`php artisan make:command GenerateAgentDocs --no-interaction`, signature `mmg:agent-docs`. Calls `AgentDocumentBuilder::forget()` then rebuilds all three. Schedule in `routes/console.php`:

```php
Schedule::command('mmg:agent-docs')->dailyAt('03:15');
```

- [ ] **Step 8: Run the tests to verify they pass**

Run: `./vendor/bin/pest tests/Feature/AgentDocumentsTest.php`
Expected: PASS, 7 tests.

- [ ] **Step 9: Commit**

```bash
git add -A
git commit -m "feat(aeo): generate llms.txt, llms-full.txt, and katalog.md from the catalog"
```

---

### Task 20: Content negotiation for markdown

**Files:**
- Create: `app/Http/Middleware/NegotiateMarkdown.php`, `tests/Feature/ContentNegotiationTest.php`
- Modify: `bootstrap/app.php`, `routes/web.php`

**Interfaces:**
- Consumes: `AgentDocumentBuilder`, `MarkdownRenderer::fromRichText(?string $json): string`
- Produces: `NegotiateMarkdown` middleware that sets `Vary: Accept` on every negotiated route; `CatalogController` and `ProductController` return a markdown response body when the request asks for markdown, via the shared `App\Support\WantsMarkdown::check(Request): bool` helper

- [ ] **Step 1: Write the failing test**

`tests/Feature/ContentNegotiationTest.php`:

```php
<?php

use App\Models\Product;

it('returns markdown when the client asks for markdown', function () {
    Product::factory()->create([
        'is_published' => true,
        'slug' => 'enema-set',
        'name' => ['id' => 'Enema Set', 'en' => 'Enema Set'],
        'specs' => ['Ukuran' => '10x15cm'],
    ]);

    $response = $this->get('/produk/enema-set', ['Accept' => 'text/markdown']);

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('text/markdown');
    expect($response->getContent())->toContain('Enema Set');
});

it('returns html when the client asks for html', function () {
    Product::factory()->create(['is_published' => true, 'slug' => 'enema-set']);

    $response = $this->get('/produk/enema-set', ['Accept' => 'text/html']);

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('text/html');
    expect($response->getContent())->toContain('<html');
});

it('always sends vary accept on negotiated routes', function () {
    Product::factory()->create(['is_published' => true, 'slug' => 'enema-set']);

    $this->get('/produk/enema-set', ['Accept' => 'text/markdown'])
        ->assertHeader('Vary', 'Accept');
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `./vendor/bin/pest tests/Feature/ContentNegotiationTest.php`
Expected: FAIL — markdown is never returned and `Vary` is absent.

- [ ] **Step 3: Write the negotiation helper and the Vary middleware**

Content negotiation is decided **in the controller**, not in middleware. Middleware cannot read view data reliably (the response content is already a `View` object, not a string), so a middleware-based body swap is fragile. Middleware owns only the header; the controller owns the body.

`app/Support/WantsMarkdown.php`:

```php
<?php

namespace App\Support;

use Illuminate\Http\Request;

class WantsMarkdown
{
    /**
     * True when the client explicitly prefers text/markdown over text/html.
     *
     * Browsers send `text/html,...` with no markdown entry, so they are
     * unaffected. Agents send either `text/markdown` alone or both with
     * q-values. When both are present, html wins only if it is strictly
     * higher-weighted.
     */
    public static function check(Request $request): bool
    {
        $accept = $request->header('Accept', '');

        if (! str_contains($accept, 'text/markdown')) {
            return false;
        }

        $html = preg_match('/text\/html;\s*q=([0-9.]+)/', $accept, $h) ? (float) $h[1] : null;
        $md = preg_match('/text\/markdown;\s*q=([0-9.]+)/', $accept, $m) ? (float) $m[1] : null;

        if ($html !== null && $md !== null) {
            return $md >= $html;
        }

        return true;
    }
}
```

`app/Http/Middleware/NegotiateMarkdown.php` — header only:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NegotiateMarkdown
{
    /**
     * Vary: Accept is unconditional on negotiated routes. Without it a shared
     * cache keyed only on URL will hand a markdown body to a browser, or an
     * HTML body to an agent.
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request)->header('Vary', 'Accept');
    }
}
```

- [ ] **Step 4: Register the middleware on the negotiated routes**

In `bootstrap/app.php`, alias `'negotiate' => \App\Http\Middleware\NegotiateMarkdown::class`. Then apply `->middleware('negotiate')` to the catalog index and product show routes.

- [ ] **Step 5: Make the controllers return markdown**

In both `CatalogController::__invoke()` and `ProductController::__invoke()`, branch at the top of the method:

```php
if (\App\Support\WantsMarkdown::check($request)) {
    return response($this->markdown($product), 200, [
        'Content-Type' => 'text/markdown; charset=utf-8',
        'Vary' => 'Accept',
    ]);
}
```

Build the markdown with `MarkdownRenderer::fromRichText()` on the same model data the HTML path uses, and reuse the `resources/views/agent/katalog.blade.php` partial from Task 19 so the product body is authored once. Do not write a second markdown template.

- [ ] **Step 6: Exclude negotiated routes from the LiteSpeed guest cache**

The design spec requires this: a shared cache keyed only on URL would serve the wrong representation. Concretely, add the negotiated paths to the LiteSpeed cache exclusion list in `.htaccess` at the document root:

```apache
<IfModule LiteSpeed>
    RewriteEngine On
    RewriteCond %{HTTP:Accept} text/markdown [NC]
    RewriteRule .* - [E=Cache-Control:no-cache]
</IfModule>
```

This bypasses the LiteSpeed guest cache for markdown requests only; HTML requests keep the full cache benefit. Leave a comment on the route definitions pointing at this `.htaccess` block so the coupling is discoverable.

- [ ] **Step 7: Run the tests to verify they pass**

Run: `./vendor/bin/pest tests/Feature/ContentNegotiationTest.php`
Expected: PASS, 3 tests.

- [ ] **Step 8: Commit**

```bash
git add -A
git commit -m "feat(aeo): markdown content negotiation with Vary: Accept"
```

---

## Phase 6 — Caching and Deployment

### Task 21: Cache invalidation observers

**Files:**
- Create: `app/Observers/{ProductObserver,CategoryObserver,BrandObserver,PageObserver}.php`, `tests/Feature/CacheInvalidationTest.php`
- Modify: `app/Providers/AppServiceProvider.php`

**Interfaces:**
- Consumes: `AgentDocumentBuilder::forget()`
- Produces: model saves clear the agent-document cache and the relevant page cache

- [ ] **Step 1: Write the failing test**

`tests/Feature/CacheInvalidationTest.php`:

```php
<?php

use App\Models\Product;
use App\Services\AgentDocumentBuilder;

it('regenerates agent documents when a product is published', function () {
    $builder = app(AgentDocumentBuilder::class);
    $builder->catalog();

    expect(Cache::has('agent.katalog'))->toBeTrue();

    Product::factory()->create(['is_published' => true]);

    expect(Cache::has('agent.katalog'))->toBeFalse();
});

it('refreshes the catalog document with the new product on next read', function () {
    $builder = app(AgentDocumentBuilder::class);

    Product::factory()->create(['is_published' => true, 'name' => ['id' => 'Baru', 'en' => 'New']]);
    expect($builder->catalog())->toContain('Baru');

    Product::factory()->create(['is_published' => true, 'name' => ['id' => 'Terbaru', 'en' => 'Newest']]);
    expect($builder->catalog())->toContain('Terbaru');
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `./vendor/bin/pest tests/Feature/CacheInvalidationTest.php`
Expected: FAIL — the cache is never cleared on save.

- [ ] **Step 3: Write one observer and register all four**

`app/Observers/ProductObserver.php`:

```php
<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\AgentDocumentBuilder;

class ProductObserver
{
    public function __construct(private AgentDocumentBuilder $builder) {}

    public function saved(Product $product): void
    {
        $this->builder->forget();
    }

    public function deleted(Product $product): void
    {
        $this->builder->forget();
    }
}
```

Register in `AppServiceProvider::boot()`:

```php
Product::observe(ProductObserver::class);
Category::observe(CategoryObserver::class);
Brand::observe(BrandObserver::class);
Page::observe(PageObserver::class);
```

- [ ] **Step 4: Run the tests to verify they pass**

Run: `./vendor/bin/pest tests/Feature/CacheInvalidationTest.php`
Expected: PASS.

- [ ] **Step 5: Run the full suite**

Run: `./vendor/bin/pest`
Expected: all tests pass. Fix any failures before proceeding — this is the gate for the deploy task.

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "feat(cache): invalidate agent documents and page cache on model save"
```

---

### Task 22: Deployment and hosting verification

**Files:**
- Create: `docs/deployment.md`, `app/Console/Commands/WarmCaches.php`
- Modify: `routes/console.php`

**Interfaces:**
- Consumes: everything above
- Produces: a documented, repeatable deploy plus verification of the five hosting risks from the spec

- [ ] **Step 1: Write the warm-caches command**

`php artisan make:command WarmCaches --no-interaction`, signature `mmg:warm`. It calls `AgentDocumentBuilder` for all three documents, rebuilds the sitemap, and caches settings/categories/brands/navigation.

- [ ] **Step 2: Document the deploy procedure**

`docs/deployment.md` must cover, in order:

1. Local/CI: `composer install --no-dev --optimize-autoloader`, `npm ci`, `npm run build`.
2. Upload via Domainesia Git Deploy Manager or `rsync` over SSH, excluding `node_modules` and `.env`.
3. SSH in and run: `php artisan migrate --force`, `php artisan config:cache`, `php artisan route:cache`, `php artisan view:cache`, `php artisan storage:link`, `php artisan mmg:warm`.
4. Cron: `* * * * * php /path/artisan schedule:run >> /dev/null 2>&1`.
5. Queue: if no persistent worker is permitted, use `php artisan queue:work --stop-when-empty --max-time=50` from cron every minute.
6. Rollback: keep the previous release directory; `ln -sfn` the `public` symlink back.

- [ ] **Step 3: Verify the five hosting risks on the real account**

Run each and record the outcome in `docs/deployment.md`:

```bash
# Risk 1 — PHP version
php -v

# Risk 2 — symlink support under CageFS
php artisan storage:link && ls -la public/storage

# Risk 3 — queue worker policy
php artisan queue:work --stop-when-empty --max-time=10

# Risk 4 — inode count after a pilot image upload
find storage/app/public -type f | wc -l

# Risk 5 — LiteSpeed cache purge after a save
curl -sI https://<domain>/produk | grep -i 'x-litespeed-cache'
```

If any risk materialises, apply the documented fallback from the spec §11 table and update `docs/deployment.md`.

- [ ] **Step 4: Verify the agent endpoints and structured data in production**

```bash
curl -s https://<domain>/llms.txt | head -20
curl -s https://<domain>/katalog.md | head -40
curl -s -H 'Accept: text/markdown' https://<domain>/produk/<slug> | head -20
curl -sI -H 'Accept: text/markdown' https://<domain>/produk/<slug> | grep -i vary
curl -s https://<domain>/sitemap.xml | head -20
```

Expected: markdown bodies returned, `Vary: Accept` present, sitemap lists both locales.

- [ ] **Step 5: Submit the sitemap and verify indexing**

Add the property to Google Search Console and Bing Webmaster Tools, submit `/sitemap.xml`, and confirm `robots.txt` is reachable at `/robots.txt`.

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "docs: deployment procedure and hosting risk verification"
```

---

## Definition of Done

- [ ] Full Pest suite passes (`./vendor/bin/pest`).
- [ ] Both locales render every public page; `hreflang` integrity test passes.
- [ ] `/admin` allows `admin` and `editor` roles; denies users with no role.
- [ ] Staff can create, translate, publish, and reorder products, categories, brands, pages, and images without developer help.
- [ ] Exactly one cover image per product, enforced by the database.
- [ ] RFQ submissions are stored, rate-limited, honeypot-protected, and emailed.
- [ ] No price appears in HTML, JSON-LD, or markdown artifacts.
- [ ] `certifications` may be empty without breaking rendering or emitting empty schema nodes.
- [ ] `/llms.txt`, `/llms-full.txt`, `/katalog.md`, and `/sitemap.xml` serve correctly and exclude unpublished records.
- [ ] `Accept: text/markdown` returns markdown with `Vary: Accept`.
- [ ] All five hosting risks verified on the real account, with fallbacks applied where needed.
- [ ] `docs/deployment.md` describes a deploy an unfamiliar developer can repeat.

## Operational Work Tracked Outside This Plan

The design spec's §9 Layers 5 and 6 are **business-run activities, not code deliverables**. They are listed here so they are not mistaken for completed work when this plan finishes:

- **Layer 5 — third-party presence.** e-Katalog LKPP registration, Google Business Profile, Kemenkes regalkes alignment, a Wikidata `Organization` entity, Indonesian healthcare-directory listings, a current LinkedIn company page, and industry press. All greenfield — no existing presence to migrate. Owner: company, with developer support for the entity data that must match the site's `Organization` JSON-LD exactly.
- **Layer 6 — monitoring.** Monthly prompt tracking across ChatGPT, Perplexity, Google AI Overviews, and Copilot for ~20 priority queries, recording citation rate and recommendation rate separately. Manual spreadsheet is sufficient at this scale.

Nothing in the code depends on these, and these do not block launch. They determine whether the AEO layer actually produces visibility.

## Phase 2 (Not In This Plan)

Blog functionality: `posts` and `post_categories` tables (schema pre-designed in the spec §5.2), Filament resources, public routes under `/blog`, `Article` JSON-LD, and comparison content with `ItemList`. Requires a separate plan.
