<?php

namespace App\Modules\User\Policies;

use App\Modules\User\Models\User;

/**
 * SUPER_ADMIN bypasses all checks via Gate::before (see AuthModuleServiceProvider),
 * so every method here only needs to handle the non-super-admin cases:
 * only SUPER_ADMIN may manage other users (VENUE_MANAGERs/CUSTOMERs manage
 * only their own profile, which goes through the Auth module's "me" endpoints,
 * not this admin-facing policy).
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('users.viewAny');
    }

    public function view(User $user, User $target): bool
    {
        return $user->hasPermissionTo('users.viewAny');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('users.create');
    }

    public function update(User $user, User $target): bool
    {
        return $user->hasPermissionTo('users.update');
    }

    public function delete(User $user, User $target): bool
    {
        return $user->hasPermissionTo('users.delete');
    }
}
