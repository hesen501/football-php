<?php

namespace App\Modules\Field\Http\Resources;

use App\Modules\Venue\Http\Resources\VenuePublicResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The public/customer-facing counterpart of FieldResource — no `status`
 * (every field reachable through this resource is already ACTIVE), no
 * timestamps. See FieldController (Customer) for the ACTIVE-only query.
 */
class FieldPublicResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'venue_id' => $this->venue_id,
            'venue' => $this->whenLoaded('venue', fn () => VenuePublicResource::make($this->venue)),
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'capacity' => $this->capacity,
            'hourly_price' => $this->hourly_price,
        ];
    }
}
