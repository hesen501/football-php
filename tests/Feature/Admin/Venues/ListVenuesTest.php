<?php

use App\Modules\User\Models\User;
use App\Modules\Venue\Models\Venue;

it('lets a super admin list all venues', function () {
    $admin = User::factory()->superAdmin()->create();
    Venue::factory()->count(3)->create();

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/admin/venues')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('scopes a venue manager\'s list to only their own venues', function () {
    $manager = User::factory()->venueManager()->create();
    $ownVenue = Venue::factory()->create();
    $ownVenue->managers()->attach($manager);

    Venue::factory()->count(2)->create(); // other managers' venues

    $this->actingAs($manager, 'sanctum')
        ->getJson('/api/admin/venues')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $ownVenue->id);
});

it('filters venues by status', function () {
    $admin = User::factory()->superAdmin()->create();
    Venue::factory()->count(2)->create();
    Venue::factory()->inactive()->create();

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/admin/venues?status=INACTIVE')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('filters venues by manager_id', function () {
    $admin = User::factory()->superAdmin()->create();
    $manager = User::factory()->venueManager()->create();
    $venue = Venue::factory()->create();
    $venue->managers()->attach($manager);
    Venue::factory()->create();

    $this->actingAs($admin, 'sanctum')
        ->getJson("/api/admin/venues?manager_id={$manager->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $venue->id);
});

it('searches venues by name or city', function () {
    $admin = User::factory()->superAdmin()->create();
    Venue::factory()->create(['name' => 'Baku National Arena', 'city' => 'Baku']);
    Venue::factory()->create(['name' => 'Ganja Sports Hall', 'city' => 'Ganja']);

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/admin/venues?search=Ganja')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('forbids a customer from listing venues', function () {
    $customer = User::factory()->customer()->create();

    $this->actingAs($customer, 'sanctum')
        ->getJson('/api/admin/venues')
        ->assertStatus(403);
});
