<?php

namespace App\Modules\Venue\Providers;

use App\Modules\Venue\Models\Venue;
use App\Modules\Venue\Policies\VenuePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class VenueModuleServiceProvider extends ServiceProvider
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

        Gate::policy(Venue::class, VenuePolicy::class);
    }
}
