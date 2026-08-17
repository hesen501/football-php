<?php

namespace App\Modules\User\Enums;

/**
 * Mirrors the three Spatie role names the app actually seeds/checks
 * against (see RoleAndPermissionSeeder) — a typed alternative to passing
 * raw role-name strings around. spatie/laravel-permission's assignRole(),
 * hasRole(), hasAnyRole(), the role() query scope, and
 * Role::findOrCreate()/findByName() all accept a BackedEnum directly (see
 * HasRoles::getStoredRole()/scopeRole()), so this drops in anywhere a
 * 'SUPER_ADMIN'/'VENUE_MANAGER'/'CUSTOMER' string literal was used.
 */
enum UserRole: string
{
    case SUPER_ADMIN = 'SUPER_ADMIN';
    case VENUE_MANAGER = 'VENUE_MANAGER';
    case CUSTOMER = 'CUSTOMER';
}
