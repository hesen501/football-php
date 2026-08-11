<?php

namespace App\Modules\Booking\Http\Resources;

use App\Modules\Field\Http\Resources\FieldResource;
use App\Modules\User\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => $this->whenLoaded('user', fn () => UserResource::make($this->user)),
            'field' => $this->whenLoaded('field', fn () => FieldResource::make($this->field)),
            'venue_id' => $this->venue_id,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'duration_minutes' => $this->duration_minutes,
            'hourly_price' => $this->hourly_price,
            // base_price: the field-booking amount alone (bookings.total_price,
            // unchanged). total_price below is base_price + items_total — see
            // Booking::grandTotal(). commission/venue_amount are computed from
            // the base amount only; items don't currently generate commission.
            'base_price' => $this->total_price,
            'items' => BookingItemResource::collection($this->bookingItems),
            'items_total' => number_format($this->itemsTotal(), 2, '.', ''),
            'total_price' => number_format($this->grandTotal(), 2, '.', ''),
            'commission_rate' => $this->commission_rate,
            'commission_amount' => $this->commission_amount,
            'venue_amount' => $this->venue_amount,
            'source' => $this->source,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'payment_reference' => $this->payment_reference,
            'cancelled_at' => $this->cancelled_at,
            'cancellation_reason' => $this->cancellation_reason,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
