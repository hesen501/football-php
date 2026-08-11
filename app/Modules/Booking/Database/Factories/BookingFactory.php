<?php

namespace App\Modules\Booking\Database\Factories;

use App\Modules\Booking\Enums\BookingSource;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Enums\PaymentStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Field\Models\Field;
use App\Modules\User\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        $start = CarbonImmutable::now()->addDay()->startOfHour();
        $hourlyPrice = 20.00;

        return [
            'user_id' => User::factory()->customer(),
            'field_id' => Field::factory(),
            // Derived from field_id, which Laravel has already resolved to a
            // real persisted Field's id by the time this closure runs.
            'venue_id' => fn (array $attributes) => Field::query()->find($attributes['field_id'])->venue_id,
            'start_time' => $start,
            'end_time' => $start->addHour(),
            'duration_minutes' => 60,
            'hourly_price' => $hourlyPrice,
            'total_price' => $hourlyPrice,
            'commission_rate' => 10.00,
            'commission_amount' => round($hourlyPrice * 0.10, 2),
            'venue_amount' => round($hourlyPrice * 0.90, 2),
            'source' => BookingSource::CUSTOMER_APP,
            'status' => BookingStatus::CONFIRMED,
            'payment_status' => PaymentStatus::PENDING,
        ];
    }

    /**
     * Pins a booking to a specific field and hourly slot with financials
     * derived from that field's actual price — the go-to state for
     * availability/overlap/concurrency tests.
     */
    public function forFieldAndTime(Field $field, CarbonImmutable $start, int $hours = 1): static
    {
        $totalPrice = round((float) $field->hourly_price * $hours, 2);

        return $this->state(fn (array $attributes) => [
            'field_id' => $field->id,
            'venue_id' => $field->venue_id,
            'start_time' => $start,
            'end_time' => $start->addHours($hours),
            'duration_minutes' => $hours * 60,
            'hourly_price' => $field->hourly_price,
            'total_price' => $totalPrice,
            'commission_amount' => round($totalPrice * 0.10, 2),
            'venue_amount' => round($totalPrice * 0.90, 2),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => BookingStatus::PENDING]);
    }

    public function confirmed(): static
    {
        return $this->state(fn () => ['status' => BookingStatus::CONFIRMED]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => BookingStatus::CANCELLED,
            'cancelled_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => BookingStatus::COMPLETED]);
    }

    public function adminPanel(): static
    {
        return $this->state(function (array $attributes) {
            $totalPrice = $attributes['total_price'] ?? 0;

            return [
                'source' => BookingSource::ADMIN_PANEL,
                'commission_rate' => 0,
                'commission_amount' => 0,
                'venue_amount' => $totalPrice,
            ];
        });
    }
}
