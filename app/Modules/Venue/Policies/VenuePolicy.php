<?php

namespace App\Modules\Venue\Policies;

use App\Modules\User\Models\User;
use App\Modules\Venue\Models\Venue;

/**
 * SUPER_ADMIN bypasses every method here via Gate::before (see
 * AuthModuleServiceProvider) — these only run for VENUE_MANAGER (and any
 * other role, which fails the permission check immediately since only
 * VENUE_MANAGER holds venues.* permissions besides SUPER_ADMIN).
 */
class VenuePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('venues.viewAny');
    }

    public function view(User $user, Venue $venue): bool
    {
        return $user->hasPermissionTo('venues.viewAny') && $venue->isManagedBy($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('venues.create');
    }

    public function update(User $user, Venue $venue): bool
    {
        return $user->hasPermissionTo('venues.update') && $venue->isManagedBy($user);
    }

    public function delete(User $user, Venue $venue): bool
    {
        // Only SUPER_ADMIN holds venues.delete — deleting/deactivating a
        // venue outright is a moderation action reserved for the platform,
        // not something a venue's own managers can do to themselves.
        // A manager can still close a venue via status=INACTIVE (update()).
        return $user->hasPermissionTo('venues.delete');
    }

    /**
     * Adding/removing co-managers is intentionally SUPER_ADMIN-only for the
     * MVP: letting a manager grant venue access to arbitrary other users
     * would be a privilege-escalation path. No permission is seeded for this
     * ability, so it always returns false here (SUPER_ADMIN never reaches
     * this method, per Gate::before).
     */
    public function manageManagers(User $user, Venue $venue): bool
    {
        return false;
    }
}
