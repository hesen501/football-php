<?php

use App\Modules\User\Models\User;
use App\Modules\Venue\Models\Venue;

it('lets a super admin create a field under any venue', function () {
    $admin = User::factory()->superAdmin()->create();
    $venue = Venue::factory()->create();

    $response = $this->actingAs($admin, 'sanctum')->postJson("/api/admin/venues/{$venue->id}/fields", [
        'name' => 'Field A',
        'type' => 'OUTDOOR',
        'capacity' => 14,
        'hourly_price' => 25,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Field A')
        ->assertJsonPath('data.venue_id', $venue->id)
        ->assertJsonPath('data.hourly_price', '25.00');

    $this->assertDatabaseHas('fields', ['name' => 'Field A', 'venue_id' => $venue->id]);
});

it('lets a manager create a field under their own venue', function () {
    $manager = User::factory()->venueManager()->create();
    $venue = Venue::factory()->create();
    $venue->managers()->attach($manager);

    $this->actingAs($manager, 'sanctum')
        ->postJson("/api/admin/venues/{$venue->id}/fields", [
            'name' => 'Field B',
            'type' => 'INDOOR',
            'capacity' => 10,
            'hourly_price' => 20,
        ])
        ->assertCreated();
});

it('forbids a manager from creating a field under another manager\'s venue', function () {
    $manager = User::factory()->venueManager()->create();
    $otherVenue = Venue::factory()->create();
    $otherVenue->managers()->attach(User::factory()->venueManager()->create());

    $this->actingAs($manager, 'sanctum')
        ->postJson("/api/admin/venues/{$otherVenue->id}/fields", [
            'name' => 'Field C',
            'type' => 'INDOOR',
            'capacity' => 10,
            'hourly_price' => 20,
        ])
        ->assertStatus(403);
});

it('validates required fields when creating a field', function () {
    $admin = User::factory()->superAdmin()->create();
    $venue = Venue::factory()->create();

    $this->actingAs($admin, 'sanctum')
        ->postJson("/api/admin/venues/{$venue->id}/fields", [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'type', 'capacity', 'hourly_price']);
});
