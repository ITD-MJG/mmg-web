<?php

/*
|--------------------------------------------------------------------------
| UI chrome (English)
|--------------------------------------------------------------------------
|
| Interface copy only. Business data — company name, address, phone, email,
| WhatsApp — lives in the `settings` table and is read through Setting::get(),
| never from here: it is single-valued, not per-locale.
|
| This file must stay in exact key parity with lang/id/ui.php; a test asserts
| it. The app's fallback_locale is `id`, so a key missing here would render
| Indonesian on every /en page instead of failing loudly.
|
*/

return [
    'nav' => [
        'products' => 'Products',
        'principals' => 'Principal',
        'about' => 'About Us',
        'contact' => 'Contact',
        'cta' => 'Request a Quote',
        'toggle' => 'Open menu',
    ],

    'hero' => [
        'heading' => 'Medical Device Solutions for Your Healthcare Facility',
        'subtext' => 'A trusted medical device distributor for hospitals, clinics, laboratories, and pharmacies across Indonesia.',
        'cta' => 'Request a Quote',
    ],

    'sections' => [
        'principal' => 'Principal',
        'products' => 'Latest Products',
        'contact' => 'Contact Us',
    ],

    'products' => [
        'cta' => 'View All Products',
        'empty' => 'No products to show yet.',
        'detail' => 'View Details',
    ],

    'contact' => [
        'address' => 'Address',
        'phone' => 'Phone',
        'email' => 'Email',
        'whatsapp' => 'WhatsApp',
        'map_title' => 'Location map',
    ],

    'footer' => [
        'address' => 'Address',
        'nav_heading' => 'Navigation',
    ],
];
