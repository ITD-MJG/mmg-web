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
        // The pair the hero's two buttons render. The primary leads to the
        // company profile and the secondary to the contact page, which is the
        // reverse of what the labels used to imply; the keys are named for the
        // position in the layout rather than for the destination, so moving a
        // destination is one edit here and none in the view.
        'cta' => 'Tentang Kami',
        'secondary_cta' => 'Hubungi Kami',
        'image_alt' => 'Alat analisis laboratorium dan seorang teknisi di laboratorium klinik',
    ],

    'sections' => [
        'facilities' => 'Jenis Fasilitas Kesehatan',
        'principal' => 'Principal',
        // Sits under the heading as the sentence that says what the row of
        // logos below it is. It names the register rather than the company's
        // own standing: "our current partners" is a claim the visitor can
        // check by looking, which is the only kind worth making here.
        'principal_subtext' => 'Didukung oleh principal terkemuka yang menjadi mitra resmi kami saat ini.',
        'products' => 'Produk Terbaru',
        'contact' => 'Hubungi Kami',
    ],

    'products' => [
        'cta' => 'Lihat Semua Produk',
        'empty' => 'Belum ada produk untuk ditampilkan.',
        'detail' => 'Lihat Detail',
    ],

    'catalog' => [
        'heading' => 'Produk',
        'intro' => 'Jelajahi katalog alat kesehatan kami berdasarkan kategori dan principal.',
        'filters' => 'Filter',
        'filter_category' => 'Kategori',
        'filter_principal' => 'Principal',
        'filter_all' => 'Semua',
        'search_label' => 'Cari produk',
        'search_placeholder' => 'Nama produk',
        'sort_label' => 'Urutkan',
        'sort_default' => 'Paling Sesuai',
        'sort_newest' => 'Terbaru',
        'sort_oldest' => 'Terlama',
        'sort_name_asc' => 'Nama A-Z',
        'sort_name_desc' => 'Nama Z-A',
        'submit' => 'Terapkan',
        'clear' => 'Hapus filter',
        'results' => 'Menampilkan :from sampai :to dari :total produk',
        'empty' => 'Tidak ada produk yang cocok dengan filter Anda.',
    ],

    'pagination' => [
        'previous' => 'Halaman sebelumnya',
        'next' => 'Halaman berikutnya',
        'label' => 'Navigasi halaman',
    ],

    'carousel' => [
        'previous' => 'Principal sebelumnya',
        'next' => 'Principal berikutnya',
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
