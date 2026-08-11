<?php

use App\Modules\Field\Models\Field;
use App\Modules\User\Models\User;
use App\Modules\Venue\Models\Venue;

it('lets a super admin update any field', function () {
    $admin = User::factory()->superAdmin()->create();
    $field = Field::factory()->create(['hourly_price' => 20]);

    $this->actingAs($admin, 'sanctum')
        ->putJson("/api/admin/fields/{$field->id}", ['hourly_price' => 30])
        ->assertOk()
        ->assertJsonPath('data.hourly_price', '30.00');
});

it('lets a manager update a field on their own venue', function () {
    $manager = User::factory()->venueManager()->create();
    $venue = Venue::factory()->create();
    $venue->managers()->attach($manager);
    $field = Field::factory()->create(['venue_id' => $venue->id]);

    $this->actingAs($manager, 'sanctum')
        ->putJson("/api/admin/fields/{$field->id}", ['status' => 'MAINTENANCE'])
        ->assertOk()
        ->assertJsonPath('data.status', 'MAINTENANCE');
});

it('forbids a manager from updating a field on another manager\'s venue', function () {
    $manager = User::factory()->venueManager()->create();
    $otherVenue = Venue::factory()->create();
    $otherVenue->managers()->attach(User::factory()->venueManager()->create());
    $field = Field::factory()->create(['venue_id' => $otherVenue->id]);

    $this->actingAs($manager, 'sanctum')
        ->putJson("/api/admin/fields/{$field->id}", ['hourly_price' => 99])
        ->assertStatus(403);
});
