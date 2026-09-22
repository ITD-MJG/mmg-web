<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

/**
 * The tag vocabulary for the seeded catalogue.
 *
 * Tags describe what a product *is* across categories: a temperature class
 * ("ultra low temperature freezer" spans Arctiko's ULT range), an instrument
 * role ("microplate reader" spans the Infinite and Spark families), or a
 * supply type ("disposable tip"). They are derived from the real product
 * families seeded from the sales system, not invented, so every tag here is
 * attached to at least one seeded product.
 *
 * Re-runnable: rows are matched on `slug`.
 */
class TagSeeder extends Seeder
{
    /**
     * @var list<array{slug: string, id: string, en: string, sort: int}>
     */
    private const TAGS = [
        ['slug' => 'microplate-reader', 'id' => 'Microplate Reader', 'en' => 'Microplate Reader', 'sort' => 10],
        ['slug' => 'multimode-reader', 'id' => 'Multimode Reader', 'en' => 'Multimode Reader', 'sort' => 20],
        ['slug' => 'microplate-washer', 'id' => 'Microplate Washer', 'en' => 'Microplate Washer', 'sort' => 30],
        ['slug' => 'ultra-low-temperature-freezer', 'id' => 'Freezer Suhu Ultra Rendah', 'en' => 'Ultra Low Temperature Freezer', 'sort' => 40],
        ['slug' => 'biomedical-freezer', 'id' => 'Freezer Biomedis', 'en' => 'Biomedical Freezer', 'sort' => 50],
        ['slug' => 'blood-bank-refrigerator', 'id' => 'Kulkas Bank Darah', 'en' => 'Blood Bank Refrigerator', 'sort' => 60],
        ['slug' => 'laboratory-refrigerator', 'id' => 'Kulkas Laboratorium', 'en' => 'Laboratory Refrigerator', 'sort' => 70],
        ['slug' => 'pharmaceutical-refrigerator', 'id' => 'Kulkas Farmasi', 'en' => 'Pharmaceutical Refrigerator', 'sort' => 80],
        ['slug' => 'cell-imaging', 'id' => 'Pencitraan Sel', 'en' => 'Cell Imaging', 'sort' => 90],
        ['slug' => 'consumable', 'id' => 'Bahan Habis Pakai', 'en' => 'Consumable', 'sort' => 100],
    ];

    public function run(): void
    {
        foreach (self::TAGS as $tag) {
            Tag::updateOrCreate(
                ['slug' => $tag['slug']],
                [
                    'name' => ['id' => $tag['id'], 'en' => $tag['en']],
                    'sort_order' => $tag['sort'],
                    'is_published' => true,
                ],
            );
        }
    }
}
