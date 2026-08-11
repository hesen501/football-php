<?php

use App\Modules\Booking\Models\Booking;
use App\Modules\User\Models\User;

it('lets a customer cancel their own future booking', function () {
    $customer = User::factory()->customer()->create();
    $booking = Booking::factory()->confirmed()->create(['user_id' => $customer->id]);

    $this->actingAs($customer, 'sanctum')
        ->postJson("/api/bookings/{$booking->id}/cancel", ['reason' => 'Change of plans'])
        ->assertOk()
        ->assertJsonPath('data.status', 'CANCELLED');
});

it('forbids cancelling another customer\'s booking', function () {
    $customer = User::factory()->customer()->create();
    $booking = Booking::factory()->confirmed()->create(); // different owner

    $this->actingAs($customer, 'sanctum')
        ->postJson("/api/bookings/{$booking->id}/cancel")
        ->assertStatus(403);
});

it('rejects cancelling a booking that already started', function () {
    $customer = User::factory()->customer()->create();
    $booking = Booking::factory()->confirmed()->create([
        'user_id' => $customer->id,
        'start_time' => now()->subHours(2)->startOfHour(),
        'end_time' => now()->subHour()->startOfHour(),
    ]);

    $this->actingAs($customer, 'sanctum')
        ->postJson("/api/bookings/{$booking->id}/cancel")
        ->assertStatus(409)
        ->assertJsonPath('error_code', 'BOOKING_NOT_CANCELLABLE');
});
