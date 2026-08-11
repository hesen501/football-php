<?php

namespace Database\Seeders;

use App\Modules\Booking\Database\Seeders\PlatformSettingSeeder;
use App\Modules\User\Database\Seeders\AdminUserSeeder;
use App\Modules\User\Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            AdminUserSeeder::class,
            PlatformSettingSeeder::class,
        ]);
    }
}
