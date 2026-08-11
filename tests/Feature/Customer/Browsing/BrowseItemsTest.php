<?php

use App\Modules\Item\Models\Item;

it('lists only active items without authentication', function () {
    Item::factory()->count(2)->create();
    Item::factory()->inactive()->create();

    $this->getJson('/api/items')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('does not expose status on public item listings', function () {
    $item = Item::factory()->create();

    $response = $this->getJson('/api/items')->assertOk();

    expect($response->json('data.0'))->not->toHaveKey('status');
});
