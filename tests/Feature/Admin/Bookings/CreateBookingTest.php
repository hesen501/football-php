<?php

use App\Modules\Booking\Models\Booking;
use App\Modules\Field\Models\Field;
use App\Modules\User\Models\User;
use App\Modules\Venue\Models\Venue;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

// $seed = true on the base TestCase already seeds commission_rate=10.00 via
// PlatformSettingSeeder.

it('lets a super admin create a booking as an admin-panel booking with no commission', function () {
    $admin = User::factory()->superAdmin()->create();
    $customer = User::factory()->customer()->create();
    $field = Field::factory()->create(['hourly_price' => 20]);
    $start = CarbonImmutable::now()->addDay()->startOfHour();

    $response = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/bookings', [
        'user_id' => $customer->id,
        'field_id' => $field->id,
        'start_time' => $start->toIso8601String(),
        'duration_hours' => 2,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.source', 'ADMIN_PANEL')
        ->assertJsonPath('data.status', 'CONFIRMED')
        ->assertJsonPath('data.payment_status', 'PENDING')
        ->assertJsonPath('data.hourly_price', '20.00')
        ->assertJsonPath('data.total_price', '40.00')
        ->assertJsonPath('data.commission_rate', '0.00')
        ->assertJsonPath('data.commission_amount', '0.00')
        ->assertJsonPath('data.venue_amount', '40.00');
});

it('lets a manager create a booking on their own venue\'s field', function () {
    $manager = User::factory()->venueManager()->create();
    $venue = Venue::factory()->create();
    $venue->managers()->attach($manager);
    $field = Field::factory()->create(['venue_id' => $venue->id]);
    $customer = User::factory()->customer()->create();
    $start = CarbonImmutable::now()->addDay()->startOfHour();

    $this->actingAs($manager, 'sanctum')->postJson('/api/admin/bookings', [
        'user_id' => $customer->id,
        'field_id' => $field->id,
        'start_time' => $start->toIso8601String(),
        'duration_hours' => 1,
    ])->assertCreated();
});

it('forbids a manager from creating a booking on another manager\'s venue', function () {
    $manager = User::factory()->venueManager()->create();
    $otherVenue = Venue::factory()->create();
    $otherVenue->managers()->attach(User::factory()->venueManager()->create());
    $field = Field::factory()->create(['venue_id' => $otherVenue->id]);
    $customer = User::factory()->customer()->create();
    $start = CarbonImmutable::now()->addDay()->startOfHour();

    $this->actingAs($manager, 'sanctum')->postJson('/api/admin/bookings', [
        'user_id' => $customer->id,
        'field_id' => $field->id,
        'start_time' => $start->toIso8601String(),
        'duration_hours' => 1,
    ])->assertStatus(403);
});

it('rejects a booking that overlaps an existing one', function () {
    $admin = User::factory()->superAdmin()->create();
    $customer = User::factory()->customer()->create();
    $field = Field::factory()->create(['hourly_price' => 20]);
    $start = CarbonImmutable::now()->addDay()->startOfHour();

    Booking::factory()->forFieldAndTime($field, $start, 2)->create();

    // 30 minutes into the existing 2-hour booking — overlaps.
    $overlapStart = $start->addHour();

    $this->actingAs($admin, 'sanctum')->postJson('/api/admin/bookings', [
        'user_id' => $customer->id,
        'field_id' => $field->id,
        'start_time' => $overlapStart->toIso8601String(),
        'duration_hours' => 1,
    ])->assertStatus(409)->assertJsonPath('error_code', 'BOOKING_SLOT_UNAVAILABLE');
});

it('allows a back-to-back booking that starts exactly when another ends', function () {
    $admin = User::factory()->superAdmin()->create();
    $customer = User::factory()->customer()->create();
    $field = Field::factory()->create(['hourly_price' => 20]);
    $start = CarbonImmutable::now()->addDay()->startOfHour();

    Booking::factory()->forFieldAndTime($field, $start, 1)->create();

    $this->actingAs($admin, 'sanctum')->postJson('/api/admin/bookings', [
        'user_id' => $customer->id,
        'field_id' => $field->id,
        'start_time' => $start->addHour()->toIso8601String(),
        'duration_hours' => 1,
    ])->assertCreated();
});

it('allows re-booking a slot that was previously cancelled', function () {
    $admin = User::factory()->superAdmin()->create();
    $customer = User::factory()->customer()->create();
    $field = Field::factory()->create(['hourly_price' => 20]);
    $start = CarbonImmutable::now()->addDay()->startOfHour();

    Booking::factory()->forFieldAndTime($field, $start, 1)->cancelled()->create();

    $this->actingAs($admin, 'sanctum')->postJson('/api/admin/bookings', [
        'user_id' => $customer->id,
        'field_id' => $field->id,
        'start_time' => $start->toIso8601String(),
        'duration_hours' => 1,
    ])->assertCreated();
});

