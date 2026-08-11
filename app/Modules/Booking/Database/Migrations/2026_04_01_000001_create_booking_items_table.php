<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One row per (booking, item) pair — quantity is incremented in place
 * rather than inserting a new row per unit added (see BookingService::
 * addItem()). unit_price/total_price are a price snapshot taken from
 * items.price the moment an item is first added to a booking; they never
 * change afterwards even if the item's current price changes later.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_items', function (Blueprint $table) {
            $table->id();
            // cascadeOnDelete: booking_items is a true child record of its
            // booking (unlike bookings.field_id, which restricts deletion to
            // protect the parent) — in practice bookings are never deleted
            // (see Booking model), so this only matters for tests/tooling.
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            // restrictOnDelete: mirrors bookings.field_id — historical
            // booking_items rows must keep resolving to a real item, so a
            // referenced item can't be hard-deleted (Item soft-deletes
            // instead; see the items migration).
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 8, 2);
            $table->decimal('total_price', 10, 2);
            $table->timestamps();

            // Enforces "one row per booking/item combination" at the DB
            // level — BookingService::addItem() relies on this to decide
            // increment-existing vs create-new.
            $table->unique(['booking_id', 'item_id']);
            $table->index('item_id');
        });

        // Defense in depth for invariants the application already
        // maintains — same philosophy as the CHECK constraints on
        // `bookings` (see that migration).
        DB::statement('ALTER TABLE booking_items ADD CONSTRAINT booking_items_quantity_positive CHECK (quantity > 0)');
        DB::statement('ALTER TABLE booking_items ADD CONSTRAINT booking_items_total_matches_quantity CHECK (total_price = quantity * unit_price)');
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_items');
    }
};
