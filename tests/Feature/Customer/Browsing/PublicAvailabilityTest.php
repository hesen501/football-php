<?php

use App\Modules\Booking\Models\Booking;
use App\Modules\Field\Models\Field;
use Carbon\CarbonImmutable;

it('shows availability for a field without authentication', function () {
    $field = Field::factory()->create();
    $date = CarbonImmutable::now()->addDay()->startOfDay();
    Booking::factory()->forFieldAndTime($field, $date->setTime(18, 0))->confirmed()->create();

    $response = $this->getJson("/api/fields/{$field->id}/availability?date={$date->toDateString()}")
        ->assertOk();

    $slots = collect($response->json('data.slots'));
    expect($slots)->toHaveCount(24);
    expect($slots->firstWhere('start_time', $date->setTime(18, 0)->toIso8601String())['available'])->toBeFalse();
});

it('requires a date parameter', function () {
    $field = Field::factory()->create();

    $this->getJson("/api/fields/{$field->id}/availability")->assertStatus(422);
});
