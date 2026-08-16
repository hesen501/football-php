<?php

namespace App\Modules\User\Http\Resources;

use App\Modules\Media\Http\Resources\MediaResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Explicit allow-list of fields, independent of the model's $hidden array —
 * defense in depth so password/remember_token can never leak here even if
 * the model's own protection is ever loosened.
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status,
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')),
            'avatar' => $this->whenLoaded('avatarMedia', fn () => $this->avatarMedia ? MediaResource::make($this->avatarMedia) : null),
            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
