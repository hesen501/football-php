<?php

namespace App\Modules\Item\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The public/customer-facing counterpart of ItemResource — no `status`
 * (every item reachable through this resource is already ACTIVE by
 * construction) and no timestamps. See ItemController (Customer) for the
 * ACTIVE-only query.
 */
class ItemPublicResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
        ];
    }
}
