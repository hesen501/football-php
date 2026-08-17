<?php

namespace App\Modules\Venue\Database\Factories;

use App\Modules\Venue\Models\Venue;
use App\Modules\Venue\Models\VenueWorkingHour;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VenueWorkingHour>
 */
class VenueWorkingHourFactory extends Factory
{
    protected $model = VenueWorkingHour::class;

    public function definition(): array
    {
        return [
            'venue_id' => Venue::factory(),
            'day_of_week' => fake()->numberBetween(0, 6),
            'is_closed' => false,
            'opens_at' => '08:00:00',
            'closes_at' => '23:00:00',
        ];
    }

    public function closed(): static
    {
        return $this->state(fn () => [
            'is_closed' => true,
            'opens_at' => null,
            'closes_at' => null,
        ]);
    }
}
