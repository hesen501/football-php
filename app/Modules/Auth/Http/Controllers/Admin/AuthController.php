<?php

namespace App\Modules\Auth\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Http\Requests\LoginRequest;
use App\Modules\Auth\Services\AuthService;
use App\Modules\User\Http\Resources\UserResource;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $auth) {}

    public function login(LoginRequest $request)
    {
        [$user, $token] = $this->auth->attemptAdminLogin(
            $request->string('email')->toString(),
            $request->string('password')->toString(),
        );

        return ApiResponse::success([
            'token' => $token,
            'user' => UserResource::make($user),
        ]);
    }

    public function logout(Request $request)
    {
        $this->auth->logout($request->user());

        return ApiResponse::success(['message' => 'Logged out.']);
    }

    public function me(Request $request)
    {
        return UserResource::make($request->user()->load('roles'));
    }
}
