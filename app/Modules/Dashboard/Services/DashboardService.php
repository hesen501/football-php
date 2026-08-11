<?php

namespace App\Modules\Dashboard\Services;

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Field\Models\Field;
use App\Modules\User\Models\User;
use App\Modules\Venue\Models\Venue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * No model of its own — pure read-only aggregation over Venue/Field/Booking
 * (and User, for SUPER_ADMIN only). A VENUE_MANAGER gets the same shape back
 * but scoped to venues they manage; "total_users" is platform-wide-only and
 * omitted (null) for managers, since user administration isn't their concern.
 */
class DashboardService
{
    public function stats(User $actor): array
    {
        $isSuperAdmin = $actor->hasRole('SUPER_ADMIN');
        $venueIds = $isSuperAdmin ? null : $actor->managedVenues()->pluck('venues.id')->all();

        $bookingStatusCounts = DB::table('bookings')
            ->when($venueIds !== null, fn ($query) => $query->whereIn('venue_id', $venueIds))
            ->select('status', DB::raw('count(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $revenue = DB::table('bookings')
            ->when($venueIds !== null, fn ($query) => $query->whereIn('venue_id', $venueIds))
            ->whereIn('status', [BookingStatus::CONFIRMED->value, BookingStatus::COMPLETED->value])
            ->selectRaw('COALESCE(SUM(total_price), 0) as total_revenue')
            ->selectRaw('COALESCE(SUM(commission_amount), 0) as total_commission')
            ->selectRaw('COALESCE(SUM(venue_amount), 0) as total_venue_amount')
            ->first();

        return [
            'total_users' => $isSuperAdmin ? User::query()->count() : null,
            'total_venues' => $isSuperAdmin ? Venue::query()->count() : count($venueIds),
            'total_fields' => Field::query()
                ->when($venueIds !== null, fn ($query) => $query->whereIn('venue_id', $venueIds))
                ->count(),
            'total_bookings' => DB::table('bookings')
                ->when($venueIds !== null, fn ($query) => $query->whereIn('venue_id', $venueIds))
                ->count(),
            'total_revenue' => (float) $revenue->total_revenue,
            'total_commission' => (float) $revenue->total_commission,
            'total_venue_amount' => (float) $revenue->total_venue_amount,
            'booking_status_breakdown' => collect(BookingStatus::cases())
                ->mapWithKeys(fn (BookingStatus $status) => [
                    $status->value => (int) ($bookingStatusCounts[$status->value] ?? 0),
                ])
                ->all(),
        ];
    }

    public function recentBookings(User $actor, int $limit = 10): Collection
    {
        $isSuperAdmin = $actor->hasRole('SUPER_ADMIN');

        return Booking::query()
            ->with(['user', 'field', 'venue'])
            ->when(! $isSuperAdmin, fn ($query) => $query->whereHas(
                'venue.managers',
                fn ($managers) => $managers->whereKey($actor->id),
            ))
            ->latest('created_at')
            ->limit(max(1, min($limit, 50)))
            ->get();
    }
}
