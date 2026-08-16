<?php

namespace App\Modules\User\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\User\Http\Requests\ListUsersRequest;
use App\Modules\User\Http\Requests\StoreUserRequest;
use App\Modules\User\Http\Requests\UpdateUserRequest;
use App\Modules\User\Http\Resources\UserResource;
use App\Modules\User\Models\User;
use App\Modules\User\Services\UserService;
use App\Shared\Exceptions\BusinessRuleException;
use App\Shared\Http\Filtering\QueryParams;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserController extends Controller
{
    public function __construct(private readonly UserService $users) {}

    public function index(ListUsersRequest $request)
    {
        $params = QueryParams::fromRequest($request);
        $paginated = $this->users->list($params, $request->only(['role', 'status']));

        return UserResource::collection($paginated);
    }

    public function store(StoreUserRequest $request)
    {
        $user = $this->users->create($request->validated());

        return UserResource::make($user)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(User $user)
    {
        $this->authorize('view', $user);

        return UserResource::make($user->load(['roles', 'avatarMedia']));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $user = $this->users->update($user, $request->validated());

        return UserResource::make($user);
    }

    public function destroy(Request $request, User $user)
    {
        $this->authorize('delete', $user);

        // SUPER_ADMIN bypasses the policy check above entirely (Gate::before),
        // so "can't delete yourself" has to be enforced here, unconditionally,
        // rather than inside UserPolicy::delete() where it would never run.
        if ($request->user()->is($user)) {
            throw new BusinessRuleException('You cannot delete your own account.', 'CANNOT_DELETE_SELF');
        }

        $this->users->delete($user);

        return response()->noContent();
    }
}
