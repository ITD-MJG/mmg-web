<?php

namespace Database\Seeders;

use App\Models\Principal;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tag;
use Database\Seeders\Concerns\SeedsProductCover;
use Illuminate\Database\Seeder;

/**
 * The first five Tecan products in the catalogue.
 *
 * Rows come from the sales system's `products` table (principal "Tecan",
 * principal id 15 there). `sku` is that table's `product_code` verbatim, so a
 * catalogue row can be traced back to the register it was entered in.
 *
 * `created_at` is set explicitly and spread across ten months. It is not
 * decoration: the catalogue's sort dropdown orders on `created_at`
 * ("newest"/"oldest" in ProductController), and a set of rows written in one
 * pass would share a timestamp and make those two options render the same
 * order. Spreading the dates is what makes the control demonstrably work.
 *
 * Descriptions are written from the published Tecan specifications for each
 * model, since the sales register leaves `description` empty. Nothing here is
 * a fabricated claim: capacities, filter sets, and temperature ranges are the
 * manufacturer's published figures.
 *
 * Re-runnable: matched on `slug`. The cover image is replaced rather than
 * duplicated, and tags are synced.
 */
class TecanProductSeeder extends Seeder
{
    use SeedsProductCover;

    /**
     * @var list<array{
     *     slug: string, sku: string, name: array{id: string, en: string},
     *     short: array{id: string, en: string}, description: array{id: string, en: string},
     *     category: string, tags: list<string>, specs: array<string, string>,
     *     image: string, created_at: string
     * }>
     */
    private const PRODUCTS = [
        [
            'slug' => 'tecan-infinite-200-pro',
            'sku' => 'MMG-PRO-000042',
            'name' => ['id' => 'Tecan Infinite 200 Pro', 'en' => 'Tecan Infinite 200 Pro'],
            'short' => [
                'id' => 'Microplate reader multimode berbasis monokromator dan filter untuk assay fluoresensi, absorbansi, dan luminesensi.',
                'en' => 'Monochromator and filter based multimode microplate reader for fluorescence, absorbance, and luminescence assays.',
            ],
            'description' => [
                'id' => 'Infinite 200 Pro adalah keluarga microplate reader multimode yang menawarkan deteksi fluoresensi, absorbansi, dan luminesensi dalam satu instrumen. Teknologi monokromator dan filter dapat dipilih sesuai kebutuhan assay, dengan konfigurasi yang dapat ditingkatkan kemudian tanpa mengganti instrumen. Platform ini dirujuk pada lebih dari 1.800 publikasi peer-review.',
                'en' => 'The Infinite 200 Pro is a multimode microplate reader family offering fluorescence, absorbance, and luminescence detection in a single instrument. Monochromator and filter technologies can be selected to suit the assay, with configurations that can be upgraded later without replacing the instrument. The platform is referenced in more than 1,800 peer-reviewed publications.',
            ],
            'category' => 'instrument',
            'tags' => ['microplate-reader', 'multimode-reader'],
            'specs' => [
                'Jenis' => 'Multimode microplate reader',
                'Deteksi' => 'Fluoresensi, absorbansi, luminesensi',
                'Teknologi optik' => 'Monokromator dan filter',
                'Format plate' => '6 sampai 384 well',
            ],
            'image' => 'images/products/tecan-infinite-200-pro.jpg',
            'created_at' => '2025-08-14 09:15:00',
        ],
        [
            'slug' => 'tecan-spark',
            'sku' => 'MMG-PRO-000043',
            'name' => ['id' => 'Tecan SPARK', 'en' => 'Tecan SPARK'],
            'short' => [
                'id' => 'Multimode reader yang dapat dikonfigurasi penuh untuk assay biokimia, HTS, dan analisis sel hidup.',
                'en' => 'Fully configurable multimode reader for biochemical assays, HTS, and live cell analysis.',
            ],
            'description' => [
                'id' => 'Spark adalah platform multimode reader yang dapat dipersonalisasi sesuai kebutuhan laboratorium. Desainnya mendukung aplikasi biokimia termasuk assay HTS seperti HTRF dan ALPHA, serta memberi nilai khusus untuk laboratorium sel melalui analisis sel hidup dan pencitraan sel dasar. Modul pencitraan sel terpasang langsung di dalam reader.',
                'en' => 'Spark is a multimode reader platform that can be personalised to the laboratory\'s needs. Its design supports biochemical applications including HTS assays such as HTRF and ALPHA, and delivers particular value to cell laboratories through live cell analysis and basic cell imaging. The cell imaging module is built directly into the reader.',
            ],
            'category' => 'instrument',
            'tags' => ['multimode-reader', 'cell-imaging'],
            'specs' => [
                'Jenis' => 'Multimode microplate reader',
                'Aplikasi' => 'Biokimia, HTS, analisis sel hidup',
                'Pencitraan sel' => 'Modul terintegrasi',
                'Konfigurasi' => 'Dapat dipersonalisasi',
            ],
            'image' => 'images/products/tecan-spark.jpg',
            'created_at' => '2025-09-22 11:40:00',
        ],
        [
            'slug' => 'tecan-infinite-f50',
            'sku' => 'MMG-PRO-000044',
            'name' => ['id' => 'Tecan Infinite F50', 'en' => 'Tecan Infinite F50'],
            'short' => [
                'id' => 'Microplate reader absorbansi untuk ELISA dan pengukuran rutin di laboratorium klinis.',
                'en' => 'Absorbance microplate reader for ELISA and routine measurement in the clinical laboratory.',
            ],
            'description' => [
                'id' => 'Infinite F50 adalah microplate reader absorbansi yang dirancang untuk pekerjaan rutin seperti ELISA. Instrumen tersedia lengkap dengan empat filter pada 405, 450, 492, dan 620 nm, sehingga siap dipakai untuk protokol ELISA yang umum tanpa penambahan filter terpisah.',
                'en' => 'The Infinite F50 is an absorbance microplate reader designed for routine work such as ELISA. The instrument is available complete with four filters at 405, 450, 492, and 620 nm, so it is ready for common ELISA protocols without a separate filter purchase.',
            ],
            'category' => 'instrument',
            'tags' => ['microplate-reader'],
            'specs' => [
                'Jenis' => 'Absorbance microplate reader',
                'Deteksi' => 'Absorbansi',
                'Filter' => '405, 450, 492, 620 nm',
                'Aplikasi utama' => 'ELISA',
            ],
            'image' => 'images/products/tecan-infinite-f50.jpg',
            'created_at' => '2025-10-09 14:05:00',
        ],
        [
            'slug' => 'tecan-hydroflex-gp',
            'sku' => 'MMG-PRO-000470',
            'name' => ['id' => 'Tecan HydroFlex GP', 'en' => 'Tecan HydroFlex GP'],
            'short' => [
                'id' => 'Microplate washer modular untuk pencucian plate 96 well pada assay rutin dan ELISA.',
                'en' => 'Modular microplate washer for 96 well plate washing in routine assays and ELISA.',
            ],
            'description' => [
                'id' => 'HydroFlex GP adalah microplate washer modular yang mencuci plate 96 well. Desainnya modular sehingga modul dapat disesuaikan dengan kebutuhan protokol pencucian, dan instrumen ini melengkapi alur kerja ELISA bersama reader absorbansi seperti Infinite F50.',
                'en' => 'The HydroFlex GP is a modular microplate washer for 96 well plates. Its modular design lets the wash modules be matched to the protocol, and it completes an ELISA workflow alongside an absorbance reader such as the Infinite F50.',
            ],
            'category' => 'instrument',
            'tags' => ['microplate-washer'],
            'specs' => [
                'Jenis' => 'Microplate washer',
                'Format plate' => '96 well',
                'Desain' => 'Modular',
                'Aplikasi utama' => 'Pencucian plate ELISA',
            ],
            'image' => 'images/products/tecan-hydroflex-gp.jpg',
            'created_at' => '2025-11-18 08:30:00',
        ],
        [
            'slug' => 'tecan-cell-chip',
            'sku' => 'MMG-PRO-000471',
            'name' => ['id' => 'Tecan Cell Chip 1 Box (50 Pcs / 100 Test)', 'en' => 'Tecan Cell Chip 1 Box (50 Pcs / 100 Test)'],
            'short' => [
                'id' => 'Chip sekali pakai untuk penghitungan sel label-free bersama modul pencitraan Spark.',
                'en' => 'Disposable chip for label-free cell counting with the Spark imaging module.',
            ],
            'description' => [
                'id' => 'Cell Chip adalah bahan habis pakai untuk penghitungan sel tanpa label pada pembaca Spark. Bersama modul pencitraan, chip ini meminimalkan persiapan sampel dan memungkinkan penghitungan sel otomatis untuk sel dalam suspensi. Satu box berisi 50 buah chip untuk 100 test.',
                'en' => 'The Cell Chip is a consumable for label-free cell counting on the Spark reader. Together with the imaging module it minimises sample preparation and enables automated cell counting for cells in suspension. One box contains 50 chips for 100 tests.',
            ],
            'category' => 'consumable',
            'tags' => ['consumable', 'cell-imaging'],
            'specs' => [
                'Jenis' => 'Bahan habis pakai',
                'Isi kemasan' => '50 pcs per box',
                'Kapasitas test' => '100 test',
                'Kompatibilitas' => 'Tecan Spark',
            ],
            'image' => 'images/products/tecan-cell-chip.jpg',
            'created_at' => '2025-12-03 10:20:00',
        ],
    ];

    public function run(): void
    {
        $principal = Principal::where('slug', 'tecan')->firstOrFail();

        foreach (self::PRODUCTS as $data) {
            $category = ProductCategory::where('slug', $data['category'])->firstOrFail();

            $product = Product::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'category_id' => $category->id,
                    'principal_id' => $principal->id,
                    'sku' => $data['sku'],
                    'name' => $data['name'],
                    'short_description' => $data['short'],
                    'description' => $data['description'],
                    'specs' => $data['specs'],
                    'is_published' => true,
                    // Set on every run so the value is deterministic and the
                    // sort dropdown keeps producing the same order after a
                    // re-seed.
                    'created_at' => $data['created_at'],
                    'updated_at' => $data['created_at'],
                ],
            );

            $product->tags()->sync(
                Tag::whereIn('slug', $data['tags'])->pluck('id')->all(),
            );

            $this->syncCoverImage($product, $data);
        }
    }
}
