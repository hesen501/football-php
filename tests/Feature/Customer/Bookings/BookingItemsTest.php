<?php

use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\BookingItem;
use App\Modules\Item\Models\Item;
use App\Modules\User\Models\User;
use Carbon\CarbonImmutable;

it('adds an item to a booking, creating a booking_items row at quantity 1', function () {
    $customer = User::factory()->customer()->create();
    $start = CarbonImmutable::now()->addDay()->startOfHour();
    $booking = Booking::factory()->confirmed()->create(['user_id' => $customer->id, 'start_time' => $start, 'end_time' => $start->addHour()]);
    $item = Item::factory()->create(['name' => 'Water Bottle', 'price' => 1.00]);

    $response = $this->actingAs($customer, 'sanctum')
        ->postJson("/api/bookings/{$booking->id}/items", ['item_id' => $item->id])
        ->assertCreated();

    $response->assertJsonCount(1, 'data.items')
        ->assertJsonPath('data.items.0.id', $item->id)
        ->assertJsonPath('data.items.0.name', 'Water Bottle')
        ->assertJsonPath('data.items.0.quantity', 1)
        ->assertJsonPath('data.items.0.unit_price', '1.00')
        ->assertJsonPath('data.items.0.total_price', '1.00');

    $this->assertDatabaseHas('booking_items', [
        'booking_id' => $booking->id,
        'item_id' => $item->id,
        'quantity' => 1,
        'unit_price' => 1.00,
        'total_price' => 1.00,
    ]);
});

it('increments quantity instead of duplicating the row when the same item is added again', function () {
    $customer = User::factory()->customer()->create();
    $start = CarbonImmutable::now()->addDay()->startOfHour();
    $booking = Booking::factory()->confirmed()->create(['user_id' => $customer->id, 'start_time' => $start, 'end_time' => $start->addHour()]);
    $item = Item::factory()->create(['price' => 1.00]);

    $this->actingAs($customer, 'sanctum')->postJson("/api/bookings/{$booking->id}/items", ['item_id' => $item->id])->assertCreated();
    $this->actingAs($customer, 'sanctum')->postJson("/api/bookings/{$booking->id}/items", ['item_id' => $item->id])->assertCreated();
    $response = $this->actingAs($customer, 'sanctum')->postJson("/api/bookings/{$booking->id}/items", ['item_id' => $item->id])->assertCreated();

    $response->assertJsonCount(1, 'data.items')
        ->assertJsonPath('data.items.0.quantity', 3)
        ->assertJsonPath('data.items.0.total_price', '3.00');

    expect(BookingItem::query()->where('booking_id', $booking->id)->where('item_id', $item->id)->count())->toBe(1);
    $this->assertDatabaseHas('booking_items', ['booking_id' => $booking->id, 'item_id' => $item->id, 'quantity' => 3]);
});

it('tracks multiple different items on the same booking independently', function () {
    $customer = User::factory()->customer()->create();
    $start = CarbonImmutable::now()->addDay()->startOfHour();
    $booking = Booking::factory()->confirmed()->create(['user_id' => $customer->id, 'start_time' => $start, 'end_time' => $start->addHour()]);
    $water = Item::factory()->create(['name' => 'Water', 'price' => 1.00]);
    $gloves = Item::factory()->create(['name' => 'Gloves', 'price' => 3.00]);
    $boots = Item::factory()->create(['name' => 'Boots', 'price' => 5.00]);

    foreach ([$water, $water, $water, $gloves, $boots, $boots] as $item) {
        $this->actingAs($customer, 'sanctum')->postJson("/api/bookings/{$booking->id}/items", ['item_id' => $item->id])->assertCreated();
    }

    $response = $this->actingAs($customer, 'sanctum')->getJson("/api/bookings/{$booking->id}")->assertOk();

    $items = collect($response->json('data.items'))->keyBy('name');
    expect($items['Water']['quantity'])->toBe(3)
        ->and($items['Water']['total_price'])->toBe('3.00')
        ->and($items['Gloves']['quantity'])->toBe(1)
        ->and($items['Gloves']['total_price'])->toBe('3.00')
        ->and($items['Boots']['quantity'])->toBe(2)
        ->and($items['Boots']['total_price'])->toBe('10.00');
});

