<?php

namespace App\Modules\Item\Database\Factories;

use App\Modules\Item\Enums\ItemStatus;
use App\Modules\Item\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Water Bottle', 'Gloves', 'Boots', 'Socks', 'Jersey', 'Shin Guards', 'Towel']),
            'price' => fake()->randomElement([1, 1.5, 2, 3, 5, 10]),
            'status' => ItemStatus::ACTIVE,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['status' => ItemStatus::INACTIVE]);
    }
}
