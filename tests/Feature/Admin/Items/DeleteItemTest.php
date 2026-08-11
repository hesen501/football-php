<?php

use App\Modules\Item\Models\Item;
use App\Modules\User\Models\User;

it('lets a super admin soft-delete an item', function () {
    $admin = User::factory()->superAdmin()->create();
    $item = Item::factory()->create();

    $this->actingAs($admin, 'sanctum')
        ->deleteJson("/api/admin/items/{$item->id}")
        ->assertNoContent();

    $this->assertSoftDeleted('items', ['id' => $item->id]);
});

it('forbids a venue manager from deleting an item', function () {
    $manager = User::factory()->venueManager()->create();
    $item = Item::factory()->create();

    $this->actingAs($manager, 'sanctum')
        ->deleteJson("/api/admin/items/{$item->id}")
        ->assertStatus(403);
});
