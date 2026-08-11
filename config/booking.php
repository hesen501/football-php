<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Platform Commission Rate (fallback)
    |--------------------------------------------------------------------------
    |
    | Percentage taken by the platform on CUSTOMER_APP bookings. The live
    | value SUPER_ADMIN can change at runtime lives in the platform_settings
    | table (see PlatformSetting::getCommissionRate()); this is only the
    | seed/fallback value used if that row is ever missing.
    |
    */

    'commission_rate' => (float) env('BOOKING_COMMISSION_RATE', 10.00),

];
