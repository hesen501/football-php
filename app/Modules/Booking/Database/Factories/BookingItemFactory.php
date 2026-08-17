<?php

namespace App\Modules\Booking\Database\Factories;

use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\BookingItem;
use App\Modules\Item\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingItem>
 */
class BookingItemFactory extends Factory
{
    protected $model = BookingItem::class;

    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 3);
        $unitPrice = fake()->randomElement([1, 2, 3, 5]);

        return [
            'booking_id' => Booking::factory(),
            'item_id' => Item::factory(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $quantity * $unitPrice,
        ];
    }

    /**
     * Pins to a specific booking/item pair with financials derived from
     * that item's actual current price — the go-to state for booking-items
     * tests, mirroring BookingFactory::forFieldAndTime().
     */
    public function forBookingAndItem(Booking $booking, Item $item, int $quantity = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'booking_id' => $booking->id,
            'item_id' => $item->id,
            'quantity' => $quantity,
            'unit_price' => $item->price,
            'total_price' => $quantity * (float) $item->price,
        ]);
    }
}
