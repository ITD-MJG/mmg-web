<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'category_id' => Category::factory(),
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
}
