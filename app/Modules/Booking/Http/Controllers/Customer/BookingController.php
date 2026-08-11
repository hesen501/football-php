<?php

namespace App\Modules\Booking\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Modules\Booking\DTOs\CreateBookingData;
use App\Modules\Booking\Enums\BookingSource;
use App\Modules\Booking\Http\Requests\CancelBookingRequest;
use App\Modules\Booking\Http\Requests\Customer\ListBookingsRequest;
use App\Modules\Booking\Http\Requests\Customer\StoreBookingRequest;
use App\Modules\Booking\Http\Resources\CustomerBookingResource;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Services\BookingService;
use App\Shared\Exceptions\BusinessRuleException;
use App\Shared\Http\Filtering\QueryParams;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;

class BookingController extends Controller
{
    public function __construct(private readonly BookingService $bookings) {}

    public function index(ListBookingsRequest $request)
    {
        $params = QueryParams::fromRequest($request);
        $paginated = $this->bookings->listForCustomer(
            $request->user(),
            $params,
            $request->only(['status', 'payment_status']),
        );

        return CustomerBookingResource::collection($paginated);
    }

    public function store(StoreBookingRequest $request)
    {
        if (! $request->user()->hasVerifiedEmail()) {
            throw new BusinessRuleException(
                'Please verify your email before making a booking.',
                'EMAIL_NOT_VERIFIED',
            );
        }

        $validated = $request->validated();

        $data = new CreateBookingData(
            userId: $request->user()->id,
            fieldId: (int) $validated['field_id'],
            startTime: CarbonImmutable::parse($validated['start_time']),
            durationHours: (int) $validated['duration_hours'],
            source: BookingSource::CUSTOMER_APP,
            notes: $validated['notes'] ?? null,
        );

        $booking = $this->bookings->create($data);

        return CustomerBookingResource::make($booking)->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Booking $booking)
    {
        $this->authorize('view', $booking);

        return CustomerBookingResource::make($booking->load('field.venue'));
    }

    public function cancel(CancelBookingRequest $request, Booking $booking)
    {
        $booking = $this->bookings->cancel($booking, $request->user(), $request->input('reason'));

        return CustomerBookingResource::make($booking);
    }
}
