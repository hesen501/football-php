<?php

namespace App\Modules\Venue\Http\Resources;

use App\Modules\Media\Http\Resources\MediaResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The public/customer-facing counterpart of VenueResource — deliberately
 * leaner: no `managers` (internal user records, PII), no `status` (every
 * venue reachable through this resource is already ACTIVE by construction),
 * no timestamps. See VenueController (Customer) for the ACTIVE-only query.
 */
class VenuePublicResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'address' => $this->address,
            'city' => $this->city,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'phone' => $this->phone,
            'email' => $this->email,
            // Empty for a venue with no configured hours (see BookingService)
            // — treated as bookable any hour, not as "closed every day".
            'working_hours' => $this->whenLoaded(
                'workingHours',
                fn () => VenueWorkingHourResource::collection($this->workingHours),
            ),
            'cover_image' => $this->whenLoaded('coverMedia', fn () => $this->coverMedia ? MediaResource::make($this->coverMedia) : null),
            'images' => $this->whenLoaded('media', fn () => MediaResource::collection($this->media)),
        ];
    }
}
