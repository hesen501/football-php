<?php

namespace App\Modules\Venue\Http\Resources;

use App\Modules\Media\Http\Resources\MediaResource;
use App\Modules\User\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VenueResource extends JsonResource
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
            'status' => $this->status,
            'managers' => $this->whenLoaded('managers', fn () => UserResource::collection($this->managers)),
            'working_hours' => $this->whenLoaded(
                'workingHours',
                fn () => VenueWorkingHourResource::collection($this->workingHours),
            ),
            // 'images' is every photo (cover included); 'cover_image' is
            // just a convenience pointer at whichever one of those is
            // currently the cover — see App\Shared\Concerns\HasMedia.
            'cover_image' => $this->whenLoaded('coverMedia', fn () => $this->coverMedia ? MediaResource::make($this->coverMedia) : null),
            'images' => $this->whenLoaded('media', fn () => MediaResource::collection($this->media)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
