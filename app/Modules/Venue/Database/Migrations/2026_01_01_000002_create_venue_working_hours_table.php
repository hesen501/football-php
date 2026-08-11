<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One row per venue per day of week (day_of_week: 0=Sunday..6=Saturday,
 * matching Carbon's ->dayOfWeek) — lets each venue set its own opening
 * hours per day instead of every hour of every day being bookable (see
 * BookingService, which used to hand out all 24 hours unconditionally).
 *
 * A venue with no rows here at all is treated as unrestricted by
 * BookingService, for backward compatibility — that should only happen for
 * venues written directly (factories/seeders/raw inserts) rather than
 * through VenueService::create(), which always seeds all 7 days for venues
 * created the normal way.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue_working_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->boolean('is_closed')->default(false);
            $table->time('opens_at')->nullable();
            $table->time('closes_at')->nullable();
            $table->timestamps();

            $table->unique(['venue_id', 'day_of_week']);
        });

        // A day is either marked closed (no hours needed) or has a real
        // opens_at < closes_at window — never both null-ish and "open".
        DB::statement(
            'ALTER TABLE venue_working_hours ADD CONSTRAINT venue_working_hours_valid_window '.
            'CHECK (is_closed OR (opens_at IS NOT NULL AND closes_at IS NOT NULL AND closes_at > opens_at))'
        );
        DB::statement('ALTER TABLE venue_working_hours ADD CONSTRAINT venue_working_hours_day_range CHECK (day_of_week BETWEEN 0 AND 6)');

        // Backfill: existing venues get the same default window newly
        // created venues are seeded with (see VenueService::create), so
        // deploying this feature doesn't silently make every existing venue
        // unbookable overnight — managers can still narrow the hours down
        // (or close specific days) afterwards.
        $venueIds = DB::table('venues')->pluck('id');
        $now = now();

        $rows = [];
        foreach ($venueIds as $venueId) {
            foreach (range(0, 6) as $day) {
                $rows[] = [
                    'venue_id' => $venueId,
                    'day_of_week' => $day,
                    'is_closed' => false,
                    'opens_at' => '08:00:00',
                    'closes_at' => '23:00:00',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('venue_working_hours')->insert($chunk);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_working_hours');
    }
};
