<?php

namespace App\Modules\Booking\Database\Seeders;

use App\Modules\Booking\Enums\BookingSource;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Enums\PaymentStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\PlatformSetting;
use App\Modules\Field\Enums\FieldStatus;
use App\Modules\Field\Models\Field;
use App\Modules\User\Enums\UserRole;
use App\Modules\User\Models\User;
use App\Modules\Venue\Enums\VenueStatus;
use App\Modules\Venue\Models\Venue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Dev/demo data — see DatabaseSeeder for why this only runs outside
 * `testing`. Generates a spread of bookings across every bookable field
 * (an ACTIVE field on an ACTIVE venue), past/today/upcoming, across both
 * booking sources (to exercise commission calculation — see
 * BookingService::calculatePrice()) and every BookingStatus/PaymentStatus
 * combination the app actually supports.
 *
 * Every slot is picked against the target field's real working hours (see
 * pickSlot()) and checked against every other booking already reserved for
 * that field/day in this run, so this can never produce two overlapping
 * non-hypothetical bookings — the same invariant the `bookings_no_overlap`
 * DB constraint enforces (see the bookings migration).
 *
 * Not idempotent field-by-field like the catalog seeders (a booking has no
 * natural unique key to updateOrCreate against — its identity *is* a
 * randomly generated time slot). Instead this simply refuses to run a
 * second time once any booking exists, so `php artisan db:seed` reruns
 * never pile up duplicate history; `migrate:fresh --seed` (the documented
 * way to regenerate demo data) always starts from an empty table anyway.
 */
class BookingSeeder extends Seeder
{
    private const TARGET_BOOKINGS = 90;

    private const PAST_DAYS_BACK = 45;

    private const FUTURE_DAYS_AHEAD = 21;

    /** @var array<string, float> */
    private const BUCKET_WEIGHTS = ['past' => 0.45, 'today' => 0.10, 'future' => 0.45];

    /** @var array<int, float> */
    private const DURATION_WEIGHTS = [1 => 0.55, 2 => 0.35, 3 => 0.10];

    /** @var array<string, float> */
    private const PAST_STATUS_WEIGHTS = [
        BookingStatus::COMPLETED->value => 0.70,
        BookingStatus::CANCELLED->value => 0.25,
        BookingStatus::CONFIRMED->value => 0.05,
    ];

    /** @var array<string, float> */
    private const FUTURE_STATUS_WEIGHTS = [
        BookingStatus::CONFIRMED->value => 0.55,
        BookingStatus::PENDING->value => 0.35,
        BookingStatus::CANCELLED->value => 0.10,
    ];

    /** @var array<string, float> */
    private const SOURCE_WEIGHTS = [
        BookingSource::CUSTOMER_APP->value => 0.65,
        BookingSource::ADMIN_PANEL->value => 0.35,
    ];

    /** @var array<int, string> */
    private const CANCELLATION_REASONS = [
        'Change of plans', 'Bad weather forecast', 'Team could not make it',
        'Found a closer venue', 'Player injury', 'Venue requested reschedule',
        'Duplicate booking made by mistake',
    ];

    /** @var array<int, string> */
    private const NOTES = [
        'Please prepare extra bibs.', 'Team of 8, arriving 10 minutes early.',
        'Corporate team-building event.', 'Birthday match — bringing a cake after.',
        'Regular Friday five-a-side group.', 'First time here, need directions to the entrance.',
        'Please reserve the north-side goals.', 'Bringing our own goalkeeper gloves.',
    ];

    /** @var array<string, array<int, string>> field_id => provider pool already used doesn't matter, just payment providers */
    private const PAYMENT_PROVIDERS = ['CARD', 'CASH', 'MOBILE'];

    /** @var array<int, array<int, array{0: int, 1: int}>> [field_id][date string] => list of [startHour, endHour) */
    private array $reserved = [];

