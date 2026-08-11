<?php

use App\Modules\Booking\Models\Booking;
use App\Modules\Field\Models\Field;
use App\Modules\User\Models\User;
use Carbon\CarbonImmutable;

it('lets a verified customer create a booking with commission applied', function () {
    // $seed = true on the base TestCase already seeds commission_rate=10.00.
    $customer = User::factory()->customer()->create(); // email_verified_at set by default factory
    $field = Field::factory()->create(['hourly_price' => 20]);
    $start = CarbonImmutable::now()->addDay()->startOfHour();

    $response = $this->actingAs($customer, 'sanctum')->postJson('/api/bookings', [
        'field_id' => $field->id,
        'start_time' => $start->toIso8601String(),
        'duration_hours' => 2,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.status', 'PENDING')
        ->assertJsonPath('data.payment_status', 'PENDING')
        ->assertJsonPath('data.total_price', '40.00');

    // Commission internals are never exposed to the customer.
    $response->assertJsonMissingPath('data.commission_rate')
        ->assertJsonMissingPath('data.commission_amount')
        ->assertJsonMissingPath('data.venue_amount');

    $this->assertDatabaseHas('bookings', [
        'user_id' => $customer->id,
        'field_id' => $field->id,
        'source' => 'CUSTOMER_APP',
        'commission_amount' => 4,
    ]);
});

it('rejects a booking from an unverified customer', function () {
    $customer = User::factory()->customer()->unverified()->create();
    $field = Field::factory()->create();

    $this->actingAs($customer, 'sanctum')->postJson('/api/bookings', [
        'field_id' => $field->id,
        'start_time' => CarbonImmutable::now()->addDay()->startOfHour()->toIso8601String(),
        'duration_hours' => 1,
    ])->assertStatus(409)->assertJsonPath('error_code', 'EMAIL_NOT_VERIFIED');
});

it('rejects an overlapping booking for a customer, same as the admin panel', function () {
    $customer = User::factory()->customer()->create();
    $field = Field::factory()->create(['hourly_price' => 20]);
    $start = CarbonImmutable::now()->addDay()->startOfHour();

    Booking::factory()->forFieldAndTime($field, $start)->confirmed()->create();

    $this->actingAs($customer, 'sanctum')->postJson('/api/bookings', [
        'field_id' => $field->id,
        'start_time' => $start->toIso8601String(),
        'duration_hours' => 1,
    ])->assertStatus(409)->assertJsonPath('error_code', 'BOOKING_SLOT_UNAVAILABLE');
});

it('requires authentication', function () {
    $field = Field::factory()->create();

    $this->postJson('/api/bookings', [
        'field_id' => $field->id,
        'start_time' => CarbonImmutable::now()->addDay()->startOfHour()->toIso8601String(),
        'duration_hours' => 1,
    ])->assertStatus(401);
});

it('validates required fields', function () {
    $customer = User::factory()->customer()->create();

    $this->actingAs($customer, 'sanctum')
        ->postJson('/api/bookings', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['field_id', 'start_time', 'duration_hours']);
});

it('does not accept a user_id override — the booking is always for the authenticated user', function () {
    $customer = User::factory()->customer()->create();
    $otherCustomer = User::factory()->customer()->create();
    $field = Field::factory()->create();

    $response = $this->actingAs($customer, 'sanctum')->postJson('/api/bookings', [
        'user_id' => $otherCustomer->id, // not a validated field — must be ignored
        'field_id' => $field->id,
        'start_time' => CarbonImmutable::now()->addDay()->startOfHour()->toIso8601String(),
        'duration_hours' => 1,
    ]);

    $response->assertCreated();
    $this->assertDatabaseHas('bookings', ['user_id' => $customer->id]);
    $this->assertDatabaseMissing('bookings', ['user_id' => $otherCustomer->id]);
});
