<?php

use App\Modules\Field\Models\Field;
use App\Modules\User\Models\User;
use App\Modules\Venue\Models\Venue;
use App\Modules\Venue\Services\VenueService;
use Carbon\CarbonImmutable;

// A Monday at least a week out — deterministic day-of-week (1), and always
// ahead of "now" regardless of when the suite runs, so the 'start_time must
// be after:now' rule never flakes here.
it('rejects a booking outside a venue\'s working hours, e.g. midnight', function () {
    $admin = User::factory()->superAdmin()->create();
    $venue = app(VenueService::class)->create($admin, [
        'name' => 'Restricted Arena', 'address' => '1 St', 'city' => 'Baku',
    ]);
    $field = Field::factory()->create(['venue_id' => $venue->id]);
    $customer = User::factory()->customer()->create();

    $midnight = CarbonImmutable::now()->addWeeks(2)->startOfWeek(CarbonImmutable::MONDAY)->setTime(0, 0);

    $this->actingAs($admin, 'sanctum')->postJson('/api/admin/bookings', [
        'user_id' => $customer->id,
        'field_id' => $field->id,
        'start_time' => $midnight->toIso8601String(),
        'duration_hours' => 1,
    ])->assertStatus(409)->assertJsonPath('error_code', 'OUTSIDE_WORKING_HOURS');
});

it('rejects a booking that ends after closing time', function () {
    $admin = User::factory()->superAdmin()->create();
    $venue = app(VenueService::class)->create($admin, [
        'name' => 'Late Night Arena', 'address' => '1 St', 'city' => 'Baku',
    ]);
    $field = Field::factory()->create(['venue_id' => $venue->id]);
    $customer = User::factory()->customer()->create();

    // Default hours are 08:00-23:00 — a 2-hour booking starting at 22:00 would
    // end at 00:00, past closing.
    $start = CarbonImmutable::now()->addWeeks(2)->startOfWeek(CarbonImmutable::MONDAY)->setTime(22, 0);

    $this->actingAs($admin, 'sanctum')->postJson('/api/admin/bookings', [
        'user_id' => $customer->id,
        'field_id' => $field->id,
        'start_time' => $start->toIso8601String(),
        'duration_hours' => 2,
    ])->assertStatus(409)->assertJsonPath('error_code', 'OUTSIDE_WORKING_HOURS');
});

it('rejects a booking on a day of the week the venue marked closed', function () {
    $admin = User::factory()->superAdmin()->create();
    $venue = app(VenueService::class)->create($admin, [
        'name' => 'Sunday-Closed Arena', 'address' => '1 St', 'city' => 'Baku',
    ]);
    $field = Field::factory()->create(['venue_id' => $venue->id]);
    $customer = User::factory()->customer()->create();

    app(VenueService::class)->setWorkingHours($venue, collect(range(0, 6))->map(fn (int $day) => $day === 0
        ? ['day_of_week' => 0, 'is_closed' => true]
        : ['day_of_week' => $day, 'is_closed' => false, 'opens_at' => '08:00', 'closes_at' => '23:00'])->all());

    // The Sunday right before that Monday.
    $sunday = CarbonImmutable::now()->addWeeks(2)->startOfWeek(CarbonImmutable::MONDAY)->subDay()->setTime(10, 0);

    $this->actingAs($admin, 'sanctum')->postJson('/api/admin/bookings', [
        'user_id' => $customer->id,
        'field_id' => $field->id,
        'start_time' => $sunday->toIso8601String(),
        'duration_hours' => 1,
    ])->assertStatus(409)->assertJsonPath('error_code', 'OUTSIDE_WORKING_HOURS');
});

it('allows a booking within the venue\'s configured working hours', function () {
    $admin = User::factory()->superAdmin()->create();
    $venue = app(VenueService::class)->create($admin, [
        'name' => 'Normal Hours Arena', 'address' => '1 St', 'city' => 'Baku',
    ]);
    $field = Field::factory()->create(['venue_id' => $venue->id]);
    $customer = User::factory()->customer()->create();

    $start = CarbonImmutable::now()->addWeeks(2)->startOfWeek(CarbonImmutable::MONDAY)->setTime(10, 0);

    $this->actingAs($admin, 'sanctum')->postJson('/api/admin/bookings', [
        'user_id' => $customer->id,
        'field_id' => $field->id,
        'start_time' => $start->toIso8601String(),
        'duration_hours' => 1,
    ])->assertCreated();
});

it('leaves a venue with no configured working hours unrestricted, including at midnight', function () {
    // Venue::factory() bypasses VenueService — no working_hours rows get
    // seeded, which is exactly the legacy/back-compat case this preserves.
    $admin = User::factory()->superAdmin()->create();
    $venue = Venue::factory()->create();
    $field = Field::factory()->create(['venue_id' => $venue->id]);
    $customer = User::factory()->customer()->create();

    expect($venue->workingHours()->count())->toBe(0);

    $midnight = CarbonImmutable::now()->addWeeks(2)->startOfWeek(CarbonImmutable::MONDAY)->setTime(0, 0);

    $this->actingAs($admin, 'sanctum')->postJson('/api/admin/bookings', [
        'user_id' => $customer->id,
        'field_id' => $field->id,
        'start_time' => $midnight->toIso8601String(),
        'duration_hours' => 1,
    ])->assertCreated();
});
