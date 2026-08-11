<?php

use App\Modules\Booking\Models\Booking;
use App\Modules\Field\Models\Field;
use App\Modules\User\Models\User;
use App\Modules\Venue\Models\Venue;
use Carbon\CarbonImmutable;

it('returns platform-wide totals for a super admin', function () {
    $admin = User::factory()->superAdmin()->create();
    User::factory()->count(2)->customer()->create();
    $venue = Venue::factory()->create();
    $field = Field::factory()->create(['venue_id' => $venue->id, 'hourly_price' => 20]);
    Booking::factory()->forFieldAndTime($field, CarbonImmutable::now()->addDay()->startOfHour())->confirmed()->create();
    Booking::factory()->forFieldAndTime($field, CarbonImmutable::now()->addDays(2)->startOfHour())->pending()->create();

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson('/api/admin/dashboard/stats')
        ->assertOk();

    $response->assertJsonPath('data.total_venues', 1)
        ->assertJsonPath('data.total_fields', 1)
        ->assertJsonPath('data.total_bookings', 2)
        ->assertJsonPath('data.booking_status_breakdown.CONFIRMED', 1)
        ->assertJsonPath('data.booking_status_breakdown.PENDING', 1);

    // total_users includes the admin + 2 customers created here (any seeded
    // admin from the migrate:fresh isn't relevant to a fresh test DB).
    expect($response->json('data.total_users'))->toBeGreaterThanOrEqual(3);
});

it('only counts confirmed/completed bookings toward revenue', function () {
    $admin = User::factory()->superAdmin()->create();
    $field = Field::factory()->create(['hourly_price' => 20]);

    Booking::factory()->forFieldAndTime($field, CarbonImmutable::now()->addDay()->startOfHour())->confirmed()->create();
    Booking::factory()->forFieldAndTime($field, CarbonImmutable::now()->addDays(2)->startOfHour())->pending()->create();
    Booking::factory()->forFieldAndTime($field, CarbonImmutable::now()->addDays(3)->startOfHour())->cancelled()->create();

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson('/api/admin/dashboard/stats')
        ->assertOk();

    // Only the CONFIRMED booking (20 AZN, 10% commission) counts. Whole-number
    // floats round-trip through JSON as plain integers (no PRESERVE_ZERO_FRACTION),
    // so these compare against ints, not floats.
    $response->assertJsonPath('data.total_revenue', 20)
        ->assertJsonPath('data.total_commission', 2)
        ->assertJsonPath('data.total_venue_amount', 18);
});

it('scopes a venue manager\'s dashboard to their own venues and omits total_users', function () {
    $manager = User::factory()->venueManager()->create();
    $ownVenue = Venue::factory()->create();
    $ownVenue->managers()->attach($manager);
    $ownField = Field::factory()->create(['venue_id' => $ownVenue->id]);
    Booking::factory()->forFieldAndTime($ownField, CarbonImmutable::now()->addDay()->startOfHour())->confirmed()->create();

    // Another manager's venue/field/booking — must not be counted.
    $otherField = Field::factory()->create();
    Booking::factory()->forFieldAndTime($otherField, CarbonImmutable::now()->addDay()->startOfHour())->confirmed()->create();

    $response = $this->actingAs($manager, 'sanctum')
        ->getJson('/api/admin/dashboard/stats')
        ->assertOk();

    $response->assertJsonPath('data.total_venues', 1)
        ->assertJsonPath('data.total_fields', 1)
        ->assertJsonPath('data.total_bookings', 1)
        ->assertJsonPath('data.total_users', null);
});

it('forbids a customer token from viewing the dashboard', function () {
    $customer = User::factory()->customer()->create();

    $this->actingAs($customer, 'sanctum')
        ->getJson('/api/admin/dashboard/stats')
        ->assertStatus(403);
});
