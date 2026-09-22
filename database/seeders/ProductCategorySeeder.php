<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

/**
 * The catalogue's product categories.
 *
 * Sourced from the distinct `category` values on the sales system's `products`
 * table (INSTRUMENT, CONSUMABLE, REAGENT, and so on), normalised into the
 * bilingual, slugged shape the public catalogue and the admin expect. The
 * sales system stores that column as free text on the row, so this is where
 * the controlled vocabulary is actually defined.
 *
 * Re-runnable: rows are matched on `slug`, so a second run refreshes the
 * translations in place and leaves `products.category_id` pointing at the same
 * rows.
 */
class ProductCategorySeeder extends Seeder
{
    /**
     * @var list<array{slug: string, id: string, en: string, sort: int}>
     */
    private const CATEGORIES = [
        ['slug' => 'instrument', 'id' => 'Instrumen', 'en' => 'Instruments', 'sort' => 10],
        ['slug' => 'consumable', 'id' => 'Bahan Habis Pakai', 'en' => 'Consumables', 'sort' => 20],
        ['slug' => 'reagent', 'id' => 'Reagen', 'en' => 'Reagents', 'sort' => 30],
        ['slug' => 'medical-equipment', 'id' => 'Peralatan Medis', 'en' => 'Medical Equipment', 'sort' => 40],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $category) {
            ProductCategory::updateOrCreate(
                ['slug' => $category['slug']],
                [
                    'name' => ['id' => $category['id'], 'en' => $category['en']],
                    'description' => [
                        'id' => $category['id'].' yang didistribusikan Medquest Mitra Global.',
                        'en' => $category['en'].' distributed by Medquest Mitra Global.',
                    ],
                    'sort_order' => $category['sort'],
                    'is_published' => true,
                ],
            );
        }
    }
}
