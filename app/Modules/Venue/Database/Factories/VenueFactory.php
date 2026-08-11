<?php

namespace App\Modules\Venue\Database\Factories;

use App\Modules\Venue\Enums\VenueStatus;
use App\Modules\Venue\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Venue>
 */
class VenueFactory extends Factory
{
    protected $model = Venue::class;

    public function definition(): array
    {
        $name = fake()->company().' Arena';

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 99999),
            'description' => fake()->paragraph(),
            'address' => fake()->streetAddress(),
            'city' => fake()->randomElement(['Baku', 'Ganja', 'Sumqayit', 'Mingachevir']),
            'latitude' => fake()->latitude(40, 41),
            'longitude' => fake()->longitude(49, 50),
            'phone' => fake()->numerify('+994#########'),
            'email' => fake()->unique()->companyEmail(),
            'status' => VenueStatus::ACTIVE,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => VenueStatus::INACTIVE,
        ]);
    }
}
