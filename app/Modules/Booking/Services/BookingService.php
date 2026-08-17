<?php

namespace App\Modules\Booking\Services;

use App\Modules\Booking\DTOs\BookingPriceBreakdown;
use App\Modules\Booking\DTOs\CreateBookingData;
use App\Modules\Booking\Enums\BookingSource;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Enums\PaymentStatus;
use App\Modules\Booking\Exceptions\BookingSlotUnavailableException;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\BookingItem;
use App\Modules\Booking\Models\PlatformSetting;
use App\Modules\Field\Enums\FieldStatus;
use App\Modules\Field\Models\Field;
use App\Modules\Item\Enums\ItemStatus;
use App\Modules\Item\Models\Item;
use App\Modules\User\Models\User;
use App\Modules\Venue\Enums\VenueStatus;
use App\Modules\Venue\Models\Venue;
use App\Modules\Venue\Models\VenueWorkingHour;
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
            ->with(['user', 'field', 'venue', 'bookingItems.item'])
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
            ->with(['field.venue', 'bookingItems.item'])
            ->where('user_id', $customer->id)
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['payment_status'] ?? null, fn ($q, $v) => $q->where('payment_status', $v))
            ->applySort($params, ['start_time', 'created_at'], '-start_time')
            ->paginate($params->perPage, page: $params->page);
    }

    public function create(CreateBookingData $data): Booking
    {
        $field = Field::query()->with('venue.workingHours')->findOrFail($data->fieldId);

        $this->assertFieldBookable($field);

        $startTime = $data->startTime;
        $endTime = $startTime->addHours($data->durationHours);

        $this->assertWithinWorkingHours($field->venue, $startTime, $endTime);

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
                ])->load(['user', 'field.venue', 'bookingItems.item']);
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

        return $booking->fresh(['user', 'field.venue', 'bookingItems.item']);
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

        return $booking->fresh(['user', 'field.venue', 'bookingItems.item']);
    }

    /**
     * "Each request represents one item being taken" — increments quantity
     * by 1 if this booking/item pair already has a row, otherwise creates
     * one at quantity 1. unit_price is captured from the item's *current*
     * price only on that first insert; every later increment reuses the
     * already-stored unit_price, never re-reading items.price — that's the
     * whole point of the snapshot (see the booking_items migration).
     *
     * Concurrency: lockForUpdate() on the booking row itself serializes
     * every item mutation for this booking behind one lock (simpler than a
     * per-item advisory lock, and correct since two concurrent "add" calls
     * for the same new item would otherwise both see "no row yet" and both
     * try to insert at quantity 1 instead of ending at 2). The DB's
     * UNIQUE(booking_id, item_id) constraint is defense in depth on top of
     * that, mirroring hasOverlap()/the bookings EXCLUDE constraint.
     */
    public function addItem(Booking $booking, int $itemId): Booking
    {
        return DB::transaction(function () use ($booking, $itemId) {
            $booking = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if (! $booking->canModifyItems()) {
                throw new BusinessRuleException('This booking can no longer be modified.', 'BOOKING_NOT_MODIFIABLE');
            }

            $item = Item::query()->findOrFail($itemId);

            if ($item->status !== ItemStatus::ACTIVE) {
                throw new BusinessRuleException('This item is not available.', 'ITEM_NOT_ACTIVE');
            }

            $bookingItem = BookingItem::query()
                ->where('booking_id', $booking->id)
                ->where('item_id', $item->id)
                ->first();

            if ($bookingItem) {
                $newQuantity = $bookingItem->quantity + 1;

                $bookingItem->update([
                    'quantity' => $newQuantity,
                    'total_price' => round($newQuantity * (float) $bookingItem->unit_price, 2),
                ]);
            } else {
                BookingItem::query()->create([
                    'booking_id' => $booking->id,
                    'item_id' => $item->id,
                    'quantity' => 1,
                    'unit_price' => $item->price,
                    'total_price' => $item->price,
                ]);
            }

            return $booking->fresh(['user', 'field.venue', 'bookingItems.item']);
        });
    }

    /**
     * Removes one unit; deletes the booking_items row entirely once
     * quantity would reach 0. Same booking-row lock as addItem() — see
     * there for why.
     */
    public function removeItem(Booking $booking, Item $item): Booking
    {
        return DB::transaction(function () use ($booking, $item) {
            $booking = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if (! $booking->canModifyItems()) {
                throw new BusinessRuleException('This booking can no longer be modified.', 'BOOKING_NOT_MODIFIABLE');
            }

            $bookingItem = BookingItem::query()
                ->where('booking_id', $booking->id)
                ->where('item_id', $item->id)
                ->firstOrFail();

            if ($bookingItem->quantity <= 1) {
                $bookingItem->delete();
            } else {
                $newQuantity = $bookingItem->quantity - 1;

                $bookingItem->update([
                    'quantity' => $newQuantity,
                    'total_price' => round($newQuantity * (float) $bookingItem->unit_price, 2),
                ]);
            }

            return $booking->fresh(['user', 'field.venue', 'bookingItems.item']);
        });
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
     * day is returned, each flagged available/booked. An hour outside the
     * venue's working hours for that day of the week (see
     * VenueWorkingHour) comes back unavailable too, same as an hour that's
     * already booked — either way it can't be booked.
     *
     * @return array<int, array{start_time: CarbonImmutable, end_time: CarbonImmutable, available: bool}>
     */
    public function availability(Field $field, CarbonImmutable $date): array
    {
        $field->loadMissing('venue.workingHours');

        $dayStart = $date->startOfDay();
        $workingHours = $field->venue->workingHoursFor($dayStart->dayOfWeek);

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
                'available' => ! $isBooked && $this->isWithinWorkingHours($field->venue, $workingHours, $slotStart, $slotEnd),
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

    /**
     * Rejects a booking that starts before the venue opens, ends after it
     * closes, or falls on a day of the week the venue marked closed — this
     * is what stops a field from being bookable at midnight (or any other
     * hour outside a venue's chosen window). A venue with no working-hours
     * rows at all (see Venue::hasConfiguredWorkingHours()) is treated as
     * unrestricted, for backward compatibility with venues that predate
     * this feature or were written directly rather than via VenueService.
     */
    private function assertWithinWorkingHours(Venue $venue, CarbonImmutable $start, CarbonImmutable $end): void
    {
        if (! $venue->hasConfiguredWorkingHours()) {
            return;
        }

        $hours = $venue->workingHoursFor($start->dayOfWeek);

        if (! $this->isWithinWorkingHours($venue, $hours, $start, $end)) {
            throw new BusinessRuleException(
                $hours && ! $hours->is_closed
                    ? sprintf(
                        'This venue is only bookable between %s and %s on this day.',
                        substr($hours->opens_at, 0, 5),
                        substr($hours->closes_at, 0, 5),
                    )
                    : 'This venue is closed on the selected day.',
                'OUTSIDE_WORKING_HOURS',
            );
        }
    }

    /**
     * Whole booking must fit inside a single day's window — a start/end
     * that would straddle midnight into the next day's own (possibly
     * different, possibly closed) hours is rejected rather than partially
     * validated against two different rows.
     */
    private function isWithinWorkingHours(Venue $venue, ?VenueWorkingHour $hours, CarbonImmutable $start, CarbonImmutable $end): bool
    {
        if (! $venue->hasConfiguredWorkingHours()) {
            return true;
        }

        if (! $hours || $hours->is_closed) {
            return false;
        }

        $opensAt = $start->setTimeFromTimeString($hours->opens_at);
        $closesAt = $start->setTimeFromTimeString($hours->closes_at);

        return $start->gte($opensAt) && $end->lte($closesAt);
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
