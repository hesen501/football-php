<?php

use App\Modules\User\Models\User;
use App\Modules\Venue\Models\Venue;

it('lets a super admin soft-delete a venue', function () {
    $admin = User::factory()->superAdmin()->create();
    $venue = Venue::factory()->create();

    $this->actingAs($admin, 'sanctum')
        ->deleteJson("/api/admin/venues/{$venue->id}")
        ->assertNoContent();

    $this->assertSoftDeleted('venues', ['id' => $venue->id]);
});

it('forbids a venue manager from deleting even their own venue', function () {
    $manager = User::factory()->venueManager()->create();
    $venue = Venue::factory()->create();
    $venue->managers()->attach($manager);

    $this->actingAs($manager, 'sanctum')
        ->deleteJson("/api/admin/venues/{$venue->id}")
        ->assertStatus(403);
});
