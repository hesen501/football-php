<?php

namespace App\Modules\Field\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Field\Http\Requests\ReorderFieldImagesRequest;
use App\Modules\Field\Http\Requests\StoreFieldImageRequest;
use App\Modules\Field\Models\Field;
use App\Modules\Media\Enums\MediaCollection;
use App\Modules\Media\Http\Resources\MediaResource;
use App\Modules\Media\Models\Media;
use App\Modules\Media\Services\MediaService;
use Illuminate\Http\Response;

/**
 * See VenueImageController's docblock — same GALLERY-plus-one-COVER shape.
 */
class FieldImageController extends Controller
{
    public function __construct(private readonly MediaService $media) {}

    public function index(Field $field)
    {
        $this->authorize('view', $field);

        return MediaResource::collection($field->media()->get());
    }

    public function store(StoreFieldImageRequest $request, Field $field)
    {
        $media = $this->media->upload($field, $request->file('image'), MediaCollection::GALLERY);

        return MediaResource::make($media)->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function destroy(Field $field, Media $media)
    {
        $this->authorize('update', $field);

        $this->media->assertBelongsTo($media, $field);
        $this->media->delete($media);

        return response()->noContent();
    }

    public function setCover(Field $field, Media $media)
    {
        $this->authorize('update', $field);

        return MediaResource::make($this->media->setCover($field, $media));
    }

    public function reorder(ReorderFieldImagesRequest $request, Field $field)
    {
        $this->media->reorder($field, $request->validated('images'));

        return MediaResource::collection($field->media()->get());
    }
}
