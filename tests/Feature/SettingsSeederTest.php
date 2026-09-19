<?php

use App\Models\Setting;
use Database\Seeders\SettingsSeeder;

/**
 * Guards the seeded business data.
 *
 * The risk here is not that the seeder breaks; it is that someone "fixes" it
 * by filling in a plausible-looking email or WhatsApp number. The Maps listing
 * publishes neither, so inventing one would put a false contact address on a
 * page whose whole purpose is generating qualified enquiries.
 */
it('does not invent contact details the source listing does not publish', function () {
    $this->seed(SettingsSeeder::class);

    // Google Maps publishes an address and a phone number for this entity, and
    // nothing else. Anything more is fabrication.
    expect(Setting::get('address'))->not->toBeNull()
        ->and(Setting::get('contact_phone'))->not->toBeNull()
        ->and(Setting::get('contact_email'))->toBeNull()
        ->and(Setting::get('whatsapp'))->toBeNull();
});

it('seeds the geo and opening hours the Organization node needs', function () {
    $this->seed(SettingsSeeder::class);

    // The spec requires "real geo" on the global Organization node. These keys
    // are the only source for it.
    expect(Setting::get('latitude'))->toBe('-6.1926502')
        ->and(Setting::get('longitude'))->toBe('106.8489722')
        ->and(Setting::get('opening_hours'))->toBe('Mo-Fr 08:00-17:00');
});

it('is idempotent so a re-run refreshes rather than duplicating', function () {
    $this->seed(SettingsSeeder::class);
    $first = Setting::query()->count();

    $this->seed(SettingsSeeder::class);

    expect(Setting::query()->count())->toBe($first)
        ->and(Setting::get('company_name'))->toBe('PT Medquest Mitra Global');
});
