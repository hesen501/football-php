<?php

use App\Modules\Booking\Models\Booking;
use App\Modules\Field\Models\Field;
use App\Modules\User\Models\User;
use Carbon\CarbonImmutable;

it('marks booked hours unavailable and the rest available', function () {
    $admin = User::factory()->superAdmin()->create();
    $field = Field::factory()->create();
    $date = CarbonImmutable::now()->addDay()->startOfDay();
    Booking::factory()->forFieldAndTime($field, $date->setTime(18, 0), 2)->confirmed()->create();

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson("/api/admin/fields/{$field->id}/availability?date={$date->toDateString()}")
        ->assertOk();

    $slots = collect($response->json('data.slots'));

    expect($slots)->toHaveCount(24);
    expect($slots->firstWhere('start_time', $date->setTime(18, 0)->toIso8601String())['available'])->toBeFalse();
    expect($slots->firstWhere('start_time', $date->setTime(19, 0)->toIso8601String())['available'])->toBeFalse();
    expect($slots->firstWhere('start_time', $date->setTime(20, 0)->toIso8601String())['available'])->toBeTrue();
    expect($slots->firstWhere('start_time', $date->setTime(9, 0)->toIso8601String())['available'])->toBeTrue();
});

it('does not mark a cancelled booking\'s slot as unavailable', function () {
    $admin = User::factory()->superAdmin()->create();
    $field = Field::factory()->create();
    $date = CarbonImmutable::now()->addDay()->startOfDay();
    Booking::factory()->forFieldAndTime($field, $date->setTime(18, 0), 1)->cancelled()->create();

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson("/api/admin/fields/{$field->id}/availability?date={$date->toDateString()}")
        ->assertOk();

    $slots = collect($response->json('data.slots'));
    expect($slots->firstWhere('start_time', $date->setTime(18, 0)->toIso8601String())['available'])->toBeTrue();
});

it('requires a date query parameter', function () {
    $admin = User::factory()->superAdmin()->create();
    $field = Field::factory()->create();

    $this->actingAs($admin, 'sanctum')
        ->getJson("/api/admin/fields/{$field->id}/availability")
        ->assertStatus(422);
});
