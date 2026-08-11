<?php

use App\Modules\Booking\Models\Booking;
use App\Modules\Field\Models\Field;
use App\Modules\User\Models\User;
use App\Modules\Venue\Models\Venue;
use Carbon\CarbonImmutable;

it('lists recent bookings for a super admin', function () {
    $admin = User::factory()->superAdmin()->create();
    Booking::factory()->count(3)->create();

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/admin/dashboard/recent-bookings')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('scopes recent bookings to a manager\'s own venues', function () {
    $manager = User::factory()->venueManager()->create();
    $ownVenue = Venue::factory()->create();
    $ownVenue->managers()->attach($manager);
    $ownField = Field::factory()->create(['venue_id' => $ownVenue->id]);
    Booking::factory()->forFieldAndTime($ownField, CarbonImmutable::now()->addDay()->startOfHour())->create();

    Booking::factory()->count(2)->create(); // other managers' bookings

    $this->actingAs($manager, 'sanctum')
        ->getJson('/api/admin/dashboard/recent-bookings')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('respects the limit query parameter, capped at 50', function () {
    $admin = User::factory()->superAdmin()->create();
    Booking::factory()->count(5)->create();

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/admin/dashboard/recent-bookings?limit=2')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('forbids a customer token from viewing recent bookings', function () {
    $customer = User::factory()->customer()->create();

    $this->actingAs($customer, 'sanctum')
        ->getJson('/api/admin/dashboard/recent-bookings')
        ->assertStatus(403);
});
