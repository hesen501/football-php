<?php

use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\BookingItem;
use App\Modules\Item\Models\Item;
use App\Modules\User\Models\User;
use App\Modules\Venue\Models\Venue;

it('lets a super admin add an item to any booking', function () {
    $admin = User::factory()->superAdmin()->create();
    $booking = Booking::factory()->confirmed()->create();
    $item = Item::factory()->create(['price' => 2.00]);

    $this->actingAs($admin, 'sanctum')
        ->postJson("/api/admin/bookings/{$booking->id}/items", ['item_id' => $item->id])
        ->assertCreated()
        ->assertJsonPath('data.items.0.quantity', 1);
});

it('lets a manager add an item to a booking on their own venue', function () {
    $manager = User::factory()->venueManager()->create();
    $venue = Venue::factory()->create();
    $venue->managers()->attach($manager);
    $booking = Booking::factory()->confirmed()->create(['venue_id' => $venue->id]);
    $item = Item::factory()->create();

    $this->actingAs($manager, 'sanctum')
        ->postJson("/api/admin/bookings/{$booking->id}/items", ['item_id' => $item->id])
        ->assertCreated();
});

it('forbids a manager from adding an item to another manager\'s booking', function () {
    $manager = User::factory()->venueManager()->create();
    $otherVenue = Venue::factory()->create();
    $otherVenue->managers()->attach(User::factory()->venueManager()->create());
    $booking = Booking::factory()->confirmed()->create(['venue_id' => $otherVenue->id]);
    $item = Item::factory()->create();

    $this->actingAs($manager, 'sanctum')
        ->postJson("/api/admin/bookings/{$booking->id}/items", ['item_id' => $item->id])
        ->assertStatus(403);
});

it('lets a manager remove an item from a booking on their own venue', function () {
    $manager = User::factory()->venueManager()->create();
    $venue = Venue::factory()->create();
    $venue->managers()->attach($manager);
    $booking = Booking::factory()->confirmed()->create(['venue_id' => $venue->id]);
    $item = Item::factory()->create(['price' => 2.00]);
    BookingItem::factory()->forBookingAndItem($booking, $item, 2)->create();

    $this->actingAs($manager, 'sanctum')
        ->deleteJson("/api/admin/bookings/{$booking->id}/items/{$item->id}")
        ->assertOk()
        ->assertJsonPath('data.items.0.quantity', 1);
});

it('forbids a manager from removing an item from another manager\'s booking', function () {
    $manager = User::factory()->venueManager()->create();
    $otherVenue = Venue::factory()->create();
    $otherVenue->managers()->attach(User::factory()->venueManager()->create());
    $booking = Booking::factory()->confirmed()->create(['venue_id' => $otherVenue->id]);
    $item = Item::factory()->create();
    BookingItem::factory()->forBookingAndItem($booking, $item, 1)->create();

    $this->actingAs($manager, 'sanctum')
        ->deleteJson("/api/admin/bookings/{$booking->id}/items/{$item->id}")
        ->assertStatus(403);
});
