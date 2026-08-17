<?php

namespace App\Modules\User\Database\Seeders;

use App\Modules\User\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    /** @var array<int, string> */
    private const PERMISSIONS = [
        'venues.viewAny', 'venues.create', 'venues.update', 'venues.delete',
        'fields.viewAny', 'fields.create', 'fields.update', 'fields.delete',
        'bookings.viewAny', 'bookings.create', 'bookings.update', 'bookings.cancel',
        'users.viewAny', 'users.create', 'users.update', 'users.delete',
        // items.* is intentionally not granted to VENUE_MANAGER below — items
        // are a single global catalog shared by every venue (no venue_id of
        // their own), so unlike fields.*, letting any manager edit them would
        // let one venue affect every other venue's add-on pricing/catalog.
        // Managing the catalog is SUPER_ADMIN-only; browsing *active* items to
        // add one to a booking needs no permission at all (see ItemPolicy).
        'items.viewAny', 'items.create', 'items.update', 'items.delete',
        'dashboard.view',
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Role::findOrCreate()/findByName() both accept a BackedEnum
        // directly (see UserRole's docblock) — no ->value needed.
        foreach (UserRole::cases() as $role) {
            Role::findOrCreate($role, 'web');
        }

        foreach (UserRole::cases() as $role) {
            $permissions = $this->permissionsFor($role);

            if ($permissions === []) {
                continue;
            }

            Role::findByName($role, 'web')->syncPermissions($permissions);
        }
    }

    /**
     * SUPER_ADMIN gets an empty array — it bypasses every check via
     * Gate::before() (see AuthModuleServiceProvider), so it needs no
     * explicit permission assignments.
     *
     * @return array<int, string>
     */
    private function permissionsFor(UserRole $role): array
    {
        return match ($role) {
            UserRole::SUPER_ADMIN => [],
            UserRole::VENUE_MANAGER => [
                'venues.viewAny', 'venues.create', 'venues.update',
                'fields.viewAny', 'fields.create', 'fields.update', 'fields.delete',
                'bookings.viewAny', 'bookings.create', 'bookings.update', 'bookings.cancel',
                'dashboard.view',
            ],
            UserRole::CUSTOMER => [
                'bookings.create', 'bookings.cancel',
            ],
        };
    }
}
