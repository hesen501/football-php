<?php

namespace App\Modules\Venue\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Media\Enums\MediaCollection;
use App\Modules\Media\Http\Resources\MediaResource;
use App\Modules\Media\Models\Media;
use App\Modules\Media\Services\MediaService;
use App\Modules\Venue\Http\Requests\ReorderVenueImagesRequest;
use App\Modules\Venue\Http\Requests\StoreVenueImageRequest;
use App\Modules\Venue\Models\Venue;
use Illuminate\Http\Response;

/**
 * A venue can have any number of GALLERY images plus at most one COVER
 * image (see MediaCollection) — every upload lands in the gallery; callers
 * promote one to cover explicitly via setCover(). Same shape as
 * FieldImageController; Item/User only ever have a single image, so they
 * don't need index/setCover/reorder at all — see ItemImageController.
 */
class VenueImageController extends Controller
{
    public function __construct(private readonly MediaService $media) {}

    public function index(Venue $venue)
    {
        $this->authorize('view', $venue);

        return MediaResource::collection($venue->media()->get());
    }

    public function store(StoreVenueImageRequest $request, Venue $venue)
    {
        $media = $this->media->upload($venue, $request->file('image'), MediaCollection::GALLERY);

        return MediaResource::make($media)->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function destroy(Venue $venue, Media $media)
    {
        $this->authorize('update', $venue);

        $this->media->assertBelongsTo($media, $venue);
        $this->media->delete($media);

        return response()->noContent();
    }

    public function setCover(Venue $venue, Media $media)
    {
        $this->authorize('update', $venue);

        return MediaResource::make($this->media->setCover($venue, $media));
    }

    public function reorder(ReorderVenueImagesRequest $request, Venue $venue)
    {
        $this->media->reorder($venue, $request->validated('images'));

        return MediaResource::collection($venue->media()->get());
    }
}
