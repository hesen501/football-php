<?php

namespace App\Modules\Item\Policies;

use App\Modules\Item\Models\Item;
use App\Modules\User\Models\User;

/**
 * Items are a global catalog with no venue/owner dimension (unlike Field,
 * where "can touch" always reduces to "manages the parent venue") — so, like
 * UserPolicy, this is a flat permission check throughout. SUPER_ADMIN
 * bypasses everything via Gate::before; items.* is intentionally not granted
 * to VENUE_MANAGER (see RoleAndPermissionSeeder) since one manager editing
 * the shared catalog would affect every other venue's bookings too. Browsing
 * *active* items (to add one to a booking) doesn't need any of this — see
 * the public Customer\ItemController, which requires no permission at all.
 */
class ItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('items.viewAny');
    }

    public function view(User $user, Item $item): bool
    {
        return $user->hasPermissionTo('items.viewAny');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('items.create');
    }

    public function update(User $user, Item $item): bool
    {
        return $user->hasPermissionTo('items.update');
    }

    public function delete(User $user, Item $item): bool
    {
        return $user->hasPermissionTo('items.delete');
    }
}
