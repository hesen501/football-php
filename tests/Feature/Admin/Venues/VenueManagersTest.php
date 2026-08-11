<?php

use App\Modules\User\Models\User;
use App\Modules\Venue\Models\Venue;

it('lets a super admin attach a co-manager', function () {
    $admin = User::factory()->superAdmin()->create();
    $venue = Venue::factory()->create();
    $venue->managers()->attach(User::factory()->venueManager()->create());
    $newManager = User::factory()->customer()->create();

    $this->actingAs($admin, 'sanctum')
        ->postJson("/api/admin/venues/{$venue->id}/managers", ['user_id' => $newManager->id])
        ->assertCreated()
        ->assertJsonCount(2, 'data');

    expect($newManager->fresh()->hasRole('VENUE_MANAGER'))->toBeTrue();
});

it('rejects attaching a manager who already manages the venue', function () {
    $admin = User::factory()->superAdmin()->create();
    $manager = User::factory()->venueManager()->create();
    $venue = Venue::factory()->create();
    $venue->managers()->attach($manager);

    $this->actingAs($admin, 'sanctum')
        ->postJson("/api/admin/venues/{$venue->id}/managers", ['user_id' => $manager->id])
        ->assertStatus(409)
        ->assertJsonPath('error_code', 'ALREADY_A_MANAGER');
});

it('lets a super admin detach a co-manager', function () {
    $admin = User::factory()->superAdmin()->create();
    $venue = Venue::factory()->create();
    $manager1 = User::factory()->venueManager()->create();
    $manager2 = User::factory()->venueManager()->create();
    $venue->managers()->attach([$manager1->id, $manager2->id]);

    $this->actingAs($admin, 'sanctum')
        ->deleteJson("/api/admin/venues/{$venue->id}/managers/{$manager1->id}")
        ->assertNoContent();

    expect($venue->managers()->count())->toBe(1);
});

it('prevents detaching the last manager of a venue', function () {
    $admin = User::factory()->superAdmin()->create();
    $venue = Venue::factory()->create();
    $manager = User::factory()->venueManager()->create();
    $venue->managers()->attach($manager);

    $this->actingAs($admin, 'sanctum')
        ->deleteJson("/api/admin/venues/{$venue->id}/managers/{$manager->id}")
        ->assertStatus(409)
        ->assertJsonPath('error_code', 'LAST_MANAGER');
});

it('forbids a venue manager from attaching a co-manager to their own venue', function () {
    $manager = User::factory()->venueManager()->create();
    $venue = Venue::factory()->create();
    $venue->managers()->attach($manager);
    $newManager = User::factory()->customer()->create();

    $this->actingAs($manager, 'sanctum')
        ->postJson("/api/admin/venues/{$venue->id}/managers", ['user_id' => $newManager->id])
        ->assertStatus(403);
});
