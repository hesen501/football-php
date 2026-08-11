<?php

use App\Modules\Booking\Models\Booking;
use App\Modules\User\Models\User;
use App\Modules\Venue\Models\Venue;

it('confirms a pending booking', function () {
    $admin = User::factory()->superAdmin()->create();
    $booking = Booking::factory()->pending()->create();

    $this->actingAs($admin, 'sanctum')
        ->postJson("/api/admin/bookings/{$booking->id}/confirm")
        ->assertOk()
        ->assertJsonPath('data.status', 'CONFIRMED');
});

it('rejects confirming a booking that is not pending', function () {
    $admin = User::factory()->superAdmin()->create();
    $booking = Booking::factory()->confirmed()->create();

    $this->actingAs($admin, 'sanctum')
        ->postJson("/api/admin/bookings/{$booking->id}/confirm")
        ->assertStatus(409)
        ->assertJsonPath('error_code', 'BOOKING_NOT_PENDING');
});

it('lets a manager confirm a booking on their own venue', function () {
    $manager = User::factory()->venueManager()->create();
    $venue = Venue::factory()->create();
    $venue->managers()->attach($manager);
    $booking = Booking::factory()->pending()->create(['venue_id' => $venue->id]);

    $this->actingAs($manager, 'sanctum')
        ->postJson("/api/admin/bookings/{$booking->id}/confirm")
        ->assertOk();
});

it('forbids a manager from confirming a booking on another venue', function () {
    $manager = User::factory()->venueManager()->create();
    $booking = Booking::factory()->pending()->create();

    $this->actingAs($manager, 'sanctum')
        ->postJson("/api/admin/bookings/{$booking->id}/confirm")
        ->assertStatus(403);
});
