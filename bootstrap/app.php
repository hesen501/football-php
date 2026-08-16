<?php

use App\Shared\Http\Middleware\EnsureIsAdminPanelUser;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin.panel' => EnsureIsAdminPanelUser::class,
        ]);

        // Laravel 11+'s minimal skeleton doesn't throttle the 'api' group by
        // default — every route in this app uses that group (see each
        // module's Routes/api.php), so this is a blanket 60/min-per-user(-or-IP)
        // ceiling on top of the tighter 'auth' limiter on login/register/etc.
        $middleware->throttleApi();

        // This is a pure JSON API with no web 'login' route to redirect
        // guests to. Without this, Auth\Middleware\Authenticate falls back to
        // route('login') for any request that doesn't send an explicit
        // `Accept: application/json` header (Laravel Sanctum tokens don't
        // require one) — which throws RouteNotFoundException and surfaces as
        // an uncaught 500 instead of our normal 401 envelope. Forcing this to
        // null makes it always let AuthenticationException through to the
        // renderer below, regardless of the client's Accept header.
        $middleware->redirectGuestsTo(null);

        // Trust any reverse proxy in front of us (ngrok, a load balancer, ...)
        // to report the real client IP and original scheme via X-Forwarded-*.
        // Without this, requests tunneled through ngrok's HTTPS edge look like
        // plain HTTP to Laravel, so signed URLs (email verification) and any
        // absolute link generation would build with the wrong scheme and
        // 403 as "invalid/expired" when clicked. '*' is fine for local dev/
        // demo tunneling; a real production deployment behind a known load
        // balancer should trust its specific IP range instead of everything.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // App\Shared\Exceptions\ApiException subclasses (business-rule errors)
        // already self-render via their own ->render() method — nothing to
        // wire up here for those. The renderers below normalize the framework's
        // own exceptions into the same {"message", "errors"?, "error_code"?}
        // envelope so every error shape is predictable regardless of source.
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error('The given data was invalid.', 422, errors: $e->errors());
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error('Unauthenticated.', 401);
            }
        });

        // Illuminate\Auth\Access\AuthorizationException is what ->authorize()
        // and FormRequest::failedAuthorization() actually throw, but Laravel's
        // Handler::prepareException() unconditionally converts it to this
        // Symfony exception *before* any render() callback runs — registering
        // against AuthorizationException itself would silently never fire.
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error($e->getMessage() ?: 'This action is unauthorized.', 403);
            }
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*')) {
                $model = class_basename($e->getModel());

                return ApiResponse::error("{$model} not found.", 404);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error('Resource not found.', 404);
            }
        });

        $exceptions->render(function (TooManyRequestsHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error('Too many requests. Please try again later.', 429);
            }
        });
    })->create();
