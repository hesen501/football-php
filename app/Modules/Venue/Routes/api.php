<?php

use App\Modules\Venue\Http\Controllers\Admin\VenueController as AdminVenueController;
use App\Modules\Venue\Http\Controllers\Admin\VenueManagerController;
use App\Modules\Venue\Http\Controllers\Customer\VenueController as CustomerVenueController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/admin')->middleware(['api', 'auth:sanctum', 'admin.panel'])->group(function () {
    Route::apiResource('venues', AdminVenueController::class)->names('admin.venues');

    Route::name('admin.venues.managers.')->group(function () {
        Route::get('venues/{venue}/managers', [VenueManagerController::class, 'index'])->name('index');
        Route::post('venues/{venue}/managers', [VenueManagerController::class, 'store'])->name('store');
        Route::delete('venues/{venue}/managers/{user}', [VenueManagerController::class, 'destroy'])->name('destroy');
    });
});

// Public discovery — no auth required.
Route::prefix('api')->middleware('api')->name('customer.venues.')->group(function () {
    Route::get('venues', [CustomerVenueController::class, 'index'])->name('index');
    Route::get('venues/{venue}', [CustomerVenueController::class, 'show'])->name('show');
});