it('includes items in the booking total, matching base_price + items_total', function () {
    $customer = User::factory()->customer()->create();
    $start = CarbonImmutable::now()->addDay()->startOfHour();
    $booking = Booking::factory()->confirmed()->create([
        'user_id' => $customer->id, 'start_time' => $start, 'end_time' => $start->addHour(), 'total_price' => 40.00,
    ]);
    $water = Item::factory()->create(['price' => 1.00]);
    $gloves = Item::factory()->create(['price' => 3.00]);
    $boots = Item::factory()->create(['price' => 5.00]);

    foreach ([$water, $water, $water, $gloves, $boots, $boots] as $item) {
        $this->actingAs($customer, 'sanctum')->postJson("/api/bookings/{$booking->id}/items", ['item_id' => $item->id])->assertCreated();
    }

    $response = $this->actingAs($customer, 'sanctum')->getJson("/api/bookings/{$booking->id}")->assertOk();

    $response->assertJsonPath('data.base_price', '40.00')
        ->assertJsonPath('data.items_total', '16.00')
        ->assertJsonPath('data.total_price', '56.00');
});

it('removes one unit of an item, decrementing its quantity', function () {
    $customer = User::factory()->customer()->create();
    $start = CarbonImmutable::now()->addDay()->startOfHour();
    $booking = Booking::factory()->confirmed()->create(['user_id' => $customer->id, 'start_time' => $start, 'end_time' => $start->addHour()]);
    $item = Item::factory()->create(['price' => 1.00]);
    BookingItem::factory()->forBookingAndItem($booking, $item, 3)->create();

    $response = $this->actingAs($customer, 'sanctum')
        ->deleteJson("/api/bookings/{$booking->id}/items/{$item->id}")
        ->assertOk();

    $response->assertJsonPath('data.items.0.quantity', 2)
        ->assertJsonPath('data.items.0.total_price', '2.00');

    $this->assertDatabaseHas('booking_items', ['booking_id' => $booking->id, 'item_id' => $item->id, 'quantity' => 2]);
});

it('deletes the booking_items row once quantity reaches zero', function () {
    $customer = User::factory()->customer()->create();
    $start = CarbonImmutable::now()->addDay()->startOfHour();
    $booking = Booking::factory()->confirmed()->create(['user_id' => $customer->id, 'start_time' => $start, 'end_time' => $start->addHour()]);
    $item = Item::factory()->create(['price' => 1.00]);
    BookingItem::factory()->forBookingAndItem($booking, $item, 1)->create();

    $response = $this->actingAs($customer, 'sanctum')
        ->deleteJson("/api/bookings/{$booking->id}/items/{$item->id}")
        ->assertOk();

    $response->assertJsonCount(0, 'data.items');
    $this->assertDatabaseMissing('booking_items', ['booking_id' => $booking->id, 'item_id' => $item->id]);
});

it('recalculates the booking total after removing an item', function () {
    $customer = User::factory()->customer()->create();
    $start = CarbonImmutable::now()->addDay()->startOfHour();
    $booking = Booking::factory()->confirmed()->create([
        'user_id' => $customer->id, 'start_time' => $start, 'end_time' => $start->addHour(), 'total_price' => 40.00,
    ]);
    $item = Item::factory()->create(['price' => 5.00]);
    BookingItem::factory()->forBookingAndItem($booking, $item, 2)->create();

    $this->actingAs($customer, 'sanctum')
        ->deleteJson("/api/bookings/{$booking->id}/items/{$item->id}")
        ->assertOk()
        ->assertJsonPath('data.items_total', '5.00')
        ->assertJsonPath('data.total_price', '45.00');
});

it('snapshots the item price into unit_price and ignores later price changes', function () {
    $customer = User::factory()->customer()->create();
    $start = CarbonImmutable::now()->addDay()->startOfHour();
    $booking = Booking::factory()->confirmed()->create(['user_id' => $customer->id, 'start_time' => $start, 'end_time' => $start->addHour()]);
    $item = Item::factory()->create(['price' => 1.00]);

    $this->actingAs($customer, 'sanctum')
        ->postJson("/api/bookings/{$booking->id}/items", ['item_id' => $item->id])
        ->assertCreated();

    $item->update(['price' => 1.50]);

    // Adding one more unit reuses the *stored* unit_price, not the item's new price.
    $response = $this->actingAs($customer, 'sanctum')
        ->postJson("/api/bookings/{$booking->id}/items", ['item_id' => $item->id])
        ->assertCreated();

    $response->assertJsonPath('data.items.0.unit_price', '1.00')
        ->assertJsonPath('data.items.0.total_price', '2.00');
});

