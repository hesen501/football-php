<?php

namespace App\Modules\Booking\Database\Seeders;

use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\BookingItem;
use App\Modules\Item\Enums\ItemStatus;
use App\Modules\Item\Models\Item;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Dev/demo data — see DatabaseSeeder for why this only runs outside
 * `testing`. Attaches 0-3 catalog items (see ItemSeeder) to a subset of the
 * bookings BookingSeeder created, mirroring what BookingService::addItem()
 * would have produced: unit_price is a snapshot of the item's price at
 * "add time" (here, just its current seeded price) and total_price is
 * always quantity * unit_price, satisfying the
 * `booking_items_total_matches_quantity` CHECK constraint (see the
 * booking_items migration). Never touches Booking::total_price itself —
 * that stays a pure field-booking snapshot; see Booking::grandTotal() for
 * where the two are combined for display.
 *
 * Guarded the same way as BookingSeeder: refuses to run a second time once
 * any booking_items row exists, so `php artisan db:seed` reruns can't pile
 * up duplicate/conflicting rows on top of bookings that no longer exist in
 * memory to dedupe against.
 */
class BookingItemSeeder extends Seeder
{
    /** Share of bookings that get any items at all. */
    private const ATTACH_PROBABILITY = 65;

    /** @var array<int, array{min: int, max: int}> item name => quantity range */
    private const QUANTITY_RANGES = [
        'Water Bottle' => ['min' => 2, 'max' => 6],
        'Bibs Set' => ['min' => 1, 'max' => 2],
        'Ball Rental' => ['min' => 1, 'max' => 2],
        'Extra 30 Minutes' => ['min' => 1, 'max' => 1],
        'Referee' => ['min' => 1, 'max' => 1],
        'Locker' => ['min' => 1, 'max' => 4],
        'Shower Kit' => ['min' => 1, 'max' => 4],
        'Training Equipment Set' => ['min' => 1, 'max' => 1],
        'Goalkeeper Gloves' => ['min' => 1, 'max' => 2],
        'Jersey Rental' => ['min' => 1, 'max' => 10],
        'Shin Guards' => ['min' => 1, 'max' => 10],
    ];

    public function run(): void
    {
        if (BookingItem::query()->exists()) {
            $this->command?->info('Booking items already exist — skipping BookingItemSeeder.');

            return;
        }

        $items = Item::query()->where('status', ItemStatus::ACTIVE->value)->get();

        if ($items->isEmpty()) {
            $this->command?->warn('No active items found — run ItemSeeder first. Skipping BookingItemSeeder.');

            return;
        }

        $bookings = Booking::query()->get(['id']);
        $created = 0;

        foreach ($bookings as $booking) {
            if (random_int(1, 100) > self::ATTACH_PROBABILITY) {
                continue;
            }

            $created += $this->attachRandomItems($booking, $items);
        }

        $this->command?->info("BookingItemSeeder: attached items to bookings ({$created} booking_items rows created).");
    }

    private function attachRandomItems(Booking $booking, Collection $items): int
    {
        $howMany = random_int(1, min(3, $items->count()));
        $picked = $items->random($howMany);
        $picked = $picked instanceof Collection ? $picked : collect([$picked]);

        $count = 0;

        foreach ($picked as $item) {
            $range = self::QUANTITY_RANGES[$item->name] ?? ['min' => 1, 'max' => 3];
            $quantity = random_int($range['min'], $range['max']);
            $unitPrice = (float) $item->price;

            BookingItem::query()->create([
                'booking_id' => $booking->id,
                'item_id' => $item->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => round($quantity * $unitPrice, 2),
            ]);

            $count++;
        }

        return $count;
    }
}
