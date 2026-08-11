<?php

use App\Modules\Field\Models\Field;
use App\Modules\User\Models\User;
use App\Modules\Venue\Models\Venue;

it('lets a super admin soft-delete a field', function () {
    $admin = User::factory()->superAdmin()->create();
    $field = Field::factory()->create();

    $this->actingAs($admin, 'sanctum')
        ->deleteJson("/api/admin/fields/{$field->id}")
        ->assertNoContent();

    $this->assertSoftDeleted('fields', ['id' => $field->id]);
});

it('lets a manager delete a field on their own venue', function () {
    $manager = User::factory()->venueManager()->create();
    $venue = Venue::factory()->create();
    $venue->managers()->attach($manager);
    $field = Field::factory()->create(['venue_id' => $venue->id]);

    $this->actingAs($manager, 'sanctum')
        ->deleteJson("/api/admin/fields/{$field->id}")
        ->assertNoContent();
});

it('forbids a manager from deleting a field on another manager\'s venue', function () {
    $manager = User::factory()->venueManager()->create();
    $otherVenue = Venue::factory()->create();
    $otherVenue->managers()->attach(User::factory()->venueManager()->create());
    $field = Field::factory()->create(['venue_id' => $otherVenue->id]);

    $this->actingAs($manager, 'sanctum')
        ->deleteJson("/api/admin/fields/{$field->id}")
        ->assertStatus(403);
});
