<?php

namespace App\Modules\User\Database\Factories;

use App\Modules\User\Enums\UserRole;
use App\Modules\User\Enums\UserStatus;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'phone' => fake()->unique()->numerify('+994#########'),
            'password' => static::$password ??= Hash::make('password'),
            'status' => UserStatus::ACTIVE,
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserStatus::SUSPENDED,
        ]);
    }

    public function superAdmin(): static
    {
        return $this->afterCreating(fn (User $user) => $user->assignRole(UserRole::SUPER_ADMIN));
    }

    public function venueManager(): static
    {
        return $this->afterCreating(fn (User $user) => $user->assignRole(UserRole::VENUE_MANAGER));
    }

    public function customer(): static
    {
        return $this->afterCreating(fn (User $user) => $user->assignRole(UserRole::CUSTOMER));
    }
}
