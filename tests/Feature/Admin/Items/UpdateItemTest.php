<?php

use App\Modules\Booking\Models\Booking;
use App\Modules\Item\Models\Item;
use App\Modules\User\Models\User;

it('lets a super admin update an item\'s price', function () {
    $admin = User::factory()->superAdmin()->create();
    $item = Item::factory()->create(['price' => 1.00]);

    $this->actingAs($admin, 'sanctum')
        ->putJson("/api/admin/items/{$item->id}", ['price' => 1.50])
        ->assertOk()
        ->assertJsonPath('data.price', '1.50');

    $this->assertDatabaseHas('items', ['id' => $item->id, 'price' => 1.50]);
});

it('lets a super admin deactivate an item', function () {
    $admin = User::factory()->superAdmin()->create();
    $item = Item::factory()->create();

    $this->actingAs($admin, 'sanctum')
        ->patchJson("/api/admin/items/{$item->id}", ['status' => 'INACTIVE'])
        ->assertOk()
        ->assertJsonPath('data.status', 'INACTIVE');
});

it('does not change existing bookings when an item\'s price changes', function () {
    $admin = User::factory()->superAdmin()->create();
    $item = Item::factory()->create(['price' => 1.00]);
    $booking = Booking::factory()->confirmed()->create();

    $this->actingAs($admin, 'sanctum')
        ->postJson("/api/admin/bookings/{$booking->id}/items", ['item_id' => $item->id])
        ->assertCreated();

    // Price rises after the item was already added to the booking.
    $this->actingAs($admin, 'sanctum')
        ->putJson("/api/admin/items/{$item->id}", ['price' => 1.50])
        ->assertOk();

    $this->assertDatabaseHas('booking_items', [
        'booking_id' => $booking->id,
        'item_id' => $item->id,
        'unit_price' => 1.00,
        'total_price' => 1.00,
    ]);
});

it('forbids a venue manager from updating an item', function () {
    $manager = User::factory()->venueManager()->create();
    $item = Item::factory()->create();

    $this->actingAs($manager, 'sanctum')
        ->putJson("/api/admin/items/{$item->id}", ['price' => 2.00])
        ->assertStatus(403);
});
