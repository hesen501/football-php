<?php

namespace App\Modules\Booking\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Booking\DTOs\CreateBookingData;
use App\Modules\Booking\Enums\BookingSource;
use App\Modules\Booking\Http\Requests\CancelBookingRequest;
use App\Modules\Booking\Http\Requests\ListBookingsRequest;
use App\Modules\Booking\Http\Requests\StoreBookingRequest;
use App\Modules\Booking\Http\Resources\BookingResource;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Services\BookingService;
use App\Shared\Http\Filtering\QueryParams;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;

class BookingController extends Controller
{
    public function __construct(private readonly BookingService $bookings) {}

    public function index(ListBookingsRequest $request)
    {
        $params = QueryParams::fromRequest($request);
        $paginated = $this->bookings->list($request->user(), $params, $request->only([
            'status', 'payment_status', 'venue_id', 'field_id', 'user_id', 'date_from', 'date_to',
        ]));

        return BookingResource::collection($paginated);
    }

    public function store(StoreBookingRequest $request)
    {
        $validated = $request->validated();

        $data = new CreateBookingData(
            userId: (int) $validated['user_id'],
            fieldId: (int) $validated['field_id'],
            startTime: CarbonImmutable::parse($validated['start_time']),
            durationHours: (int) $validated['duration_hours'],
            source: BookingSource::ADMIN_PANEL,
            notes: $validated['notes'] ?? null,
        );

        $booking = $this->bookings->create($data);

        return BookingResource::make($booking)->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Booking $booking)
    {
        $this->authorize('view', $booking);

        return BookingResource::make($booking->load(['user', 'field.venue']));
    }

    public function confirm(Booking $booking)
    {
        $this->authorize('update', $booking);

        $booking = $this->bookings->confirm($booking);

        return BookingResource::make($booking);
    }

    public function cancel(CancelBookingRequest $request, Booking $booking)
    {
        $booking = $this->bookings->cancel($booking, $request->user(), $request->input('reason'));

        return BookingResource::make($booking);
    }
}
