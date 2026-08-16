<?php

namespace App\Modules\Item\Database\Seeders;

use App\Modules\Item\Enums\ItemStatus;
use App\Modules\Item\Models\Item;
use Illuminate\Database\Seeder;

/**
 * Dev/demo data — see DatabaseSeeder for why this only runs outside
 * `testing`. Items are a single global catalog (no venue_id — see the
 * Item model docblock), so this is a flat list rather than per-venue data.
 *
 * Keyed by name via updateOrCreate(), so reruns update rather than
 * duplicate.
 */
class ItemSeeder extends Seeder
{
    /** @var array<int, array{name: string, price: float, status?: ItemStatus}> */
    private const ITEMS = [
        ['name' => 'Water Bottle', 'price' => 1.00],
        ['name' => 'Bibs Set', 'price' => 2.00],
        ['name' => 'Ball Rental', 'price' => 5.00],
        ['name' => 'Extra 30 Minutes', 'price' => 15.00],
        ['name' => 'Referee', 'price' => 20.00],
        ['name' => 'Locker', 'price' => 3.00],
        ['name' => 'Shower Kit', 'price' => 2.50],
        ['name' => 'Training Equipment Set', 'price' => 10.00],
        ['name' => 'Goalkeeper Gloves', 'price' => 8.00],
        ['name' => 'Jersey Rental', 'price' => 6.00],
        ['name' => 'Shin Guards', 'price' => 4.00],
        ['name' => 'Towel', 'price' => 1.50, 'status' => ItemStatus::INACTIVE],
    ];

    public function run(): void
    {
        foreach (self::ITEMS as $item) {
            Item::query()->updateOrCreate(
                ['name' => $item['name']],
                [
                    'price' => $item['price'],
                    'status' => $item['status'] ?? ItemStatus::ACTIVE,
                ],
            );
        }
    }
}
