<?php

namespace Database\Factories;

use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductImageFactory extends Factory
{
    protected $model = ProductImage::class;

    public function definition(): array
    {
        return [
            'path' => 'products/'.fake()->uuid().'.jpg',
            'alt' => ['id' => fake()->words(3, true), 'en' => fake()->words(3, true)],
            'is_cover' => false,
            'sort_order' => 0,
        ];
    }

    public function cover(): static
    {
        return $this->state(fn () => ['is_cover' => true]);
    }
}
