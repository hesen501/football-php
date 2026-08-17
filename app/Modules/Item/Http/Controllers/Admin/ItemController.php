<?php

namespace App\Modules\Item\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Item\Http\Requests\ListItemsRequest;
use App\Modules\Item\Http\Requests\StoreItemRequest;
use App\Modules\Item\Http\Requests\UpdateItemRequest;
use App\Modules\Item\Http\Resources\ItemResource;
use App\Modules\Item\Models\Item;
use App\Modules\Item\Services\ItemService;
use App\Shared\Http\Filtering\QueryParams;
use Illuminate\Http\Response;

class ItemController extends Controller
{
    public function __construct(private readonly ItemService $items) {}

    public function index(ListItemsRequest $request)
    {
        $params = QueryParams::fromRequest($request);
        $paginated = $this->items->list($params, $request->only(['status']));

        return ItemResource::collection($paginated);
    }

    public function store(StoreItemRequest $request)
    {
        $item = $this->items->create($request->validated());

        return ItemResource::make($item)->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Item $item)
    {
        $this->authorize('view', $item);

        return ItemResource::make($item->load('imageMedia'));
    }

    public function update(UpdateItemRequest $request, Item $item)
    {
        $item = $this->items->update($item, $request->validated());

        return ItemResource::make($item);
    }

    public function destroy(Item $item)
    {
        $this->authorize('delete', $item);

        $this->items->delete($item);

        return response()->noContent();
    }
}
