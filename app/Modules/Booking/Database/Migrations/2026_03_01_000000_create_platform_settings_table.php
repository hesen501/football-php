<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Minimal key/value store so SUPER_ADMIN can change platform-wide settings
 * (currently just the commission rate) without a redeploy. Individual
 * bookings still snapshot their own commission_rate at creation time, so
 * changing this value here never rewrites historical bookings.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('value');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
    }
};
