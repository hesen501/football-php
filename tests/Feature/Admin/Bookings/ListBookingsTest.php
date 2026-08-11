<?php

use App\Modules\Booking\Models\Booking;
use App\Modules\Field\Models\Field;
use App\Modules\User\Models\User;
use App\Modules\Venue\Models\Venue;
use Carbon\CarbonImmutable;

it('lets a super admin list all bookings', function () {
    $admin = User::factory()->superAdmin()->create();
    Booking::factory()->count(3)->create();

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/admin/bookings')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('scopes a manager\'s booking list to their own venues', function () {
    $manager = User::factory()->venueManager()->create();
    $ownVenue = Venue::factory()->create();
    $ownVenue->managers()->attach($manager);
    $ownField = Field::factory()->create(['venue_id' => $ownVenue->id]);
    $start = CarbonImmutable::now()->addDay()->startOfHour();
    Booking::factory()->forFieldAndTime($ownField, $start)->create();
    Booking::factory()->forFieldAndTime($ownField, $start->addHours(2))->create();

    Booking::factory()->count(3)->create(); // other managers' bookings

    $this->actingAs($manager, 'sanctum')
        ->getJson('/api/admin/bookings')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('filters bookings by status', function () {
    $admin = User::factory()->superAdmin()->create();
    Booking::factory()->confirmed()->count(2)->create();
    Booking::factory()->pending()->create();

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/admin/bookings?status=PENDING')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('filters bookings by venue_id and field_id', function () {
    $admin = User::factory()->superAdmin()->create();
    $venue = Venue::factory()->create();
    $field = Field::factory()->create(['venue_id' => $venue->id]);
    Booking::factory()->create(['venue_id' => $venue->id, 'field_id' => $field->id]);
    Booking::factory()->count(2)->create();

    $this->actingAs($admin, 'sanctum')
        ->getJson("/api/admin/bookings?venue_id={$venue->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->actingAs($admin, 'sanctum')
        ->getJson("/api/admin/bookings?field_id={$field->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('filters bookings by user_id', function () {
    $admin = User::factory()->superAdmin()->create();
    $customer = User::factory()->customer()->create();
    Booking::factory()->create(['user_id' => $customer->id]);
    Booking::factory()->count(2)->create();

    $this->actingAs($admin, 'sanctum')
        ->getJson("/api/admin/bookings?user_id={$customer->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('filters bookings by a start_time date range', function () {
    $admin = User::factory()->superAdmin()->create();
    Booking::factory()->create([
        'start_time' => now()->addDays(2)->startOfHour(),
        'end_time' => now()->addDays(2)->startOfHour()->addHour(),
    ]);
    Booking::factory()->create([
        'start_time' => now()->addDays(10)->startOfHour(),
        'end_time' => now()->addDays(10)->startOfHour()->addHour(),
    ]);

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/admin/bookings?date_from='.now()->toDateString().'&date_to='.now()->addDays(5)->toDateString())
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('forbids a customer token from listing admin bookings', function () {
    $customer = User::factory()->customer()->create();

    $this->actingAs($customer, 'sanctum')
        ->getJson('/api/admin/bookings')
        ->assertStatus(403);
});
