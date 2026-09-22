<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'category_id' => ProductCategory::factory(),
            'slug' => Str::slug($name).'-'.Str::random(5),
            'name' => ['id' => $name, 'en' => $name],
            'short_description' => [
                'id' => fake()->sentence(),
                'en' => fake()->sentence(),
            ],
            'description' => [
                'id' => fake()->paragraph(),
                'en' => fake()->paragraph(),
            ],
            'specs' => ['Ukuran' => '10x15cm'],
            'is_published' => true,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn () => ['is_published' => false]);
    }

    /**
     * Attach tags after the product exists, since `tags` is a pivot relation
     * and cannot be set as a plain attribute on the definition array.
     */
    public function withTags(int $count = 2): static
    {
        return $this->afterCreating(function (Product $product) use ($count): void {
            $product->tags()->attach(Tag::factory()->count($count)->create());
        });
    }
}
