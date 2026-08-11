<?php

namespace App\Modules\Auth\Providers;

use App\Modules\User\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AuthModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        if (is_file($routes = __DIR__.'/../Routes/api.php')) {
            $this->loadRoutesFrom($routes);
        }

        // SUPER_ADMIN bypasses every Policy/Gate check — the authorization
        // strategy lives here rather than duplicated across every policy.
        Gate::before(fn (User $user, string $ability) => $user->hasRole('SUPER_ADMIN') ? true : null);

        // Brute-force protection for auth endpoints (login/register/password reset).
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip().'|'.$request->input('email'));
        });

        // This is an API-only backend with no frontend yet: Laravel's default
        // ResetPassword notification links to a `password.reset` *named web
        // route*, which doesn't exist here. Point it at the future frontend
        // instead — it's the frontend's job to render the reset form and
        // call POST /api/auth/reset-password with these query params.
        ResetPassword::createUrlUsing(function (User $notifiable, string $token) {
            $frontendUrl = rtrim(config('app.frontend_url'), '/');
            $email = urlencode($notifiable->getEmailForPasswordReset());

            return "{$frontendUrl}/reset-password?token={$token}&email={$email}";
        });
    }
}
