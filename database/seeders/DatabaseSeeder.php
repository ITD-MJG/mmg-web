<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            FirstAdminSeeder::class,
            SettingsSeeder::class,
            PrincipalSeeder::class,
            ProductCategorySeeder::class,
            TagSeeder::class,
        ]);
    }
}
