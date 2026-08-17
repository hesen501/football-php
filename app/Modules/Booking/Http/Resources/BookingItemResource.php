<?php

namespace App\Modules\Booking\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One line item on a booking. `id`/`name` here are the catalog Item's, not
 * this BookingItem row's own id — matches the id used in the add/remove
 * endpoints (POST body's item_id, DELETE's {item} route param).
 */
class BookingItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->item_id,
            'name' => $this->item->name,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'total_price' => $this->total_price,
        ];
    }
}
