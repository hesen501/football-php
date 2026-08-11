<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Needed for the EXCLUDE USING gist constraint below (a plain btree
        // index can't express "no overlapping range for the same field_id").
        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('field_id')->constrained()->restrictOnDelete();
            // Denormalized from field.venue_id at creation time — avoids a
            // join for venue-level filters/reporting/dashboard queries.
            $table->foreignId('venue_id')->constrained()->restrictOnDelete();

            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->unsignedSmallInteger('duration_minutes');

            // Financial snapshot at booking time — never recalculated from
            // the field's current price or the platform's current commission
            // rate, so old bookings keep showing their original numbers even
            // after prices/rates change later.
            $table->decimal('hourly_price', 8, 2);
            $table->decimal('total_price', 10, 2);
            $table->decimal('commission_rate', 5, 2);
            $table->decimal('commission_amount', 10, 2);
            $table->decimal('venue_amount', 10, 2);

            $table->string('source');
            $table->string('status');
            $table->string('payment_status');
            $table->string('payment_reference')->nullable()->unique();
            $table->string('payment_provider')->nullable();

            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->foreignId('cancelled_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->text('notes')->nullable();
            $table->timestamps();
            // No softDeletes(): bookings are never deleted — they transition
            // through status (PENDING/CONFIRMED/CANCELLED/COMPLETED) instead,
            // preserving the full financial/historical record.

            $table->index(['field_id', 'start_time']);
            $table->index('venue_id');
            $table->index('status');
            $table->index('payment_status');
            $table->index('source');
            $table->index('user_id');
            $table->index(['field_id', 'status', 'start_time']);
        });

        // Defense in depth for the "fixed hourly slot" business rule — also
        // enforced in StoreBookingRequest, but a DB CHECK means it holds even
        // if some future code path writes to this table directly.
        DB::statement('ALTER TABLE bookings ADD CONSTRAINT bookings_end_after_start CHECK (end_time > start_time)');
        DB::statement("ALTER TABLE bookings ADD CONSTRAINT bookings_start_on_hour CHECK (date_trunc('hour', start_time) = start_time)");
        DB::statement('ALTER TABLE bookings ADD CONSTRAINT bookings_duration_whole_hours CHECK (MOD(EXTRACT(EPOCH FROM (end_time - start_time))::integer, 3600) = 0)');

        // The concurrency-safety core: Postgres refuses to let two
        // non-cancelled bookings for the same field have overlapping time
        // ranges, full stop — independent of any row-locking discipline the
        // application does or doesn't follow correctly. See BookingService
        // for the advisory-lock layer that sits in front of this for a
        // faster/cleaner failure path; this constraint is what actually
        // guarantees correctness under concurrent requests.
        DB::statement(
            'ALTER TABLE bookings ADD CONSTRAINT bookings_no_overlap '.
            "EXCLUDE USING gist (field_id WITH =, tsrange(start_time, end_time) WITH &&) WHERE (status <> 'CANCELLED')"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
