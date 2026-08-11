<?php

use App\Modules\User\Models\User;
use App\Modules\Venue\Models\Venue;

it('lets a super admin update any venue', function () {
    $admin = User::factory()->superAdmin()->create();
    $venue = Venue::factory()->create(['city' => 'Baku']);

    $this->actingAs($admin, 'sanctum')
        ->putJson("/api/admin/venues/{$venue->id}", ['city' => 'Ganja'])
        ->assertOk()
        ->assertJsonPath('data.city', 'Ganja');
});

it('lets a manager update their own venue', function () {
    $manager = User::factory()->venueManager()->create();
    $venue = Venue::factory()->create();
    $venue->managers()->attach($manager);

    $this->actingAs($manager, 'sanctum')
        ->putJson("/api/admin/venues/{$venue->id}", ['status' => 'INACTIVE'])
        ->assertOk()
        ->assertJsonPath('data.status', 'INACTIVE');
});

it('forbids a manager from updating another manager\'s venue', function () {
    $manager = User::factory()->venueManager()->create();
    $otherVenue = Venue::factory()->create();
    $otherVenue->managers()->attach(User::factory()->venueManager()->create());

    $this->actingAs($manager, 'sanctum')
        ->putJson("/api/admin/venues/{$otherVenue->id}", ['city' => 'Hijacked'])
        ->assertStatus(403);
});

it('does not regenerate the slug when the name changes', function () {
    $admin = User::factory()->superAdmin()->create();
    $venue = Venue::factory()->create(['name' => 'Old Name', 'slug' => 'old-name']);

    $this->actingAs($admin, 'sanctum')
        ->putJson("/api/admin/venues/{$venue->id}", ['name' => 'New Name'])
        ->assertOk()
        ->assertJsonPath('data.slug', 'old-name');
});
