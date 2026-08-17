<?php

namespace App\Modules\Item\Http\Resources;

use App\Modules\Media\Http\Resources\MediaResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'status' => $this->status,
            'image' => $this->whenLoaded('imageMedia', fn () => $this->imageMedia ? MediaResource::make($this->imageMedia) : null),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
