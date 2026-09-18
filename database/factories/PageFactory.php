<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PageFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->words(2, true);

        return [
            'slug' => Str::slug($title).'-'.Str::random(5),
            'title' => ['id' => $title, 'en' => $title],
            'body' => ['id' => fake()->paragraph(), 'en' => fake()->paragraph()],
            'is_published' => true,
        ];
    }
}
