<?php

namespace App\Modules\User\Services;

use App\Modules\User\Enums\UserStatus;
use App\Modules\User\Models\User;
use App\Shared\Http\Filtering\QueryParams;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class UserService
{
    /** @param array{role?: string, status?: string} $filters */
    public function list(QueryParams $params, array $filters): LengthAwarePaginator
    {
        return User::query()
            ->with('roles')
            ->when($filters['role'] ?? null, fn ($query, $role) => $query->whereHas(
                'roles',
                fn ($roles) => $roles->where('name', $role),
            ))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->applySearch($params, ['name', 'email', 'phone'])
            ->applySort($params, ['name', 'email', 'created_at'], '-created_at')
            ->paginate($params->perPage, page: $params->page);
    }

    /** @param array{name: string, email: string, phone?: string|null, password: string, role: string, status?: string} $data */
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
                'status' => $data['status'] ?? UserStatus::ACTIVE->value,
                // Admin-created accounts are trusted/pre-verified — no
                // verification email loop for accounts an admin sets up directly.
                'email_verified_at' => now(),
            ]);

            $user->assignRole($data['role']);

            return $user->load('roles');
        });
    }

    /** @param array{name?: string, email?: string, phone?: string|null, password?: string, role?: string, status?: string} $data */
    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $user->fill(array_intersect_key($data, array_flip(['name', 'email', 'phone', 'status'])));

            if (! empty($data['password'])) {
                $user->password = $data['password'];
            }

            $user->save();

            if (! empty($data['role'])) {
                $user->syncRoles([$data['role']]);
            }

            return $user->load('roles');
        });
    }

    public function delete(User $user): void
    {
        $user->delete();
    }

    /**
     * Self-service profile update — unlike update(), never touches role or
     * status (a user can't promote or reactivate themselves), and resets
     * email_verified_at when the email actually changes so the new address
     * gets re-verified rather than inheriting the old one's verified state.
     *
     * @param  array{name?: string, email?: string, phone?: string|null, password?: string}  $data
     */
    public function updateOwnProfile(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $emailChanged = isset($data['email']) && $data['email'] !== $user->email;

            $user->fill(array_intersect_key($data, array_flip(['name', 'email', 'phone'])));

            if ($emailChanged) {
                $user->email_verified_at = null;
            }

            if (! empty($data['password'])) {
                $user->password = $data['password'];
            }

            $user->save();

            if ($emailChanged) {
                $user->sendEmailVerificationNotification();
            }

            return $user->load('roles');
        });
    }
}
