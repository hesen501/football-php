<?php

namespace App\Modules\Auth\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Http\Requests\ForgotPasswordRequest;
use App\Modules\Auth\Http\Requests\LoginRequest;
use App\Modules\Auth\Http\Requests\RegisterRequest;
use App\Modules\Auth\Http\Requests\ResetPasswordRequest;
use App\Modules\Auth\Services\AuthService;
use App\Modules\User\Http\Resources\UserResource;
use App\Modules\User\Models\User;
use App\Shared\Exceptions\BusinessRuleException;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $auth) {}

    public function register(RegisterRequest $request)
    {
        [$user, $token] = $this->auth->register($request->validated());

        return ApiResponse::success([
            'token' => $token,
            'user' => UserResource::make($user),
        ], status: Response::HTTP_CREATED);
    }

    public function login(LoginRequest $request)
    {
        [$user, $token] = $this->auth->attemptCustomerLogin(
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

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $this->auth->sendPasswordResetLink($request->string('email')->toString());

        // Same response regardless of whether the email is registered —
        // see AuthService::sendPasswordResetLink().
        return ApiResponse::success(['message' => 'If that email is registered, a password reset link has been sent.']);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $this->auth->resetPassword($request->validated());

        return ApiResponse::success(['message' => 'Password reset successfully.']);
    }

    /**
     * Public, unauthenticated by design — signed URL from the verification
     * email is the credential, not a Sanctum token. See routes/api.php: the
     * `signed` middleware rejects a tampered/expired link before this runs.
     */
    public function verifyEmail(Request $request, int $id, string $hash)
    {
        $user = User::query()->findOrFail($id);

        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            throw new BusinessRuleException('This verification link is invalid.', 'INVALID_VERIFICATION_LINK', 400);
        }

        if ($user->hasVerifiedEmail()) {
            return ApiResponse::success(['message' => 'Email already verified.']);
        }

        $user->markEmailAsVerified();

        return ApiResponse::success(['message' => 'Email verified successfully.']);
    }

    public function resendVerification(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return ApiResponse::success(['message' => 'Email already verified.']);
        }

        $request->user()->sendEmailVerificationNotification();

        return ApiResponse::success(['message' => 'Verification link sent.']);
    }
}
