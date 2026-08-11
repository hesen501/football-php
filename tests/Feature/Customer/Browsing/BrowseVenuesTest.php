<?php

use App\Modules\Venue\Models\Venue;

it('lists active venues without any authentication', function () {
    Venue::factory()->count(2)->create();
    Venue::factory()->inactive()->create();

    $this->getJson('/api/venues')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('does not expose managers or status on public venue listings', function () {
    Venue::factory()->create();

    $response = $this->getJson('/api/venues')->assertOk();

    expect($response->json('data.0'))
        ->not->toHaveKey('managers')
        ->not->toHaveKey('status');
});

it('filters public venues by city', function () {
    Venue::factory()->create(['city' => 'Baku']);
    Venue::factory()->create(['city' => 'Ganja']);

    $this->getJson('/api/venues?city=Ganja')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('searches public venues by name', function () {
    Venue::factory()->create(['name' => 'Baku National Arena']);
    Venue::factory()->create(['name' => 'Something Else']);

    $this->getJson('/api/venues?search=National')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('shows a single active venue by slug', function () {
    $venue = Venue::factory()->create(['slug' => 'baku-national-arena']);

    $this->getJson('/api/venues/baku-national-arena')
        ->assertOk()
        ->assertJsonPath('data.id', $venue->id);
});

it('returns 404 for an inactive venue\'s public page', function () {
    $venue = Venue::factory()->inactive()->create(['slug' => 'hidden-venue']);

    $this->getJson('/api/venues/hidden-venue')->assertStatus(404);
});

it('returns 404 for a soft-deleted venue\'s public page', function () {
    $venue = Venue::factory()->create(['slug' => 'deleted-venue']);
    $venue->delete();

    $this->getJson('/api/venues/deleted-venue')->assertStatus(404);
});
