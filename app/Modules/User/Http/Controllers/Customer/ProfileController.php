<?php

namespace App\Modules\User\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Modules\User\Http\Requests\Customer\UpdateProfileRequest;
use App\Modules\User\Http\Resources\UserResource;
use App\Modules\User\Services\UserService;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(private readonly UserService $users) {}

    public function show(Request $request)
    {
        return UserResource::make($request->user()->load(['roles', 'avatarMedia']));
    }

    public function update(UpdateProfileRequest $request)
    {
        $user = $this->users->updateOwnProfile($request->user(), $request->validated());

        return UserResource::make($user);
    }
}
