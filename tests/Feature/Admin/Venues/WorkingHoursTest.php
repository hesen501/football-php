<?php

use App\Modules\User\Models\User;
use App\Modules\Venue\Models\Venue;
use App\Modules\Venue\Services\VenueService;

it('seeds a default 08:00-23:00 window for every day when a venue is created', function () {
    $admin = User::factory()->superAdmin()->create();

    $response = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/venues', [
        'name' => 'Baku National Arena',
        'address' => '1 Stadium Rd',
        'city' => 'Baku',
    ]);

    $response->assertCreated()->assertJsonCount(7, 'data.working_hours');

    $venue = Venue::query()->where('name', 'Baku National Arena')->firstOrFail();

    expect($venue->workingHours()->pluck('day_of_week')->sort()->values()->all())->toBe(range(0, 6));
    expect($venue->workingHours()->first()->opens_at)->toBe('08:00:00');
    expect($venue->workingHours()->first()->closes_at)->toBe('23:00:00');
});

it('lets a manager view their venue\'s working hours', function () {
    $manager = User::factory()->venueManager()->create();
    $venue = app(VenueService::class)->create($manager, [
        'name' => 'Sumqayit Field',
        'address' => '10 Industrial Ave',
        'city' => 'Sumqayit',
    ]);

    $response = $this->actingAs($manager, 'sanctum')
        ->getJson("/api/admin/venues/{$venue->id}/working-hours")
        ->assertOk();

    expect($response->json('data'))->toHaveCount(7);
    expect(collect($response->json('data'))->firstWhere('day_of_week', 1))
        ->toMatchArray(['day_name' => 'Monday', 'is_closed' => false, 'opens_at' => '08:00', 'closes_at' => '23:00']);
});

it('lets a manager set custom hours per day, including closing a day entirely', function () {
    $manager = User::factory()->venueManager()->create();
    $venue = app(VenueService::class)->create($manager, [
        'name' => 'Ganja Sports Hall',
        'address' => '5 Main St',
        'city' => 'Ganja',
    ]);

    $days = collect(range(0, 6))->map(fn (int $day) => $day === 0
        ? ['day_of_week' => 0, 'is_closed' => true]
        : ['day_of_week' => $day, 'is_closed' => false, 'opens_at' => '09:00', 'closes_at' => '22:00'])->values()->all();

    $response = $this->actingAs($manager, 'sanctum')
        ->putJson("/api/admin/venues/{$venue->id}/working-hours", ['days' => $days])
        ->assertOk();

    $sunday = collect($response->json('data'))->firstWhere('day_of_week', 0);
    expect($sunday)->toMatchArray(['is_closed' => true, 'opens_at' => null, 'closes_at' => null]);

    $monday = collect($response->json('data'))->firstWhere('day_of_week', 1);
    expect($monday)->toMatchArray(['is_closed' => false, 'opens_at' => '09:00', 'closes_at' => '22:00']);

    $this->assertDatabaseHas('venue_working_hours', [
        'venue_id' => $venue->id,
        'day_of_week' => 0,
        'is_closed' => true,
        'opens_at' => null,
    ]);
});

it('rejects a working-hours update missing a day', function () {
    $admin = User::factory()->superAdmin()->create();
    $venue = Venue::factory()->create();

    $days = collect(range(0, 5))->map(fn (int $day) => [
        'day_of_week' => $day, 'is_closed' => false, 'opens_at' => '08:00', 'closes_at' => '23:00',
    ])->values()->all();

    $this->actingAs($admin, 'sanctum')
        ->putJson("/api/admin/venues/{$venue->id}/working-hours", ['days' => $days])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['days']);
});

it('rejects closes_at before opens_at', function () {
    $admin = User::factory()->superAdmin()->create();
    $venue = Venue::factory()->create();

    $days = collect(range(0, 6))->map(fn (int $day) => [
        'day_of_week' => $day, 'is_closed' => false, 'opens_at' => '23:00', 'closes_at' => '08:00',
    ])->values()->all();

    $this->actingAs($admin, 'sanctum')
        ->putJson("/api/admin/venues/{$venue->id}/working-hours", ['days' => $days])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['days.0.closes_at']);
});

it('forbids a manager from editing another manager\'s venue hours', function () {
    $manager = User::factory()->venueManager()->create();
    $otherVenue = Venue::factory()->create();
    $otherVenue->managers()->attach(User::factory()->venueManager()->create());

    $days = collect(range(0, 6))->map(fn (int $day) => [
        'day_of_week' => $day, 'is_closed' => false, 'opens_at' => '08:00', 'closes_at' => '23:00',
    ])->values()->all();

    $this->actingAs($manager, 'sanctum')
        ->putJson("/api/admin/venues/{$otherVenue->id}/working-hours", ['days' => $days])
        ->assertStatus(403);
});