it('still allows removing an item that was later soft-deleted from the catalog', function () {
    $customer = User::factory()->customer()->create();
    $start = CarbonImmutable::now()->addDay()->startOfHour();
    $booking = Booking::factory()->confirmed()->create(['user_id' => $customer->id, 'start_time' => $start, 'end_time' => $start->addHour()]);
    $item = Item::factory()->create(['price' => 1.00]);
    BookingItem::factory()->forBookingAndItem($booking, $item, 1)->create();

    $item->delete(); // e.g. discontinued after being ordered — soft-deleted, not gone.

    $this->actingAs($customer, 'sanctum')
        ->deleteJson("/api/bookings/{$booking->id}/items/{$item->id}")
        ->assertOk()
        ->assertJsonCount(0, 'data.items');
});

it('rejects adding a nonexistent item', function () {
    $customer = User::factory()->customer()->create();
    $start = CarbonImmutable::now()->addDay()->startOfHour();
    $booking = Booking::factory()->confirmed()->create(['user_id' => $customer->id, 'start_time' => $start, 'end_time' => $start->addHour()]);

    $this->actingAs($customer, 'sanctum')
        ->postJson("/api/bookings/{$booking->id}/items", ['item_id' => 999999])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['item_id']);
});

it('rejects adding an inactive item', function () {
    $customer = User::factory()->customer()->create();
    $start = CarbonImmutable::now()->addDay()->startOfHour();
    $booking = Booking::factory()->confirmed()->create(['user_id' => $customer->id, 'start_time' => $start, 'end_time' => $start->addHour()]);
    $item = Item::factory()->inactive()->create();

    $this->actingAs($customer, 'sanctum')
        ->postJson("/api/bookings/{$booking->id}/items", ['item_id' => $item->id])
        ->assertStatus(409)
        ->assertJsonPath('error_code', 'ITEM_NOT_ACTIVE');
});

it('forbids modifying another customer\'s booking items', function () {
    $customer = User::factory()->customer()->create();
    $otherCustomer = User::factory()->customer()->create();
    $start = CarbonImmutable::now()->addDay()->startOfHour();
    $booking = Booking::factory()->confirmed()->create(['user_id' => $otherCustomer->id, 'start_time' => $start, 'end_time' => $start->addHour()]);
    $item = Item::factory()->create();

    $this->actingAs($customer, 'sanctum')
        ->postJson("/api/bookings/{$booking->id}/items", ['item_id' => $item->id])
        ->assertStatus(403);
});

it('rejects removing an item that was never added to the booking', function () {
    $customer = User::factory()->customer()->create();
    $start = CarbonImmutable::now()->addDay()->startOfHour();
    $booking = Booking::factory()->confirmed()->create(['user_id' => $customer->id, 'start_time' => $start, 'end_time' => $start->addHour()]);
    $item = Item::factory()->create();

    $this->actingAs($customer, 'sanctum')
        ->deleteJson("/api/bookings/{$booking->id}/items/{$item->id}")
        ->assertStatus(404);
});

it('rejects adding an item to a cancelled booking', function () {
    $customer = User::factory()->customer()->create();
    $start = CarbonImmutable::now()->addDay()->startOfHour();
    $booking = Booking::factory()->cancelled()->create(['user_id' => $customer->id, 'start_time' => $start, 'end_time' => $start->addHour()]);
    $item = Item::factory()->create();

    $this->actingAs($customer, 'sanctum')
        ->postJson("/api/bookings/{$booking->id}/items", ['item_id' => $item->id])
        ->assertStatus(409)
        ->assertJsonPath('error_code', 'BOOKING_NOT_MODIFIABLE');
});

it('rejects adding an item to a booking that already started', function () {
    $customer = User::factory()->customer()->create();
    $booking = Booking::factory()->confirmed()->create([
        'user_id' => $customer->id,
        'start_time' => now()->subHours(2)->startOfHour(),
        'end_time' => now()->subHour()->startOfHour(),
    ]);
    $item = Item::factory()->create();

    $this->actingAs($customer, 'sanctum')
        ->postJson("/api/bookings/{$booking->id}/items", ['item_id' => $item->id])
        ->assertStatus(409)
        ->assertJsonPath('error_code', 'BOOKING_NOT_MODIFIABLE');
});
