<?php

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Field\Models\Field;
use App\Modules\User\Models\User;
use App\Modules\Venue\Models\Venue;
use Carbon\CarbonImmutable;

it('cancels a future confirmed booking', function () {
    $admin = User::factory()->superAdmin()->create();
    $booking = Booking::factory()->confirmed()->create();

    $this->actingAs($admin, 'sanctum')
        ->postJson("/api/admin/bookings/{$booking->id}/cancel", ['reason' => 'Weather'])
        ->assertOk()
        ->assertJsonPath('data.status', 'CANCELLED')
        ->assertJsonPath('data.cancellation_reason', 'Weather');

    $booking->refresh();
    expect($booking->status)->toBe(BookingStatus::CANCELLED)
        ->and($booking->cancelled_by_user_id)->toBe($admin->id)
        ->and($booking->cancelled_at)->not->toBeNull();
});

it('rejects cancelling a booking that already started in the past', function () {
    $admin = User::factory()->superAdmin()->create();
    $booking = Booking::factory()->confirmed()->create([
        'start_time' => now()->subHours(3)->startOfHour(),
        'end_time' => now()->subHours(2)->startOfHour(),
    ]);

    $this->actingAs($admin, 'sanctum')
        ->postJson("/api/admin/bookings/{$booking->id}/cancel")
        ->assertStatus(409)
        ->assertJsonPath('error_code', 'BOOKING_NOT_CANCELLABLE');
});

it('rejects cancelling an already-cancelled booking', function () {
    $admin = User::factory()->superAdmin()->create();
    $booking = Booking::factory()->cancelled()->create();

    $this->actingAs($admin, 'sanctum')
        ->postJson("/api/admin/bookings/{$booking->id}/cancel")
        ->assertStatus(409)
        ->assertJsonPath('error_code', 'BOOKING_NOT_CANCELLABLE');
});

it('frees the slot for a new booking once cancelled', function () {
    $admin = User::factory()->superAdmin()->create();
    $customer = User::factory()->customer()->create();
    $field = Field::factory()->create(['hourly_price' => 20]);
    $start = CarbonImmutable::now()->addDay()->startOfHour();
    $booking = Booking::factory()->forFieldAndTime($field, $start)->confirmed()->create();

    $this->actingAs($admin, 'sanctum')
        ->postJson("/api/admin/bookings/{$booking->id}/cancel")
        ->assertOk();

    $this->actingAs($admin, 'sanctum')->postJson('/api/admin/bookings', [
        'user_id' => $customer->id,
        'field_id' => $field->id,
        'start_time' => $start->toIso8601String(),
        'duration_hours' => 1,
    ])->assertCreated();
});

it('forbids a manager from cancelling a booking on another venue', function () {
    $manager = User::factory()->venueManager()->create();
    $booking = Booking::factory()->confirmed()->create();

    $this->actingAs($manager, 'sanctum')
        ->postJson("/api/admin/bookings/{$booking->id}/cancel")
        ->assertStatus(403);
});

it('lets a manager cancel a booking on their own venue', function () {
    $manager = User::factory()->venueManager()->create();
    $venue = Venue::factory()->create();
    $venue->managers()->attach($manager);
    $booking = Booking::factory()->confirmed()->create(['venue_id' => $venue->id]);

    $this->actingAs($manager, 'sanctum')
        ->postJson("/api/admin/bookings/{$booking->id}/cancel")
        ->assertOk();
});
