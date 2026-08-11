<?php

namespace App\Modules\Booking\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Booking\Http\Requests\AddBookingItemRequest;
use App\Modules\Booking\Http\Resources\BookingResource;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Services\BookingService;
use App\Modules\Item\Models\Item;
use Illuminate\Http\Response;

class BookingItemController extends Controller
{
    public function __construct(private readonly BookingService $bookings) {}

    public function store(AddBookingItemRequest $request, Booking $booking)
    {
        $booking = $this->bookings->addItem($booking, $request->integer('item_id'));

        return BookingResource::make($booking)->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function destroy(Booking $booking, Item $item)
    {
        $this->authorize('manageItems', $booking);

        $booking = $this->bookings->removeItem($booking, $item);

        return BookingResource::make($booking);
    }
}
