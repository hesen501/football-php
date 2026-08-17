<?php

namespace App\Modules\Booking\Http\Resources;

use App\Modules\Field\Http\Resources\FieldPublicResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The customer-facing counterpart of BookingResource — deliberately omits
 * commission_rate/commission_amount/venue_amount. A customer cares what
 * *they* paid (total_price); the platform's revenue split with the venue is
 * an internal business detail, not something to expose to end users. Reuses
 * FieldPublicResource for the nested field so no venue.managers/status leaks
 * through either.
 */
class CustomerBookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'field' => $this->whenLoaded('field', fn () => FieldPublicResource::make($this->field)),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'duration_minutes' => $this->duration_minutes,
            'hourly_price' => $this->hourly_price,
            'base_price' => $this->total_price,
            'items' => BookingItemResource::collection($this->bookingItems),
            'items_total' => number_format($this->itemsTotal(), 2, '.', ''),
            'total_price' => number_format($this->grandTotal(), 2, '.', ''),
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'payment_reference' => $this->payment_reference,
            'cancelled_at' => $this->cancelled_at,
            'cancellation_reason' => $this->cancellation_reason,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
        ];
    }
}
