<?php

use App\Models\Principal;
use App\Models\Product;
use Database\Seeders\PrincipalSeeder;

/**
 * Guards the seeded principal register.
 *
 * The register is the list the business actually trades on, and the marquee
 * renders a logo for every entry that has one. Two failure modes are worth
 * pinning down: a logo path that does not resolve to a real file (the marquee
 * would render a broken image on the live site), and a re-run that orphans the
 * products pointing at a principal.
 */
it('gives every seeded principal a logo that exists on disk', function () {
    $this->seed(PrincipalSeeder::class);

    $withLogos = Principal::whereNotNull('logo')->get();

    expect($withLogos)->not->toBeEmpty();

    foreach ($withLogos as $principal) {
        // Seeded logos are public assets, so the stored path is relative to
        // the public root. `public_path()` is what the browser will request.
        expect(public_path($principal->logo))
            ->toBeFile("{$principal->name} points at a missing logo");
    }
});

it('frames every seeded logo as a square image', function () {
    $this->seed(PrincipalSeeder::class);

    $withLogos = Principal::whereNotNull('logo')->get();

    expect($withLogos)->not->toBeEmpty();

    foreach ($withLogos as $principal) {
        $path = public_path($principal->logo);

        // `getimagesize()` reads the header, so this checks the file the
        // browser will actually fetch rather than a stored width/height column
        // that could drift from it.
        $size = getimagesize($path);

        expect($size)->not->toBeFalse("{$principal->name}'s logo is not a readable image")
            ->and($size[0])->toBe(
                $size[1],
                "{$principal->name}'s logo is not 1:1 and will not fill the carousel frame",
            );
    }
});

it('leaves a principal without a logo unset rather than pointing at a placeholder', function () {
    $this->seed(PrincipalSeeder::class);

    // Biochem has no published mark. It must fall back to its name, not to
    // some other brand's file.
    $biochem = Principal::where('slug', 'biochem')->firstOrFail();

    expect($biochem->logo)->toBeNull()
        ->and($biochem->logoUrl())->toBeNull();
});

it('publishes every principal it seeds', function () {
    $this->seed(PrincipalSeeder::class);

    expect(Principal::where('is_published', false)->count())->toBe(0)
        ->and(Principal::count())->toBeGreaterThan(20);
});

it('keeps the principal the catalog products already reference', function () {
    $this->seed(PrincipalSeeder::class);

    // Six published products hang off Mindray through `principal_id`. The
    // register does not include it, so the seeder has to carry it forward
    // explicitly; otherwise those products would be left with no brand.
    $mindray = Principal::where('slug', 'mindray')->first();

    expect($mindray)->not->toBeNull();

    Product::factory()->create(['principal_id' => $mindray->id]);

    $this->seed(PrincipalSeeder::class);

    expect(Product::whereNotNull('principal_id')->count())->toBe(1)
        ->and(Principal::where('slug', 'mindray')->exists())->toBeTrue();
});

it('is idempotent and drops only the scaffold names it replaces', function () {
    $this->seed(PrincipalSeeder::class);
    $count = Principal::count();

    // A principal added by hand through the admin panel must survive a
    // deploy-time re-run: the cleanup is scoped to the seeded list, not a
    // blanket delete.
    $manual = Principal::factory()->create(['name' => 'Principal Manual']);

    $this->seed(PrincipalSeeder::class);

    expect(Principal::count())->toBe($count + 1)
        ->and(Principal::where('slug', $manual->slug)->exists())->toBeTrue();
});
