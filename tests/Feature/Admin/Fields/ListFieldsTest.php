<?php

use App\Modules\Field\Models\Field;
use App\Modules\User\Models\User;
use App\Modules\Venue\Models\Venue;

it('lets a super admin list all fields', function () {
    $admin = User::factory()->superAdmin()->create();
    Field::factory()->count(3)->create();

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/admin/fields')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('scopes a venue manager\'s field list to their own venues', function () {
    $manager = User::factory()->venueManager()->create();
    $ownVenue = Venue::factory()->create();
    $ownVenue->managers()->attach($manager);
    Field::factory()->count(2)->create(['venue_id' => $ownVenue->id]);

    Field::factory()->count(3)->create(); // other managers' fields

    $this->actingAs($manager, 'sanctum')
        ->getJson('/api/admin/fields')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('filters fields by venue_id', function () {
    $admin = User::factory()->superAdmin()->create();
    $venue = Venue::factory()->create();
    Field::factory()->count(2)->create(['venue_id' => $venue->id]);
    Field::factory()->count(3)->create();

    $this->actingAs($admin, 'sanctum')
        ->getJson("/api/admin/fields?venue_id={$venue->id}")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('filters fields by status', function () {
    $admin = User::factory()->superAdmin()->create();
    Field::factory()->count(2)->create();
    Field::factory()->underMaintenance()->create();

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/admin/fields?status=MAINTENANCE')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('forbids a customer from listing fields', function () {
    $customer = User::factory()->customer()->create();

    $this->actingAs($customer, 'sanctum')
        ->getJson('/api/admin/fields')
        ->assertStatus(403);
});
