<?php

namespace App\Modules\User\Database\Seeders;

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

    /**
     * SUPER_ADMIN is intentionally absent here — it bypasses every check via
     * Gate::before() (see AuthModuleServiceProvider), so it needs no explicit
     * permission assignments.
     *
     * @var array<string, array<int, string>>
     */
    private const ROLE_PERMISSIONS = [
        'VENUE_MANAGER' => [
            'venues.viewAny', 'venues.create', 'venues.update',
            'fields.viewAny', 'fields.create', 'fields.update', 'fields.delete',
            'bookings.viewAny', 'bookings.create', 'bookings.update', 'bookings.cancel',
            'dashboard.view',
        ],
        'CUSTOMER' => [
            'bookings.create', 'bookings.cancel',
        ],
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach (['SUPER_ADMIN', 'VENUE_MANAGER', 'CUSTOMER'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        foreach (self::ROLE_PERMISSIONS as $roleName => $permissions) {
            Role::findByName($roleName, 'web')->syncPermissions($permissions);
        }
    }
}
