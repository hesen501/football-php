<?php

namespace App\Modules\Venue\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\User\Http\Resources\UserResource;
use App\Modules\User\Models\User;
use App\Modules\Venue\Http\Requests\AttachManagerRequest;
use App\Modules\Venue\Models\Venue;
use App\Modules\Venue\Services\VenueService;
use Illuminate\Http\Response;

class VenueManagerController extends Controller
{
    public function __construct(private readonly VenueService $venues) {}

    public function index(Venue $venue)
    {
        $this->authorize('view', $venue);

        return UserResource::collection($venue->managers()->get());
    }

    public function store(AttachManagerRequest $request, Venue $venue)
    {
        $this->venues->attachManager($venue, $request->integer('user_id'));

        return UserResource::collection($venue->managers()->get())
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function destroy(Venue $venue, User $user)
    {
        $this->authorize('manageManagers', $venue);

        $this->venues->detachManager($venue, $user);

        return response()->noContent();
    }
}
