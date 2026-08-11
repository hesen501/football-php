<?php

use App\Modules\User\Models\User;
use App\Modules\Venue\Models\Venue;

it('lets a super admin create a venue', function () {
    $admin = User::factory()->superAdmin()->create();

    $response = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/venues', [
        'name' => 'Baku National Arena',
        'address' => '1 Stadium Rd',
        'city' => 'Baku',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Baku National Arena')
        ->assertJsonPath('data.slug', 'baku-national-arena')
        ->assertJsonPath('data.managers', []);

    $this->assertDatabaseHas('venues', ['name' => 'Baku National Arena']);
});

it('lets a super admin create a venue and assign a manager', function () {
    $admin = User::factory()->superAdmin()->create();
    $manager = User::factory()->customer()->create(); // not yet a manager

    $response = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/venues', [
        'name' => 'Ganja Sports Hall',
        'address' => '5 Main St',
        'city' => 'Ganja',
        'manager_id' => $manager->id,
    ]);

    $response->assertCreated()->assertJsonCount(1, 'data.managers');

    expect($manager->fresh()->hasRole('VENUE_MANAGER'))->toBeTrue();
});

it('lets a venue manager self-service create a venue and becomes its manager', function () {
    $manager = User::factory()->venueManager()->create();

    $response = $this->actingAs($manager, 'sanctum')->postJson('/api/admin/venues', [
        'name' => 'Sumqayit Field',
        'address' => '10 Industrial Ave',
        'city' => 'Sumqayit',
    ]);

    $response->assertCreated();

    $venue = Venue::query()->where('name', 'Sumqayit Field')->firstOrFail();
    expect($venue->isManagedBy($manager))->toBeTrue();
});

it('generates a unique slug when names collide', function () {
    $admin = User::factory()->superAdmin()->create();
    Venue::factory()->create(['name' => 'City Arena', 'slug' => 'city-arena']);

    $response = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/venues', [
        'name' => 'City Arena',
        'address' => '2 Second St',
        'city' => 'Baku',
    ]);

    $response->assertCreated();
    expect($response->json('data.slug'))->not->toBe('city-arena');
});

it('validates required fields when creating a venue', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/admin/venues', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'address', 'city']);
});

it('forbids a customer from creating a venue', function () {
    $customer = User::factory()->customer()->create();

    $this->actingAs($customer, 'sanctum')
        ->postJson('/api/admin/venues', [
            'name' => 'Somewhere',
            'address' => '1 St',
            'city' => 'Baku',
        ])
        ->assertStatus(403);
});
