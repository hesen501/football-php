<?php

namespace App\Modules\User\Database\Seeders;

use App\Modules\User\Models\User;
use Illuminate\Database\Seeder;

/**
 * Dev/local seed only — a real deployment should create its first
 * SUPER_ADMIN through a proper provisioning step, not a fixed credential.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@footballbooking.test'],
            [
                'name' => 'Super Admin',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        if (! $admin->hasRole('SUPER_ADMIN')) {
            $admin->assignRole('SUPER_ADMIN');
        }
    }
}
