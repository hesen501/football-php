<?php

namespace Database\Seeders;

use App\Modules\Booking\Database\Seeders\BookingItemSeeder;
use App\Modules\Booking\Database\Seeders\BookingSeeder;
use App\Modules\Booking\Database\Seeders\PlatformSettingSeeder;
use App\Modules\Field\Database\Seeders\FieldSeeder;
use App\Modules\Item\Database\Seeders\ItemSeeder;
use App\Modules\Media\Database\Seeders\MediaSeeder;
use App\Modules\User\Database\Seeders\RoleAndPermissionSeeder;
use App\Modules\User\Database\Seeders\UserSeeder;
use App\Modules\Venue\Database\Seeders\VenueSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * The first block (roles/permissions, the SUPER_ADMIN account, the
     * platform commission-rate setting) is the minimum every environment
     * needs to function — including the test suite, which runs this whole
     * class via RefreshDatabase (see tests/TestCase.php: `$seed = true`).
     *
     * The second block is realistic dev/demo data — venues, fields, items,
     * media/images, bookings, booking items — and is intentionally skipped in `testing`:
     * it's randomly generated, sized for "looks like a real app" rather
     * than "fast to build once per test run", and no test asserts against
     * it. UserSeeder follows the same split internally (see that class).
     */
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            UserSeeder::class,
            PlatformSettingSeeder::class,
        ]);

        if (app()->environment('testing')) {
            return;
        }

        $this->call([
            VenueSeeder::class,
            FieldSeeder::class,
            ItemSeeder::class,
            MediaSeeder::class,
            BookingSeeder::class,
            BookingItemSeeder::class,
        ]);
    }
}
