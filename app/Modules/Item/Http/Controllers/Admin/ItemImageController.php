<?php

namespace App\Modules\Item\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Item\Http\Requests\StoreItemImageRequest;
use App\Modules\Item\Models\Item;
use App\Modules\Media\Enums\MediaCollection;
use App\Modules\Media\Http\Resources\MediaResource;
use App\Modules\Media\Services\MediaService;
use Illuminate\Http\Response;

/**
 * An item only ever has one IMAGE — no gallery, no cover concept (see
 * MediaCollection::isSingleton()). Uploading again replaces it; MediaService::
 * upload() handles deleting the previous row/file before storing the new one.
 */
class ItemImageController extends Controller
{
    public function __construct(private readonly MediaService $media) {}

    public function store(StoreItemImageRequest $request, Item $item)
    {
        $media = $this->media->upload($item, $request->file('image'), MediaCollection::IMAGE);

        return MediaResource::make($media)->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function destroy(Item $item)
    {
        $this->authorize('update', $item);

        $this->media->deleteAllInCollection($item, MediaCollection::IMAGE);

        return response()->noContent();
    }
}