    public function run(): void
    {
        if (Booking::query()->exists()) {
            $this->command?->info('Bookings already exist — skipping BookingSeeder.');

            return;
        }

        $fields = Field::query()
            ->with('venue.workingHours')
            ->where('status', FieldStatus::ACTIVE->value)
            ->whereHas('venue', fn ($query) => $query->where('status', VenueStatus::ACTIVE->value))
            ->get();

        $customerIds = User::role(UserRole::CUSTOMER)->pluck('id')->all();

        if ($fields->isEmpty() || empty($customerIds)) {
            $this->command?->warn('No bookable fields or customers found — run VenueSeeder/FieldSeeder/UserSeeder first. Skipping BookingSeeder.');

            return;
        }

        $managersByVenue = Venue::query()->with('managers')->get()
            ->mapWithKeys(fn (Venue $venue) => [$venue->id => $venue->managers->pluck('id')->all()]);

        $commissionRate = PlatformSetting::getCommissionRate();

        // Every bookable field gets at least one booking; the rest of the
        // target is distributed randomly across fields.
        $assignments = $fields->all();
        $remaining = max(0, self::TARGET_BOOKINGS - count($assignments));

        for ($i = 0; $i < $remaining; $i++) {
            $assignments[] = $fields->random();
        }

        shuffle($assignments);

        $created = 0;
        $paymentCounter = 0;

        foreach ($assignments as $field) {
            $bucket = $this->weightedPick(self::BUCKET_WEIGHTS);
            $duration = (int) $this->weightedPick(self::DURATION_WEIGHTS);

            $slot = $this->pickSlot($field, $bucket, $duration);

            if (! $slot) {
                continue; // No conflict-free slot found within a handful of attempts — skip this assignment.
            }

            [$start, $end] = $slot;

            $effectiveBucket = $start->lt(now()) ? 'past' : 'future';

            $status = BookingStatus::from($this->weightedPick(
                $effectiveBucket === 'past' ? self::PAST_STATUS_WEIGHTS : self::FUTURE_STATUS_WEIGHTS,
            ));

            $source = BookingSource::from($this->weightedPick(self::SOURCE_WEIGHTS));
            $userId = $customerIds[array_rand($customerIds)];

            $hourlyPrice = (float) $field->hourly_price;
            $totalPrice = round($hourlyPrice * $duration, 2);
            $rate = $source === BookingSource::CUSTOMER_APP ? $commissionRate : 0.0;
            $commissionAmount = round($totalPrice * $rate / 100, 2);
            $venueAmount = round($totalPrice - $commissionAmount, 2);

            $paymentStatus = $this->paymentStatusFor($status);
            $paymentCounter++;

            $attributes = [
                'user_id' => $userId,
                'field_id' => $field->id,
                'venue_id' => $field->venue_id,
                'start_time' => $start,
                'end_time' => $end,
                'duration_minutes' => $duration * 60,
                'hourly_price' => $hourlyPrice,
                'total_price' => $totalPrice,
                'commission_rate' => $rate,
                'commission_amount' => $commissionAmount,
                'venue_amount' => $venueAmount,
                'source' => $source,
                'status' => $status,
                'payment_status' => $paymentStatus,
                'payment_reference' => $paymentStatus !== PaymentStatus::PENDING
                    ? sprintf('PAY-%06d-%s', $paymentCounter, Str::upper(Str::random(4)))
                    : null,
                'payment_provider' => $paymentStatus !== PaymentStatus::PENDING
                    ? self::PAYMENT_PROVIDERS[array_rand(self::PAYMENT_PROVIDERS)]
                    : null,
                'notes' => random_int(1, 100) <= 30 ? self::NOTES[array_rand(self::NOTES)] : null,
            ];

            if ($status === BookingStatus::CANCELLED) {
                $managerIds = $managersByVenue[$field->venue_id] ?? [];
                $cancelledBy = (random_int(1, 100) <= 75 || empty($managerIds))
                    ? $userId
                    : $managerIds[array_rand($managerIds)];

                $candidate = $effectiveBucket === 'future'
                    ? Carbon::now()->subMinutes(random_int(30, 60 * 24 * 5))
                    : $start->copy()->addMinutes(random_int(-180, 120));

                $attributes['cancelled_at'] = $candidate->gt(now()) ? now() : $candidate;
                $attributes['cancellation_reason'] = self::CANCELLATION_REASONS[array_rand(self::CANCELLATION_REASONS)];
                $attributes['cancelled_by_user_id'] = $cancelledBy;
            }

            Booking::query()->create($attributes);
            $created++;
        }

        $this->command?->info("BookingSeeder: created {$created} bookings.");
    }

