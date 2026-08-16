<?php

namespace App\Modules\Auth\Services;

use App\Modules\User\Enums\UserStatus;
use App\Modules\User\Models\User;
use App\Shared\Exceptions\BusinessRuleException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Authenticates a user for the admin panel and issues a Sanctum token.
     * Only SUPER_ADMIN / VENUE_MANAGER accounts may use this surface —
     * CUSTOMER-only accounts are rejected here even with correct credentials.
     *
     * @return array{0: User, 1: string} [$user, $plainTextToken]
     */
    public function attemptAdminLogin(string $email, string $password): array
    {
        $user = $this->attemptLogin($email, $password);

        if (! $user->hasAnyRole(['SUPER_ADMIN', 'VENUE_MANAGER'])) {
            throw ValidationException::withMessages([
                'email' => ['You do not have access to the admin panel.'],
            ]);
        }

        return [$user, $user->createToken('admin-panel')->plainTextToken];
    }

    /**
     * Authenticates a user for the customer app. Open to any active account
     * — a VENUE_MANAGER logging in here to book a field as a customer is
     * legitimate, unlike the reverse (see attemptAdminLogin).
     *
     * @return array{0: User, 1: string} [$user, $plainTextToken]
     */
    public function attemptCustomerLogin(string $email, string $password): array
    {
        $user = $this->attemptLogin($email, $password);

        return [$user, $user->createToken('customer-app')->plainTextToken];
    }

    /** @param array{name: string, email: string, phone?: string|null, password: string} $data
     * @return array{0: User, 1: string} [$user, $plainTextToken]
     */
    public function register(array $data): array
    {
        $user = DB::transaction(function () use ($data) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
                'status' => UserStatus::ACTIVE->value,
            ]);

            $user->assignRole('CUSTOMER');

            return $user;
        });

        $user->sendEmailVerificationNotification();

        return [$user->load(['roles', 'avatarMedia']), $user->createToken('customer-app')->plainTextToken];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }

    /**
     * Always succeeds from the caller's point of view regardless of whether
     * the email is registered — deliberately not distinguishing the two, to
     * avoid leaking which emails have accounts (user enumeration).
     */
    public function sendPasswordResetLink(string $email): void
    {
        Password::sendResetLink(['email' => $email]);
    }

    /** @param array{token: string, email: string, password: string} $credentials */
    public function resetPassword(array $credentials): void
    {
        $status = Password::reset(
            $credentials,
            fn (User $user, string $password) => $user->forceFill(['password' => $password])->save(),
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw new BusinessRuleException(
                'This password reset link is invalid or has expired.',
                'INVALID_RESET_TOKEN',
                400,
            );
        }
    }

    private function attemptLogin(string $email, string $password): User
    {
        $user = User::query()->where('email', $email)->first();

        // Failures render as a 422 validation error on the `email` field
        // rather than a bare 401, matching Laravel's own login convention
        // (Breeze/Fortify) and avoiding a separate response shape just for this.
        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        if (! $user->isActive()) {
            throw ValidationException::withMessages([
                'email' => ['This account has been suspended.'],
            ]);
        }

        return $user->load(['roles', 'avatarMedia']);
    }
}
