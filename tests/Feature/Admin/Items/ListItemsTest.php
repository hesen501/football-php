<?php

use App\Modules\Item\Models\Item;
use App\Modules\User\Models\User;

it('lets a super admin list all items, active and inactive', function () {
    $admin = User::factory()->superAdmin()->create();
    Item::factory()->count(2)->create();
    Item::factory()->inactive()->create();

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/admin/items')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('filters items by status', function () {
    $admin = User::factory()->superAdmin()->create();
    Item::factory()->count(2)->create();
    Item::factory()->inactive()->create();

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/admin/items?status=INACTIVE')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('forbids a venue manager from listing the items catalog', function () {
    $manager = User::factory()->venueManager()->create();

    $this->actingAs($manager, 'sanctum')
        ->getJson('/api/admin/items')
        ->assertStatus(403);
});
