<?php

use App\Modules\Field\Models\Field;
use App\Modules\Venue\Models\Venue;

it('lists active fields for a venue by slug', function () {
    $venue = Venue::factory()->create(['slug' => 'baku-arena']);
    Field::factory()->count(2)->create(['venue_id' => $venue->id]);
    Field::factory()->inactive()->create(['venue_id' => $venue->id]);

    $this->getJson('/api/venues/baku-arena/fields')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('filters a venue\'s public fields by type', function () {
    $venue = Venue::factory()->create(['slug' => 'baku-arena']);
    Field::factory()->create(['venue_id' => $venue->id, 'type' => 'INDOOR']);
    Field::factory()->create(['venue_id' => $venue->id, 'type' => 'OUTDOOR']);

    $this->getJson('/api/venues/baku-arena/fields?type=INDOOR')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('returns 404 when listing fields for an inactive venue', function () {
    $venue = Venue::factory()->inactive()->create(['slug' => 'hidden-venue']);
    Field::factory()->create(['venue_id' => $venue->id]);

    $this->getJson('/api/venues/hidden-venue/fields')->assertStatus(404);
});

it('shows a single active field with its public venue nested', function () {
    $field = Field::factory()->create();

    $this->getJson("/api/fields/{$field->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $field->id)
        ->assertJsonPath('data.venue.id', $field->venue_id);
});

it('returns 404 for a field under maintenance', function () {
    $field = Field::factory()->underMaintenance()->create();

    $this->getJson("/api/fields/{$field->id}")->assertStatus(404);
});

it('returns 404 for a field belonging to an inactive venue', function () {
    $venue = Venue::factory()->inactive()->create();
    $field = Field::factory()->create(['venue_id' => $venue->id]);

    $this->getJson("/api/fields/{$field->id}")->assertStatus(404);
});

it('does not expose status on public field listings', function () {
    $field = Field::factory()->create();

    $response = $this->getJson("/api/fields/{$field->id}")->assertOk();

    expect($response->json('data'))->not->toHaveKey('status');
});