    /**
     * Finds a conflict-free, working-hours-respecting slot for $field in
     * the requested temporal $bucket, reserving it in $this->reserved so no
     * later assignment in this run can double-book it. Never checks against
     * bookings from a previous run (BookingSeeder refuses to run at all if
     * any booking already exists — see run()).
     *
     * @return array{0: Carbon, 1: Carbon}|null
     */
    private function pickSlot(Field $field, string $bucket, int $durationHours): ?array
    {
        for ($attempt = 0; $attempt < 25; $attempt++) {
            $date = match ($bucket) {
                'past' => Carbon::today()->subDays(random_int(1, self::PAST_DAYS_BACK)),
                'today' => Carbon::today(),
                'future' => Carbon::today()->addDays(random_int(1, self::FUTURE_DAYS_AHEAD)),
            };

            $workingHours = $field->venue->workingHoursFor($date->dayOfWeek);

            if (! $workingHours || $workingHours->is_closed) {
                continue;
            }

            $openHour = (int) substr($workingHours->opens_at, 0, 2);
            $closeHour = (int) substr($workingHours->closes_at, 0, 2);
            $latestStart = $closeHour - $durationHours;

            if ($latestStart < $openHour) {
                continue;
            }

            $startHour = random_int($openHour, $latestStart);
            $endHour = $startHour + $durationHours;

            $dateKey = $date->toDateString();
            $existing = $this->reserved[$field->id][$dateKey] ?? [];

            $conflict = false;

            foreach ($existing as [$reservedStart, $reservedEnd]) {
                if ($startHour < $reservedEnd && $endHour > $reservedStart) {
                    $conflict = true;
                    break;
                }
            }

            if ($conflict) {
                continue;
            }

            $this->reserved[$field->id][$dateKey][] = [$startHour, $endHour];

            $start = $date->copy()->setTime($startHour, 0, 0);

            return [$start, $start->copy()->addHours($durationHours)];
        }

        return null;
    }

    private function paymentStatusFor(BookingStatus $status): PaymentStatus
    {
        $weights = match ($status) {
            BookingStatus::COMPLETED => [
                PaymentStatus::PAID->value => 0.90,
                PaymentStatus::REFUNDED->value => 0.05,
                PaymentStatus::FAILED->value => 0.05,
            ],
            BookingStatus::CANCELLED => [
                PaymentStatus::REFUNDED->value => 0.55,
                PaymentStatus::PENDING->value => 0.30,
                PaymentStatus::FAILED->value => 0.15,
            ],
            BookingStatus::CONFIRMED => [
                PaymentStatus::PAID->value => 0.70,
                PaymentStatus::PENDING->value => 0.30,
            ],
            BookingStatus::PENDING => [
                PaymentStatus::PENDING->value => 0.90,
                PaymentStatus::FAILED->value => 0.10,
            ],
        };

        return PaymentStatus::from($this->weightedPick($weights));
    }

    /** @param array<string, float> $weights */
    private function weightedPick(array $weights): string
    {
        $total = array_sum($weights);
        $random = (mt_rand() / mt_getrandmax()) * $total;
        $cumulative = 0.0;

        foreach ($weights as $key => $weight) {
            $cumulative += $weight;

            if ($random <= $cumulative) {
                return (string) $key;
            }
        }

        return (string) array_key_last($weights);
    }
}
