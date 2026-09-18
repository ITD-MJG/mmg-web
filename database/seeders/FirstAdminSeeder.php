<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class FirstAdminSeeder extends Seeder
{
    /**
     * Create the first admin account so a fresh deployment is administrable.
     * Skipped when any user already exists, so re-running it never duplicates
     * the account or resets its password.
     */
    public function run(): void
    {
        if (User::query()->exists()) {
            return;
        }

        $email = config('app.admin_email');
        $password = config('app.admin_password');

        if (blank($email) || blank($password)) {
            $this->command?->warn('FirstAdminSeeder skipped: set ADMIN_EMAIL and ADMIN_PASSWORD.');

            return;
        }

        $admin = User::create([
            'name' => 'Administrator',
            'email' => $email,
            'password' => $password,
        ]);

        $admin->assignRole(Role::findOrCreate('admin'));
    }
}
