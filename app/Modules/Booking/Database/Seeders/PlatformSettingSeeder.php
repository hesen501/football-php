<?php

namespace App\Modules\Booking\Database\Seeders;

use App\Modules\Booking\Models\PlatformSetting;
use Illuminate\Database\Seeder;

class PlatformSettingSeeder extends Seeder
{
    public function run(): void
    {
        PlatformSetting::query()->firstOrCreate(
            ['key' => 'commission_rate'],
            ['value' => (string) config('booking.commission_rate')],
        );
    }
}
