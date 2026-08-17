<?php

namespace App\Modules\User\Database\Seeders;

use App\Modules\User\Enums\UserRole;
use App\Modules\User\Models\User;
use Illuminate\Database\Seeder;

/**
 * Dev/local seed only — a real deployment should create its first
 * SUPER_ADMIN through a proper provisioning step, not a fixed credential.
 *
 * Replaces the old AdminUserSeeder: still guarantees the one SUPER_ADMIN
 * account every test/dev environment relies on being able to log in as,
 * then (outside `testing`, see DatabaseSeeder) fills out the rest of the
 * roster — extra admins, venue managers, and customers — that VenueSeeder/
 * BookingSeeder attach data to.
 *
 * -----------------------------------------------------------------------
 * Seeded credentials (all passwords: "password")
 * -----------------------------------------------------------------------
 * SUPER_ADMIN   admin@footballbooking.test      Super Admin
 * SUPER_ADMIN   admin2@footballbooking.test     Elvin Mammadli
 * SUPER_ADMIN   admin3@footballbooking.test     Aysel Huseynli
 * VENUE_MANAGER manager1@footballbooking.test   Tural Aliyev
 * VENUE_MANAGER manager2@footballbooking.test   Nigar Qasimova
 * VENUE_MANAGER manager3@footballbooking.test   Rashad Ismayilov
 * VENUE_MANAGER manager4@footballbooking.test   Leyla Abbasova
 * VENUE_MANAGER manager5@footballbooking.test   Kamran Huseynov
 * CUSTOMER      customer1..16@footballbooking.test (see self::CUSTOMERS)
 * -----------------------------------------------------------------------
 *
 * This class only ever calls firstOrCreate() keyed by email, so reruns
 * (e.g. `php artisan db:seed`) never duplicate accounts or reassign roles
 * that were already granted.
 */
class UserSeeder extends Seeder
{
    /** @var array<string, string> */
    private const ADMINS = [
        'admin2@footballbooking.test' => 'Elvin Mammadli',
        'admin3@footballbooking.test' => 'Aysel Huseynli',
    ];

    /** @var array<string, string> */
    public const MANAGERS = [
        'manager1@footballbooking.test' => 'Tural Aliyev',
        'manager2@footballbooking.test' => 'Nigar Qasimova',
        'manager3@footballbooking.test' => 'Rashad Ismayilov',
        'manager4@footballbooking.test' => 'Leyla Abbasova',
        'manager5@footballbooking.test' => 'Kamran Huseynov',
    ];

    /** @var array<int, string> */
    public const CUSTOMERS = [
        'Namig Qarayev', 'Farid Nasibov', 'Samir Balayev', 'Orkhan Mammadov',
        'Kanan Suleymanov', 'Vusal Rzayev', 'Anar Jafarov', 'Murad Guliyev',
        'Ilkin Abdullayev', 'Aydan Karimova', 'Sabina Aliyeva', 'Gunel Hasanova',
        'Konul Ismayilova', 'Nargiz Rustamova', 'Turkan Veliyeva', 'Elvin Sadigov',
    ];

    public function run(): void
    {
        // Always seeded, in every environment — the one account every
        // test/dev workflow assumes it can log into the admin panel with.
        $this->createUser('admin@footballbooking.test', 'Super Admin', UserRole::SUPER_ADMIN, 1);

        // The full demo roster is dev-only: seeding ~20 extra accounts on
        // every test run (RefreshDatabase re-seeds per Pest run — see
        // tests/TestCase.php) would be pure overhead with no test relying
        // on them. See DatabaseSeeder for the matching guard on the rest
        // of the demo bundle (venues/fields/items/bookings).
        if (app()->environment('testing')) {
            return;
        }

        $sequence = 2;

        foreach (self::ADMINS as $email => $name) {
            $this->createUser($email, $name, UserRole::SUPER_ADMIN, $sequence++);
        }

        foreach (self::MANAGERS as $email => $name) {
            $this->createUser($email, $name, UserRole::VENUE_MANAGER, $sequence++);
        }

        foreach (self::CUSTOMERS as $index => $name) {
            $email = sprintf('customer%d@footballbooking.test', $index + 1);
            $this->createUser($email, $name, UserRole::CUSTOMER, $sequence++);
        }
    }

    private function createUser(string $email, string $name, UserRole $role, int $phoneSeq): void
    {
        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'phone' => sprintf('+99450%07d', $phoneSeq),
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }
    }
}
