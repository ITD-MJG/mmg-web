<?php

/*
|--------------------------------------------------------------------------
| UI chrome (Indonesian)
|--------------------------------------------------------------------------
|
| Interface copy only. Business data — company name, address, phone, email,
| WhatsApp — lives in the `settings` table and is read through Setting::get(),
| never from here: it is single-valued, not per-locale.
|
| Indonesian is the default locale and therefore the source of truth. Keep
| this file and lang/en/ui.php in exact key parity; a test asserts it, because
| a missing EN key silently falls back to Indonesian and hides the omission.
|
*/

return [
    'nav' => [
        'products' => 'Produk',
        'principals' => 'Principal',
        'about' => 'Tentang Kami',
        'contact' => 'Kontak',
        'cta' => 'Minta Penawaran',
        'toggle' => 'Buka menu',
        'skip' => 'Lompat ke konten',
        'primary' => 'Navigasi utama',
    ],

    'hero' => [
        'heading' => 'Solusi Alat Kesehatan untuk Fasilitas Kesehatan Anda',
        'subtext' => 'Distributor alat kesehatan terpercaya untuk rumah sakit, klinik, laboratorium, dan apotek di seluruh Indonesia.',
        'cta' => 'Minta Penawaran',
        'secondary_cta' => 'Lihat Produk',
        'image_alt' => 'Peralatan kesehatan di fasilitas medis',
    ],

    'sections' => [
        'facilities' => 'Jenis Fasilitas Kesehatan',
        'principal' => 'Principal',
        'products' => 'Produk Terbaru',
        'contact' => 'Hubungi Kami',
    ],

    'products' => [
        'cta' => 'Lihat Semua Produk',
        'empty' => 'Belum ada produk untuk ditampilkan.',
        'detail' => 'Lihat Detail',
    ],

    'contact' => [
        'address' => 'Alamat',
        'phone' => 'Telepon',
        'email' => 'Email',
        'whatsapp' => 'WhatsApp',
        'map_title' => 'Peta lokasi',
        'unconfigured' => 'Informasi kontak belum tersedia. Silakan hubungi kami melalui formulir permintaan penawaran.',
    ],

    'footer' => [
        'address' => 'Alamat',
        'nav_heading' => 'Navigasi',
        'contact_heading' => 'Kontak',
        'tagline' => 'Distributor alat kesehatan untuk fasilitas kesehatan di Indonesia.',
    ],
];