it('the database exclusion constraint rejects an overlap even bypassing the service pre-check', function () {
    // Proves the guarantee doesn't depend on BookingService's own overlap
    // query — even a raw insert around it is stopped by the DB itself.
    $field = Field::factory()->create(['hourly_price' => 20]);
    $customer = User::factory()->customer()->create();
    $start = CarbonImmutable::now()->addDay()->startOfHour();

    Booking::factory()->forFieldAndTime($field, $start, 1)->create();

    expect(fn () => DB::table('bookings')->insert([
        'user_id' => $customer->id,
        'field_id' => $field->id,
        'venue_id' => $field->venue_id,
        'start_time' => $start,
        'end_time' => $start->addHour(),
        'duration_minutes' => 60,
        'hourly_price' => 20,
        'total_price' => 20,
        'commission_rate' => 10,
        'commission_amount' => 2,
        'venue_amount' => 18,
        'source' => 'CUSTOMER_APP',
        'status' => 'CONFIRMED',
        'payment_status' => 'PENDING',
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});

it('rejects a booking on a field under maintenance', function () {
    $admin = User::factory()->superAdmin()->create();
    $customer = User::factory()->customer()->create();
    $field = Field::factory()->underMaintenance()->create();

    $this->actingAs($admin, 'sanctum')->postJson('/api/admin/bookings', [
        'user_id' => $customer->id,
        'field_id' => $field->id,
        'start_time' => CarbonImmutable::now()->addDay()->startOfHour()->toIso8601String(),
        'duration_hours' => 1,
    ])->assertStatus(409)->assertJsonPath('error_code', 'FIELD_NOT_BOOKABLE');
});

it('rejects a booking on an inactive venue', function () {
    $admin = User::factory()->superAdmin()->create();
    $customer = User::factory()->customer()->create();
    $venue = Venue::factory()->inactive()->create();
    $field = Field::factory()->create(['venue_id' => $venue->id]);

    $this->actingAs($admin, 'sanctum')->postJson('/api/admin/bookings', [
        'user_id' => $customer->id,
        'field_id' => $field->id,
        'start_time' => CarbonImmutable::now()->addDay()->startOfHour()->toIso8601String(),
        'duration_hours' => 1,
    ])->assertStatus(409)->assertJsonPath('error_code', 'VENUE_NOT_ACTIVE');
});

it('rejects a start time that is not aligned to the hour', function () {
    $admin = User::factory()->superAdmin()->create();
    $customer = User::factory()->customer()->create();
    $field = Field::factory()->create();

    $this->actingAs($admin, 'sanctum')->postJson('/api/admin/bookings', [
        'user_id' => $customer->id,
        'field_id' => $field->id,
        'start_time' => CarbonImmutable::now()->addDay()->startOfHour()->addMinutes(30)->toIso8601String(),
        'duration_hours' => 1,
    ])->assertStatus(422)->assertJsonValidationErrors('start_time');
});

it('rejects a start time in the past', function () {
    $admin = User::factory()->superAdmin()->create();
    $customer = User::factory()->customer()->create();
    $field = Field::factory()->create();

    $this->actingAs($admin, 'sanctum')->postJson('/api/admin/bookings', [
        'user_id' => $customer->id,
        'field_id' => $field->id,
        'start_time' => CarbonImmutable::now()->subDay()->startOfHour()->toIso8601String(),
        'duration_hours' => 1,
    ])->assertStatus(422)->assertJsonValidationErrors('start_time');
});

it('validates required fields', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/admin/bookings', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['user_id', 'field_id', 'start_time', 'duration_hours']);
});

it('forbids a customer token from creating an admin-panel booking despite holding bookings.create', function () {
    // Regression test for the admin.panel middleware: CUSTOMER intentionally
    // holds bookings.create (for the future customer app), so this must be
    // blocked by the route-level admin/customer surface guard, not by permissions.
    $customer = User::factory()->customer()->create();
    $field = Field::factory()->create();

    $this->actingAs($customer, 'sanctum')->postJson('/api/admin/bookings', [
        'user_id' => $customer->id,
        'field_id' => $field->id,
        'start_time' => CarbonImmutable::now()->addDay()->startOfHour()->toIso8601String(),
        'duration_hours' => 1,
    ])->assertStatus(403);
});
