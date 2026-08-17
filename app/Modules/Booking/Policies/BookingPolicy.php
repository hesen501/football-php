<?php

namespace App\Modules\Booking\Policies;

use App\Modules\Booking\Models\Booking;
use App\Modules\Field\Models\Field;
use App\Modules\User\Models\User;

/**
 * Two distinct ownership paths converge here: on the admin surface,
 * "own" means "manages the booking's venue"; on the customer surface (added
 * in Phase 8), "own" means "is the booking's customer". Both view()/cancel()
 * check the customer path first since it's a cheap, no-query comparison.
 * SUPER_ADMIN bypasses everything via Gate::before, as usual.
 */
class BookingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('bookings.viewAny');
    }

    public function view(User $user, Booking $booking): bool
    {
        if ($booking->user_id === $user->id) {
            return true;
        }

        return $user->hasPermissionTo('bookings.viewAny') && $booking->venue->isManagedBy($user);
    }

    /**
     * Called as can('create', [Booking::class, $field]) from the admin
     * surface — there's no Booking instance yet, so the ownership check is
     * against the field's venue. $field may be null if field_id failed
     * validation entirely; in that case defer to the FormRequest's
     * validation rules for a clean 422 rather than a misleading 403.
     *
     * Called as can('create', Booking::class) (no $field) from the customer
     * surface — a CUSTOMER never owns a venue, so only the flat permission
     * check applies there, which the $field === null branch gives for free.
     */
    public function create(User $user, ?Field $field = null): bool
    {
        if (! $user->hasPermissionTo('bookings.create')) {
            return false;
        }

        return $field === null || $field->venue->isManagedBy($user);
    }

    public function update(User $user, Booking $booking): bool
    {
        return $user->hasPermissionTo('bookings.update') && $booking->venue->isManagedBy($user);
    }

    public function cancel(User $user, Booking $booking): bool
    {
        if ($booking->user_id === $user->id) {
            return true;
        }

        return $user->hasPermissionTo('bookings.cancel') && $booking->venue->isManagedBy($user);
    }

    /**
     * Governs POST/DELETE .../bookings/{booking}/items on both surfaces —
     * same ownership split as cancel(): a customer may modify their own
     * booking's items; on the admin surface it's gated by the same
     * 'bookings.update' permission used for other booking mutations (no
     * dedicated items permission — adding/removing an item is just another
     * way of updating a booking).
     */
    public function manageItems(User $user, Booking $booking): bool
    {
        if ($booking->user_id === $user->id) {
            return true;
        }

        return $user->hasPermissionTo('bookings.update') && $booking->venue->isManagedBy($user);
    }
}
