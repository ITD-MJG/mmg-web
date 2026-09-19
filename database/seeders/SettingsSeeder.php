<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * The company's real business data.
 *
 * These are facts, not secrets: they are the same values published on the
 * company's Google Maps listing, so they belong in the repository rather than
 * in `.env`. An admin can still override any of them from the settings page,
 * which upserts the keys it owns without touching anything else.
 *
 * Every key here is read by something: `Setting::get()` call sites in the nav,
 * footer, home page, and the JSON-LD builder. Nothing is seeded speculatively.
 *
 * Source: the MMG Head Office listing on Google Maps.
 *   Address  Menara Salemba 7Fl, Jl. Salemba Raya No.5-5A, Paseban, Senen,
 *            Central Jakarta 10440
 *   Phone    (021) 39842961
 *   Hours    Monday to Friday, 08:00-17:00
 *   Geo      -6.1926502, 106.8489722
 *   Plus     RR4X+WH Paseban
 *
 * Re-runnable: every write is an upsert on the setting key, so running the
 * seeder again refreshes the values without creating duplicates.
 */
class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // Registered legal name, as published. The logo carries the
            // marketing identity, so this is used for the accessible name, the
            // copyright line, and the Organization JSON-LD node.
            'company_name' => 'PT Medquest Mitra Global',

            // The official mark, shipped as a public asset rather than an
            // upload so a fresh deploy has it. Resolved through `asset()`.
            'logo' => 'images/MMG-logo.png',

            'address' => 'Menara Salemba, 7th Floor, Jl. Salemba Raya No. 5-5A, Paseban, Senen, Jakarta Pusat 10440',

            // Stored exactly as published, Indonesian domestic format. The
            // settings page imposes no format rule, so this round-trips.
            'contact_phone' => '(021) 39842961',

            // Maps publishes no email or WhatsApp for this entity. Left unset
            // rather than invented: every view that renders them is guarded by
            // `filled()`, so the sections degrade instead of printing a
            // placeholder that looks like real contact data.
            'contact_email' => null,
            'whatsapp' => null,

            // Geo, required by the spec's Organization node ("address
            // (Jakarta, real geo)"). NOTE: the phase 1 plan's Task 18 does not
            // currently emit `geo` from these keys; that is a gap in the plan
            // text, not in this data. The values are seeded here so the task
            // has them available.
            //
            // Stored as strings because the settings table is key/value JSON
            // and a float would round-trip with locale-dependent formatting.
            'latitude' => '-6.1926502',
            'longitude' => '106.8489722',
            'plus_code' => 'RR4X+WH Paseban, Jakarta Pusat',

            // Maps publishing hours, as a schema.org openingHours string.
            'opening_hours' => 'Mo-Fr 08:00-17:00',

            // Meta defaults. Written for a human scanning a search result and
            // naming the actual audience, not a keyword list.
            'default_meta_title' => 'PT Medquest Mitra Global - Distributor Alat Kesehatan Jakarta',
            'default_meta_description' => 'Distributor alat kesehatan untuk rumah sakit, klinik, laboratorium, dan apotek di seluruh Indonesia. Kantor pusat di Menara Salemba, Jakarta Pusat.',

            // No social profiles are published on the Maps listing, so the
            // JSON-LD `sameAs` array stays empty rather than pointing at
            // profiles that may not be the company's.
            'socials' => [],
        ];

        foreach ($settings as $key => $value) {
            Setting::set($key, $value);
        }
    }
}
