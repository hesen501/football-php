<?php

use App\Modules\Booking\Models\Booking;
use App\Modules\User\Models\User;

it('lists only the authenticated customer\'s own bookings', function () {
    $customer = User::factory()->customer()->create();
    Booking::factory()->count(2)->create(['user_id' => $customer->id]);
    Booking::factory()->count(3)->create(); // other customers' bookings

    $this->actingAs($customer, 'sanctum')
        ->getJson('/api/bookings')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('filters own bookings by status', function () {
    $customer = User::factory()->customer()->create();
    Booking::factory()->confirmed()->create(['user_id' => $customer->id]);
    Booking::factory()->pending()->create(['user_id' => $customer->id]);

    $this->actingAs($customer, 'sanctum')
        ->getJson('/api/bookings?status=PENDING')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('shows a single own booking', function () {
    $customer = User::factory()->customer()->create();
    $booking = Booking::factory()->create(['user_id' => $customer->id]);

    $this->actingAs($customer, 'sanctum')
        ->getJson("/api/bookings/{$booking->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $booking->id);
});

it('forbids viewing another customer\'s booking', function () {
    $customer = User::factory()->customer()->create();
    $booking = Booking::factory()->create(); // belongs to a different, auto-created user

    $this->actingAs($customer, 'sanctum')
        ->getJson("/api/bookings/{$booking->id}")
        ->assertStatus(403);
});

it('requires authentication to list bookings', function () {
    $this->getJson('/api/bookings')->assertStatus(401);
});
