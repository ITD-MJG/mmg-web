<?php

namespace Database\Factories;

use App\Enums\InquiryStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class InquiryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'company' => fake()->company(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'message' => fake()->paragraph(),
            'locale' => 'id',
            'status' => InquiryStatus::New,
            // Mirrors the controller's keyed hash: raw IPs are never stored.
            'ip_hash' => hash('sha256', fake()->ipv4().config('app.key')),
            'source_path' => '/produk/contoh',
        ];
    }
}
