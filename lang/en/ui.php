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
        'skip' => 'Skip to content',
        'primary' => 'Primary',
    ],

    'hero' => [
        'heading' => 'Medical Device Solutions for Your Healthcare Facility',
        'subtext' => 'A trusted medical device distributor for hospitals, clinics, laboratories, and pharmacies across Indonesia.',
        // See the Indonesian file: the keys name the button's position in the
        // layout, not its destination.
        'cta' => 'About Us',
        'secondary_cta' => 'Contact Us',
        'image_alt' => 'Laboratory analysers and a technician in a clinical laboratory',
    ],

    'sections' => [
        'facilities' => 'Healthcare Facility Types',
        'principal' => 'Principal',
        // See the Indonesian file for what this sentence is doing.
        'principal_subtext' => 'Backed by the leading principals who are our current official partners.',
        'products' => 'Latest Products',
        'contact' => 'Contact Us',
    ],

    'products' => [
        'cta' => 'View All Products',
        'empty' => 'No products to show yet.',
        'detail' => 'View Details',
    ],

    'catalog' => [
        'heading' => 'Products',
        'intro' => 'Browse our medical device catalogue by category and principal.',
        'filters' => 'Filters',
        'filter_category' => 'Category',
        'filter_principal' => 'Principal',
        'filter_all' => 'All',
        'search_label' => 'Search products',
        'search_placeholder' => 'Product name',
        'sort_label' => 'Sort by',
        'sort_default' => 'Relevance',
        'sort_newest' => 'Newest',
        'sort_oldest' => 'Oldest',
        'sort_name_asc' => 'Name A-Z',
        'sort_name_desc' => 'Name Z-A',
        'submit' => 'Apply',
        'clear' => 'Clear filters',
        'results' => 'Showing :from to :to of :total products',
        'empty' => 'No products match your filters.',
    ],

    'pagination' => [
        'previous' => 'Previous page',
        'next' => 'Next page',
        'label' => 'Pagination',
    ],

    'carousel' => [
        'previous' => 'Previous principals',
        'next' => 'Next principals',
    ],

    'contact' => [
        'address' => 'Address',
        'phone' => 'Phone',
        'email' => 'Email',
        'whatsapp' => 'WhatsApp',
        'map_title' => 'Location map',
        'unconfigured' => 'Contact details are not available yet. Please reach us through the quote request form.',
    ],

    'footer' => [
        'address' => 'Address',
        'nav_heading' => 'Navigation',
        'contact_heading' => 'Contact',
        'tagline' => 'Medical device distributor for healthcare facilities across Indonesia.',
    ],
];
