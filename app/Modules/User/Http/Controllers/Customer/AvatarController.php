<?php

namespace App\Modules\User\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Modules\Media\Enums\MediaCollection;
use App\Modules\Media\Http\Resources\MediaResource;
use App\Modules\Media\Services\MediaService;
use App\Modules\User\Http\Requests\Customer\StoreAvatarRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Self-service counterpart of Admin\UserAvatarController — always acts on
 * the authenticated user's own account, alongside ProfileController's
 * profile/{show,update}. A user can never reach another user's avatar
 * through this controller: there's no {user} route parameter at all.
 */
class AvatarController extends Controller
{
    public function __construct(private readonly MediaService $media) {}

    public function store(StoreAvatarRequest $request)
    {
        $media = $this->media->upload($request->user(), $request->file('image'), MediaCollection::AVATAR);

        return MediaResource::make($media)->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function destroy(Request $request)
    {
        $this->media->deleteAllInCollection($request->user(), MediaCollection::AVATAR);

        return response()->noContent();
    }
}
