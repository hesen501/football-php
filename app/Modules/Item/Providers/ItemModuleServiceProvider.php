<?php

namespace App\Modules\Item\Providers;

use App\Modules\Item\Models\Item;
use App\Modules\Item\Policies\ItemPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class ItemModuleServiceProvider extends ServiceProvider
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

        Gate::policy(Item::class, ItemPolicy::class);
    }
}
