<?php

namespace App\Modules\Field\Database\Seeders;

use App\Modules\Field\Enums\FieldStatus;
use App\Modules\Field\Enums\FieldType;
use App\Modules\Field\Models\Field;
use App\Modules\Venue\Models\Venue;
use Illuminate\Database\Seeder;

/**
 * Dev/demo data — see DatabaseSeeder for why this only runs outside
 * `testing`. 2-4 fields per venue seeded by VenueSeeder, varying by size
 * (5-a-side/7-a-side/11-a-side, tracked via `capacity` — there's no
 * FieldType::FIVE_A_SIDE etc. in the schema, only INDOOR/OUTDOOR, so the
 * side count lives in the name and capacity instead), type and price.
 *
 * Keyed by (venue_id, name) via updateOrCreate(), so reruns update rather
 * than duplicate.
 */
class FieldSeeder extends Seeder
{
    /**
     * venue slug => list of fields.
     *
     * @var array<string, array<int, array<string, mixed>>>
     */
    private const FIELDS = [
        'arena-football-center' => [
            ['name' => 'Pitch 1 — 5-a-side', 'type' => FieldType::INDOOR, 'capacity' => 10, 'hourly_price' => 25.00],
            ['name' => 'Pitch 2 — 7-a-side', 'type' => FieldType::OUTDOOR, 'capacity' => 14, 'hourly_price' => 35.00],
            ['name' => 'Pitch 3 — 11-a-side', 'type' => FieldType::OUTDOOR, 'capacity' => 22, 'hourly_price' => 55.00],
        ],
        'baki-football-park' => [
            ['name' => 'Pitch 1 — 5-a-side', 'type' => FieldType::INDOOR, 'capacity' => 10, 'hourly_price' => 20.00],
            ['name' => 'Pitch 2 — 5-a-side', 'type' => FieldType::OUTDOOR, 'capacity' => 10, 'hourly_price' => 18.00, 'status' => FieldStatus::MAINTENANCE],
            ['name' => 'Pitch 3 — 7-a-side', 'type' => FieldType::OUTDOOR, 'capacity' => 14, 'hourly_price' => 30.00],
            ['name' => 'Pitch 4 — 11-a-side', 'type' => FieldType::OUTDOOR, 'capacity' => 22, 'hourly_price' => 50.00],
        ],
        'olimpik-arena' => [
            ['name' => 'Hall A — 5-a-side', 'type' => FieldType::INDOOR, 'capacity' => 10, 'hourly_price' => 24.00],
            ['name' => 'Hall B — 7-a-side', 'type' => FieldType::INDOOR, 'capacity' => 14, 'hourly_price' => 40.00],
            ['name' => 'Main Pitch — 11-a-side', 'type' => FieldType::OUTDOOR, 'capacity' => 22, 'hourly_price' => 60.00],
        ],
        'neftci-sport-complex' => [
            ['name' => 'Pitch 1 — 5-a-side', 'type' => FieldType::OUTDOOR, 'capacity' => 10, 'hourly_price' => 20.00],
            ['name' => 'Pitch 2 — 7-a-side', 'type' => FieldType::OUTDOOR, 'capacity' => 14, 'hourly_price' => 32.00],
            ['name' => 'Pitch 3 — 11-a-side', 'type' => FieldType::OUTDOOR, 'capacity' => 22, 'hourly_price' => 55.00, 'status' => FieldStatus::INACTIVE],
        ],
        'genclik-football-center' => [
            ['name' => 'Hall 1 — 5-a-side', 'type' => FieldType::INDOOR, 'capacity' => 10, 'hourly_price' => 24.00],
            ['name' => 'Hall 2 — 7-a-side', 'type' => FieldType::INDOOR, 'capacity' => 14, 'hourly_price' => 38.00],
        ],
        'xezer-arena' => [
            ['name' => 'Pitch 1 — 5-a-side', 'type' => FieldType::OUTDOOR, 'capacity' => 10, 'hourly_price' => 18.00],
            ['name' => 'Pitch 2 — 7-a-side', 'type' => FieldType::OUTDOOR, 'capacity' => 14, 'hourly_price' => 28.00],
            ['name' => 'Pitch 3 — 11-a-side', 'type' => FieldType::OUTDOOR, 'capacity' => 22, 'hourly_price' => 48.00],
        ],
        'nizami-sport-club' => [
            ['name' => 'Pitch 1 — 5-a-side', 'type' => FieldType::OUTDOOR, 'capacity' => 10, 'hourly_price' => 15.00],
            ['name' => 'Pitch 2 — 7-a-side', 'type' => FieldType::OUTDOOR, 'capacity' => 14, 'hourly_price' => 25.00],
            ['name' => 'Pitch 3 — 11-a-side', 'type' => FieldType::OUTDOOR, 'capacity' => 22, 'hourly_price' => 40.00],
        ],
        'sumqayit-arena' => [
            ['name' => 'Hall 1 — 5-a-side', 'type' => FieldType::INDOOR, 'capacity' => 10, 'hourly_price' => 20.00],
            ['name' => 'Pitch 2 — 7-a-side', 'type' => FieldType::OUTDOOR, 'capacity' => 14, 'hourly_price' => 30.00],
            ['name' => 'Pitch 3 — 11-a-side', 'type' => FieldType::OUTDOOR, 'capacity' => 22, 'hourly_price' => 45.00],
        ],
        'mingacevir-football-park' => [
            ['name' => 'Pitch 1 — 5-a-side', 'type' => FieldType::OUTDOOR, 'capacity' => 10, 'hourly_price' => 16.00],
            ['name' => 'Pitch 2 — 7-a-side', 'type' => FieldType::OUTDOOR, 'capacity' => 14, 'hourly_price' => 26.00],
        ],
        'seki-yasil-meydan' => [
            ['name' => 'Pitch 1 — 5-a-side', 'type' => FieldType::OUTDOOR, 'capacity' => 10, 'hourly_price' => 15.00],
            ['name' => 'Pitch 2 — 7-a-side', 'type' => FieldType::OUTDOOR, 'capacity' => 14, 'hourly_price' => 22.00],
        ],
    ];

    public function run(): void
    {
        $venuesBySlug = Venue::query()->pluck('id', 'slug');

        foreach (self::FIELDS as $slug => $fields) {
            $venueId = $venuesBySlug[$slug] ?? null;

            if (! $venueId) {
                continue;
            }

            foreach ($fields as $field) {
                Field::query()->updateOrCreate(
                    ['venue_id' => $venueId, 'name' => $field['name']],
                    [
                        'description' => $field['description'] ?? sprintf(
                            '%s, %s field.',
                            $field['type']->value === 'INDOOR' ? 'Indoor' : 'Outdoor',
                            str_contains($field['name'], '5-a-side') ? '5-a-side' : (str_contains($field['name'], '7-a-side') ? '7-a-side' : '11-a-side'),
                        ),
                        'type' => $field['type'],
                        'capacity' => $field['capacity'],
                        'hourly_price' => $field['hourly_price'],
                        'status' => $field['status'] ?? FieldStatus::ACTIVE,
                    ],
                );
            }
        }
    }
}
