<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One reusable polymorphic table for every entity that can own uploaded
 * images (Venue, Field, Item, User — see App\Shared\Concerns\HasMedia),
 * instead of a `venues.cover_image_url` / `items.image_url` / ... column
 * per entity. `model_type` stores the morph-map alias configured in
 * MediaModuleServiceProvider (e.g. "venue", not the FQCN) — see that class.
 *
 * No foreign key from model_id: it's polymorphic, so it can't reference a
 * single table. Cleanup on the owner's deletion is handled in application
 * code instead (HasMedia::bootHasMedia()), since the DB can't cascade this
 * itself.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->string('collection');
            $table->string('disk');
            $table->string('path');
            $table->string('original_filename')->nullable();
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['model_type', 'model_id']);
            $table->index(['model_type', 'model_id', 'collection']);
            $table->index('sort_order');
        });

        // At most one AVATAR/COVER/IMAGE row per owner — the DB-level twin
        // of MediaCollection::isSingleton()/MediaService's singleton-replace
        // logic, same defense-in-depth philosophy as the CHECK/EXCLUDE
        // constraints on `bookings` (see that migration). GALLERY is
        // intentionally excluded — an owner may have many gallery rows.
        DB::statement(
            'CREATE UNIQUE INDEX media_singleton_collection_unique ON media (model_type, model_id, collection) '.
            "WHERE collection IN ('avatar', 'cover', 'image')"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
