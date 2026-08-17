<?php

namespace App\Modules\User\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Media\Enums\MediaCollection;
use App\Modules\Media\Http\Resources\MediaResource;
use App\Modules\Media\Services\MediaService;
use App\Modules\User\Http\Requests\Admin\StoreUserAvatarRequest;
use App\Modules\User\Models\User;
use Illuminate\Http\Response;

/**
 * Lets an admin manage another user's avatar (e.g. removing an
 * inappropriate one) — gated by the same UserPolicy::update used for every
 * other admin mutation on a user, so in practice this is SUPER_ADMIN-only,
 * same as the rest of UserController. See Customer\AvatarController for
 * the self-service counterpart every authenticated user has for their own account.
 */
class UserAvatarController extends Controller
{
    public function __construct(private readonly MediaService $media) {}

    public function store(StoreUserAvatarRequest $request, User $user)
    {
        $media = $this->media->upload($user, $request->file('image'), MediaCollection::AVATAR);

        return MediaResource::make($media)->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function destroy(User $user)
    {
        $this->authorize('update', $user);

        $this->media->deleteAllInCollection($user, MediaCollection::AVATAR);

        return response()->noContent();
    }
}
