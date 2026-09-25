<?php

namespace Database\Seeders;

use App\Models\Principal;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tag;
use Database\Seeders\Concerns\SeedsProductCover;
use Illuminate\Database\Seeder;

/**
 * The first five Arctiko products in the catalogue.
 *
 * Rows come from the sales system's `products` table (principal "Arctiko",
 * principal id 2 there). `sku` is that table's `product_code` verbatim.
 *
 * `created_at` is set explicitly and spread across ten months for the same
 * reason as the Tecan set: the catalogue's sort dropdown orders on
 * `created_at`, and rows written in one pass would share a timestamp and make
 * "newest" and "oldest" render identically.
 *
 * Specs are the manufacturer's published datasheet figures (capacity in
 * litres, temperature range, item code, cooling technology). The item codes
 * match the `DAI` numbers that also appear on some of the sales register's
 * rows for these same models, which is how the two systems line up.
 *
 * Re-runnable: matched on `slug`.
 */
class ArctikoProductSeeder extends Seeder
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
            'slug' => 'arctiko-uluf-450-2m',
            'sku' => 'MMG-PRO-000005',
            'name' => ['id' => 'Arctiko ULUF 450-2M Ultra Low Temperature Freezer', 'en' => 'Arctiko ULUF 450-2M Ultra Low Temperature Freezer'],
            'short' => [
                'id' => 'Freezer suhu ultra rendah -86 sampai -40 derajat Celsius dengan teknologi kompresor tunggal dan pendinginan langsung.',
                'en' => 'Ultra low temperature freezer from -86 to -40 degrees Celsius with single compressor technology and direct cooling.',
            ],
            'description' => [
                'id' => 'ULUF 450-2M adalah freezer suhu ultra rendah dengan sistem kompresor tunggal dan pendinginan langsung, yang memberi keseragaman suhu lebih tinggi dan stabilitas suhu yang baik. Seri ULUF -86 derajat Celsius diproduksi dengan teknologi kompresor tunggal asli. Pintu berpemanas mencegah pembekuan pada area segel, dan unit dilengkapi kunci pintu serta roda untuk pemindahan.',
                'en' => 'The ULUF 450-2M is an ultra low temperature freezer with a single compressor system and direct cooling, giving higher temperature uniformity and good temperature stability. The ULUF -86 degrees Celsius series is produced with the original single compressor technology. A heated door prevents freezing at the seal, and the unit ships with a door lock and castors.',
            ],
            'category' => 'medical-equipment',
            'tags' => ['ultra-low-temperature-freezer', 'biomedical-freezer'],
            'specs' => [
                'Kode item' => 'DAI 1414',
                'Rentang suhu' => '-86 sampai -40 derajat Celsius',
                'Kapasitas' => '370 L',
                'Dimensi luar' => '720 x 885 x 1990 mm',
                'Teknologi pendinginan' => 'Kompresor tunggal, pendinginan langsung',
            ],
            'image' => 'images/products/arctiko-uluf-450-2m.jpg',
            'created_at' => '2025-07-21 09:00:00',
        ],
        [
            'slug' => 'arctiko-bbr-500-d',
            'sku' => 'MMG-PRO-000006',
            'name' => ['id' => 'Arctiko BBR 500-D Blood Bank Refrigerator', 'en' => 'Arctiko BBR 500-D Blood Bank Refrigerator'],
            'short' => [
                'id' => 'Kulkas bank darah 4 derajat Celsius dengan teknologi True Dual Cooling dan sertifikasi MDD kelas IIA.',
                'en' => 'Blood bank refrigerator at 4 degrees Celsius with True Dual Cooling technology and MDD class IIA certification.',
            ],
            'description' => [
                'id' => 'BBR 500-D dirancang untuk penyimpanan darah yang aman dan andal pada 4 derajat Celsius, dengan keseragaman suhu yang stabil dari atas ke bawah. Unit ini bersertifikat MDD 93/42/EEC kelas IIA, sehingga disetujui untuk menyimpan jaringan atau zat yang akan dimasukkan ke tubuh manusia. Untuk memenuhi persyaratan tersebut, unit dilengkapi probe terendam gliserin, controller keamanan tambahan, chart recorder, dan probe sekunder.',
                'en' => 'The BBR 500-D is designed for safe and reliable blood storage at 4 degrees Celsius, with stable temperature uniformity from top to bottom. It is certified under MDD 93/42/EEC class IIA, so it is approved for storing tissue or substances intended to be introduced into the human body. To meet those requirements it carries probes immersed in glycerin, an extra security controller, a chart recorder, and a secondary probe.',
            ],
            'category' => 'medical-equipment',
            'tags' => ['blood-bank-refrigerator', 'laboratory-refrigerator'],
            'specs' => [
                'Kode item' => 'DAI 0001',
                'Rentang suhu' => '4 derajat Celsius',
                'Kapasitas' => '532 L',
                'Dimensi luar' => '620 x 860 x 1997 mm',
                'Sertifikasi' => 'MDD 93/42/EEC kelas IIA',
                'Teknologi pendinginan' => 'True Dual Cooling',
            ],
            'image' => 'images/products/arctiko-bbr-500-d.jpg',
            'created_at' => '2025-08-05 13:30:00',
        ],
        [
            'slug' => 'arctiko-lre-490',
            'sku' => 'MMG-PRO-000007',
            'name' => ['id' => 'Arctiko LRE 490 Refrigerator', 'en' => 'Arctiko LRE 490 Refrigerator'],
            'short' => [
                'id' => 'Kulkas farmasi tegak 488 L dengan rentang suhu 2 sampai 8 derajat Celsius dan pendinginan udara paksa.',
                'en' => '488 L upright pharmaceutical refrigerator with a 2 to 8 degrees Celsius range and forced air cooling.',
            ],
            'description' => [
                'id' => 'LRE 490 dari seri Flexaline adalah kulkas tegak dengan pintu solid dan kapasitas 488 L, dirancang untuk memenuhi standar aplikasi farmasi. Suhu dikontrol antara 2 sampai 8 derajat Celsius dengan pendinginan udara paksa, dan unit memakai teknologi kompresor tunggal untuk kemudahan perawatan, konsumsi energi rendah, serta kebisingan yang lebih rendah. Refrigeran ramah lingkungan dan bebas HCFC serta CFC.',
                'en' => 'The LRE 490 from the Flexaline series is an upright refrigerator with a solid door and 488 L capacity, designed to meet pharmaceutical application standards. Temperature is controlled between 2 and 8 degrees Celsius with forced air cooling, and the unit uses single compressor technology for easier maintenance, low energy use, and lower noise. The refrigerant is eco friendly and free of HCFC and CFC.',
            ],
            'category' => 'medical-equipment',
            'tags' => ['laboratory-refrigerator', 'pharmaceutical-refrigerator'],
            'specs' => [
                'Kode item' => '1900028-01',
                'Rentang suhu' => '2 sampai 8 derajat Celsius',
                'Kapasitas' => '488 L',
                'Dimensi luar' => '780 x 710 x 1950 mm',
                'Teknologi pendinginan' => 'Pendinginan udara paksa',
            ],
            'image' => 'images/products/arctiko-lre-490.jpg',
            'created_at' => '2025-09-11 15:45:00',
        ],
        [
            'slug' => 'arctiko-pre-380',
            'sku' => 'MMG-PRO-000467',
            'name' => ['id' => 'Arctiko PRE 380 Refrigerator', 'en' => 'Arctiko PRE 380 Refrigerator'],
            'short' => [
                'id' => 'Kulkas farmasi tegak 395 L dengan rentang 2 sampai 8 derajat Celsius dan defrost otomatis.',
                'en' => '395 L upright pharmaceutical refrigerator with a 2 to 8 degrees Celsius range and automatic defrost.',
            ],
            'description' => [
                'id' => 'PRE 380 adalah kulkas farmasi tegak berkapasitas 395 L yang dirancang untuk kebutuhan aplikasi farmasi. Suhu dijaga pada 2 sampai 8 derajat Celsius dengan pendinginan udara paksa dan defrost otomatis. Seperti seri Flexaline lainnya, unit ini memakai kompresor tunggal, refrigeran ramah lingkungan, dan bebas HCFC serta CFC.',
                'en' => 'The PRE 380 is a 395 L upright pharmaceutical refrigerator designed for pharmaceutical application requirements. Temperature is held at 2 to 8 degrees Celsius with forced air cooling and automatic defrost. Like the rest of the Flexaline series it uses a single compressor, an eco friendly refrigerant, and is free of HCFC and CFC.',
            ],
            'category' => 'medical-equipment',
            'tags' => ['laboratory-refrigerator', 'pharmaceutical-refrigerator'],
            'specs' => [
                'Kode item' => '1900017-01',
                'Rentang suhu' => '2 sampai 8 derajat Celsius',
                'Kapasitas' => '395 L',
                'Dimensi luar' => '608 x 698 x 1840 mm',
                'Teknologi pendinginan' => 'Pendinginan udara paksa',
            ],
            'image' => 'images/products/arctiko-pre-380.jpg',
            'created_at' => '2025-10-27 10:10:00',
        ],
        [
            'slug' => 'arctiko-lfe-360',
            'sku' => 'MMG-PRO-000107',
            'name' => ['id' => 'Arctiko LFE 360 Biomedical Freezer', 'en' => 'Arctiko LFE 360 Biomedical Freezer'],
            'short' => [
                'id' => 'Freezer biomedis 356 L dengan rentang -25 sampai -15 derajat Celsius dan interior yang dapat disesuaikan.',
                'en' => '356 L biomedical freezer with a -25 to -15 degrees Celsius range and an adjustable interior.',
            ],
            'description' => [
                'id' => 'LFE 360 adalah freezer biomedis dengan kapasitas 356 L dan rentang suhu -25 sampai -15 derajat Celsius. Interiornya fleksibel dengan rak yang dapat disesuaikan, sehingga penyimpanan sampel dapat ditata sesuai kebutuhan. Unit dilengkapi kunci pintu untuk membatasi akses ke sampel yang disimpan serta alarm suhu visual dan audible.',
                'en' => 'The LFE 360 is a biomedical freezer with 356 L capacity and a -25 to -15 degrees Celsius temperature range. Its interior is flexible with adjustable shelves, so sample storage can be arranged to suit the workload. The unit has a built-in door lock to restrict access to stored samples, plus visual and audible temperature alarms.',
            ],
            'category' => 'medical-equipment',
            'tags' => ['biomedical-freezer'],
            'specs' => [
                'Kode item' => 'DAI 6000',
                'Rentang suhu' => '-25 sampai -15 derajat Celsius',
                'Kapasitas' => '356 L',
                'Dimensi luar' => '600 x 700 x 1865 mm',
                'Interior' => 'Rak dapat disesuaikan',
            ],
            'image' => 'images/products/arctiko-lfe-360.jpg',
            'created_at' => '2025-11-30 12:00:00',
        ],
    ];

    public function run(): void
    {
        $principal = Principal::where('slug', 'arctiko')->firstOrFail();

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
