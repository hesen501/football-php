<?php

namespace App\Modules\Item\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Modules\Item\Enums\ItemStatus;
use App\Modules\Item\Http\Requests\Customer\ListPublicItemsRequest;
use App\Modules\Item\Http\Resources\ItemPublicResource;
use App\Modules\Item\Models\Item;
use App\Shared\Http\Filtering\QueryParams;

class ItemController extends Controller
{
    /**
     * The catalog of add-ons a booking can currently have added to it —
     * inactive items are never listed here (see ItemPolicy's docblock for
     * why this needs no permission check at all).
     */
    public function index(ListPublicItemsRequest $request)
    {
        $params = QueryParams::fromRequest($request);

        $items = Item::query()
            ->where('status', ItemStatus::ACTIVE->value)
            ->applySearch($params, ['name'])
            ->applySort($params, ['name', 'price'], 'name')
            ->paginate($params->perPage, page: $params->page);

        return ItemPublicResource::collection($items);
    }
}
