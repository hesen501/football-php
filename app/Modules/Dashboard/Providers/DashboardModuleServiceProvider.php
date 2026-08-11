<?php

namespace App\Modules\Dashboard\Providers;

use Illuminate\Support\ServiceProvider;

class DashboardModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // No model/migrations of its own — pure read-only aggregation over
        // other modules' tables.
        if (is_file($routes = __DIR__.'/../Routes/api.php')) {
            $this->loadRoutesFrom($routes);
        }
    }
}
