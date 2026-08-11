<?php

use App\Modules\Dashboard\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/admin/dashboard')->middleware(['api', 'auth:sanctum', 'admin.panel'])->name('admin.dashboard.')->group(function () {
    Route::get('stats', [DashboardController::class, 'stats'])->name('stats');
    Route::get('recent-bookings', [DashboardController::class, 'recentBookings'])->name('recentBookings');
});
