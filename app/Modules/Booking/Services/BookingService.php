<?php

namespace App\Modules\Booking\Services;

use App\Modules\Booking\DTOs\BookingPriceBreakdown;
use App\Modules\Booking\DTOs\CreateBookingData;
use App\Modules\Booking\Enums\BookingSource;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Enums\PaymentStatus;
use App\Modules\Booking\Exceptions\BookingSlotUnavailableException;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\PlatformSetting;
use App\Modules\Field\Enums\FieldStatus;
use App\Modules\Field\Models\Field;
use App\Modules\User\Models\User;
use App\Modules\Venue\Enums\VenueStatus;
use App\Shared\Exceptions\BusinessRuleException;
use App\Shared\Http\Filtering\QueryParams;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class BookingService
{
    /** @param array{status?: string, payment_status?: string, venue_id?: int, field_id?: int, user_id?: int, date_from?: string, date_to?: string} $filters */
    public function list(User $actor, QueryParams $params, array $filters): LengthAwarePaginator
    {
        return Booking::query()
            ->with(['user', 'field', 'venue'])
            // A VENUE_MANAGER only sees bookings for venues they manage;
            // SUPER_ADMIN (the only other role reaching this method) sees all.
            ->when(! $actor->hasRole('SUPER_ADMIN'), fn ($query) => $query->whereHas(
                'venue.managers',
                fn ($managers) => $managers->whereKey($actor->id),
            ))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['payment_status'] ?? null, fn ($q, $v) => $q->where('payment_status', $v))
            ->when($filters['venue_id'] ?? null, fn ($q, $v) => $q->where('venue_id', $v))
            ->when($filters['field_id'] ?? null, fn ($q, $v) => $q->where('field_id', $v))
            ->when($filters['user_id'] ?? null, fn ($q, $v) => $q->where('user_id', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('start_time', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('start_time', '<=', $v))
            ->applySort($params, ['start_time', 'created_at', 'total_price'], '-start_time')
            ->paginate($params->perPage, page: $params->page);
    }

    /** @param array{status?: string, payment_status?: string} $filters */
    public function listForCustomer(User $customer, QueryParams $params, array $filters): LengthAwarePaginator
    {
        return Booking::query()
            ->with(['field.venue'])
            ->where('user_id', $customer->id)
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['payment_status'] ?? null, fn ($q, $v) => $q->where('payment_status', $v))
            ->applySort($params, ['start_time', 'created_at'], '-start_time')
            ->paginate($params->perPage, page: $params->page);
    }

    public function create(CreateBookingData $data): Booking
    {
        $field = Field::query()->with('venue')->findOrFail($data->fieldId);

        $this->assertFieldBookable($field);

        $startTime = $data->startTime;
        $endTime = $startTime->addHours($data->durationHours);

        return DB::transaction(function () use ($data, $field, $startTime, $endTime) {
            // Serializes concurrent booking attempts on the *same field*
            // (cheap — doesn't lock unrelated fields); auto-released at
            // transaction end regardless of commit or rollback.
            DB::select('SELECT pg_advisory_xact_lock(?)', [(int) $field->id]);

            // Fast, friendly failure path for the common case.
            if ($this->hasOverlap($field->id, $startTime, $endTime)) {
                throw new BookingSlotUnavailableException;
            }

            $pricing = $this->calculatePrice($field, $data->durationHours, $data->source);

            try {
                return Booking::query()->create([
                    'user_id' => $data->userId,
                    'field_id' => $field->id,
                    'venue_id' => $field->venue_id,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'duration_minutes' => $data->durationHours * 60,
                    'hourly_price' => $pricing->hourlyPrice,
                    'total_price' => $pricing->totalPrice,
                    'commission_rate' => $pricing->commissionRate,
                    'commission_amount' => $pricing->commissionAmount,
                    'venue_amount' => $pricing->venueAmount,
                    'source' => $data->source,
                    'status' => $data->source === BookingSource::ADMIN_PANEL
                        ? BookingStatus::CONFIRMED->value
                        : BookingStatus::PENDING->value,
                    'payment_status' => PaymentStatus::PENDING->value,
                    'notes' => $data->notes,
                ])->load(['user', 'field.venue']);
            } catch (QueryException $e) {
                // Last-resort guarantee: even if the advisory lock above was
                // somehow bypassed, the DB EXCLUDE constraint (see the
                // bookings migration) makes an overlapping insert impossible
                // — this just turns that into our normal domain error instead
                // of a raw 500.
                if ($this->isOverlapViolation($e)) {
                    throw new BookingSlotUnavailableException;
                }

                throw $e;
            }
        });
    }

    public function confirm(Booking $booking): Booking
    {
        if ($booking->status !== BookingStatus::PENDING) {
            throw new BusinessRuleException('Only pending bookings can be confirmed.', 'BOOKING_NOT_PENDING');
        }

        $booking->update(['status' => BookingStatus::CONFIRMED->value]);

        return $booking->fresh(['user', 'field.venue']);
    }

    public function cancel(Booking $booking, User $actor, ?string $reason = null): Booking
    {
        if (! $booking->isCancellable()) {
            throw new BusinessRuleException('This booking can no longer be cancelled.', 'BOOKING_NOT_CANCELLABLE');
        }

        $booking->update([
            'status' => BookingStatus::CANCELLED->value,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'cancelled_by_user_id' => $actor->id,
        ]);

        return $booking->fresh(['user', 'field.venue']);
    }

    public function calculatePrice(Field $field, int $durationHours, BookingSource $source): BookingPriceBreakdown
    {
        $hourlyPrice = (float) $field->hourly_price;
        $totalPrice = round($hourlyPrice * $durationHours, 2);

        // Commission only applies to bookings made through the customer
        // app — admin-panel bookings never generate platform commission.
        $commissionRate = $source === BookingSource::CUSTOMER_APP
            ? PlatformSetting::getCommissionRate()
            : 0.0;
        $commissionAmount = round($totalPrice * $commissionRate / 100, 2);
        $venueAmount = round($totalPrice - $commissionAmount, 2);

        return new BookingPriceBreakdown($hourlyPrice, $totalPrice, $commissionRate, $commissionAmount, $venueAmount);
    }

    /**
     * Hourly availability for a field on a given date — every hour of the
     * day is returned (no configurable "business hours" concept exists yet),
     * each flagged available/booked.
     *
     * @return array<int, array{start_time: CarbonImmutable, end_time: CarbonImmutable, available: bool}>
     */
    public function availability(Field $field, CarbonImmutable $date): array
    {
        $dayStart = $date->startOfDay();

        $bookings = Booking::query()
            ->where('field_id', $field->id)
            ->where('status', '!=', BookingStatus::CANCELLED->value)
            ->whereBetween('start_time', [$dayStart, $dayStart->addDay()])
            ->get(['start_time', 'end_time']);

        $slots = [];

        for ($hour = 0; $hour < 24; $hour++) {
            $slotStart = $dayStart->addHours($hour);
            $slotEnd = $slotStart->addHour();

            $isBooked = $bookings->contains(
                fn (Booking $booking) => $booking->start_time->lt($slotEnd) && $booking->end_time->gt($slotStart)
            );

            $slots[] = [
                'start_time' => $slotStart,
                'end_time' => $slotEnd,
                'available' => ! $isBooked,
            ];
        }

        return $slots;
    }

    private function assertFieldBookable(Field $field): void
    {
        if ($field->status !== FieldStatus::ACTIVE) {
            throw new BusinessRuleException('This field is not available for booking.', 'FIELD_NOT_BOOKABLE');
        }

        if ($field->venue->status !== VenueStatus::ACTIVE) {
            throw new BusinessRuleException('This venue is not currently active.', 'VENUE_NOT_ACTIVE');
        }
    }

    private function hasOverlap(int $fieldId, CarbonImmutable $start, CarbonImmutable $end): bool
    {
        return Booking::query()
            ->where('field_id', $fieldId)
            ->where('status', '!=', BookingStatus::CANCELLED->value)
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->exists();
    }

    private function isOverlapViolation(QueryException $e): bool
    {
        return $e->getCode() === '23P01' || str_contains($e->getMessage(), 'bookings_no_overlap');
    }
}
