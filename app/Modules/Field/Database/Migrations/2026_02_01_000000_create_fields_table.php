<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type');
            $table->unsignedSmallInteger('capacity');
            // Current price — a booking snapshots its own hourly_price at
            // creation time, so changing this never rewrites past bookings.
            $table->decimal('hourly_price', 8, 2);
            $table->string('status')->default('ACTIVE');
            $table->timestamps();
            $table->softDeletes();

            $table->index('venue_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fields');
    }
};
