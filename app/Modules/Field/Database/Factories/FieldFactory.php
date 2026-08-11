<?php

namespace App\Modules\Field\Database\Factories;

use App\Modules\Field\Enums\FieldStatus;
use App\Modules\Field\Enums\FieldType;
use App\Modules\Field\Models\Field;
use App\Modules\Venue\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Field>
 */
class FieldFactory extends Factory
{
    protected $model = Field::class;

    public function definition(): array
    {
        return [
            'venue_id' => Venue::factory(),
            'name' => 'Field '.fake()->unique()->numberBetween(1, 999),
            'description' => fake()->sentence(),
            'type' => fake()->randomElement(FieldType::cases()),
            'capacity' => fake()->randomElement([10, 14, 22]),
            'hourly_price' => fake()->randomElement([15, 20, 25, 30, 40]),
            'status' => FieldStatus::ACTIVE,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['status' => FieldStatus::INACTIVE]);
    }

    public function underMaintenance(): static
    {
        return $this->state(fn (array $attributes) => ['status' => FieldStatus::MAINTENANCE]);
    }
}
