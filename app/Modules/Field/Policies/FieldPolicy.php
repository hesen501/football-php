<?php

namespace App\Modules\Field\Policies;

use App\Modules\Field\Models\Field;
use App\Modules\User\Models\User;
use App\Modules\Venue\Models\Venue;

/**
 * A field has no ownership of its own — "can this user touch this field"
 * always reduces to "does this user manage the field's venue". SUPER_ADMIN
 * bypasses everything via Gate::before, as usual.
 */
class FieldPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('fields.viewAny');
    }

    public function view(User $user, Field $field): bool
    {
        return $user->hasPermissionTo('fields.viewAny') && $field->venue->isManagedBy($user);
    }

    /**
     * Called as can('create', [Field::class, $venue]) — there's no Field
     * instance yet, so the ownership check is against the parent Venue.
     */
    public function create(User $user, Venue $venue): bool
    {
        return $user->hasPermissionTo('fields.create') && $venue->isManagedBy($user);
    }

    public function update(User $user, Field $field): bool
    {
        return $user->hasPermissionTo('fields.update') && $field->venue->isManagedBy($user);
    }

    public function delete(User $user, Field $field): bool
    {
        return $user->hasPermissionTo('fields.delete') && $field->venue->isManagedBy($user);
    }
}
