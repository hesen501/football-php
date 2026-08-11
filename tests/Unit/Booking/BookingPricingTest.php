<?php

use App\Modules\Booking\Enums\BookingSource;
use App\Modules\Booking\Models\PlatformSetting;
use App\Modules\Booking\Services\BookingService;
use App\Modules\Field\Models\Field;

beforeEach(function () {
    // $seed = true on the base TestCase already seeds commission_rate=10.00
    // via PlatformSettingSeeder.
    $this->service = new BookingService;
});

it('calculates total price as hourly price times duration', function () {
    $field = Field::factory()->create(['hourly_price' => 20]);

    $breakdown = $this->service->calculatePrice($field, 2, BookingSource::CUSTOMER_APP);

    expect($breakdown->hourlyPrice)->toBe(20.0)
        ->and($breakdown->totalPrice)->toBe(40.0);
});

it('applies the 10% platform commission to customer-app bookings', function () {
    $field = Field::factory()->create(['hourly_price' => 20]);

    $breakdown = $this->service->calculatePrice($field, 2, BookingSource::CUSTOMER_APP);

    expect($breakdown->commissionRate)->toBe(10.0)
        ->and($breakdown->commissionAmount)->toBe(4.0)
        ->and($breakdown->venueAmount)->toBe(36.0);
});

it('charges zero commission on admin-panel bookings', function () {
    $field = Field::factory()->create(['hourly_price' => 20]);

    $breakdown = $this->service->calculatePrice($field, 2, BookingSource::ADMIN_PANEL);

    expect($breakdown->commissionRate)->toBe(0.0)
        ->and($breakdown->commissionAmount)->toBe(0.0)
        ->and($breakdown->venueAmount)->toBe(40.0);
});

it('reads the live commission rate from platform_settings, not the config fallback', function () {
    PlatformSetting::query()->where('key', 'commission_rate')->update(['value' => '15.00']);
    $field = Field::factory()->create(['hourly_price' => 20]);

    $breakdown = $this->service->calculatePrice($field, 1, BookingSource::CUSTOMER_APP);

    expect($breakdown->commissionRate)->toBe(15.0)
        ->and($breakdown->commissionAmount)->toBe(3.0);
});
