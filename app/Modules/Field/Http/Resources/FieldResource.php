<?php

namespace App\Modules\Field\Http\Resources;

use App\Modules\Media\Http\Resources\MediaResource;
use App\Modules\Venue\Http\Resources\VenueResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FieldResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'venue_id' => $this->venue_id,
            'venue' => $this->whenLoaded(
                'venue',
                fn () => VenueResource::make($this->venue)
            ),
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'capacity' => $this->capacity,
            'hourly_price' => $this->hourly_price,
            'status' => $this->status,
            'cover_image' => $this->whenLoaded(
                'coverMedia',
                fn () => $this->coverMedia ? MediaResource::make($this->coverMedia) : null
            ),
            'images' => $this->whenLoaded(
                'media',
                fn () => MediaResource::collection($this->media)
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
