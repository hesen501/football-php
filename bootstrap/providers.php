<?php

use App\Modules\Auth\Providers\AuthModuleServiceProvider;
use App\Modules\Booking\Providers\BookingModuleServiceProvider;
use App\Modules\Dashboard\Providers\DashboardModuleServiceProvider;
use App\Modules\Field\Providers\FieldModuleServiceProvider;
use App\Modules\User\Providers\UserModuleServiceProvider;
use App\Modules\Venue\Providers\VenueModuleServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,

    // Auth registers Gate::before(SUPER_ADMIN bypass) — must load before any
    // module policy is evaluated, so it's listed first among domain modules.
    AuthModuleServiceProvider::class,
    UserModuleServiceProvider::class,
    VenueModuleServiceProvider::class,
    FieldModuleServiceProvider::class,
    BookingModuleServiceProvider::class,
    DashboardModuleServiceProvider::class,
];
