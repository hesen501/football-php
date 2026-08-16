<?php

use App\Modules\Venue\Http\Controllers\Admin\VenueController as AdminVenueController;
use App\Modules\Venue\Http\Controllers\Admin\VenueImageController;
use App\Modules\Venue\Http\Controllers\Admin\VenueManagerController;
use App\Modules\Venue\Http\Controllers\Admin\VenueWorkingHoursController;
use App\Modules\Venue\Http\Controllers\Customer\VenueController as CustomerVenueController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/admin')->middleware(['api', 'auth:sanctum', 'admin.panel'])->group(function () {
    Route::apiResource('venues', AdminVenueController::class)->names('admin.venues');

    Route::name('admin.venues.managers.')->group(function () {
        Route::get('venues/{venue}/managers', [VenueManagerController::class, 'index'])->name('index');
        Route::post('venues/{venue}/managers', [VenueManagerController::class, 'store'])->name('store');
        Route::delete('venues/{venue}/managers/{user}', [VenueManagerController::class, 'destroy'])->name('destroy');
    });

    Route::name('admin.venues.workingHours.')->group(function () {
        Route::get('venues/{venue}/working-hours', [VenueWorkingHoursController::class, 'index'])->name('index');
        Route::put('venues/{venue}/working-hours', [VenueWorkingHoursController::class, 'update'])->name('update');
    });

    Route::name('admin.venues.images.')->group(function () {
        Route::get('venues/{venue}/images', [VenueImageController::class, 'index'])->name('index');
        Route::post('venues/{venue}/images', [VenueImageController::class, 'store'])->name('store');
        Route::patch('venues/{venue}/images/order', [VenueImageController::class, 'reorder'])->name('reorder');
        Route::delete('venues/{venue}/images/{media}', [VenueImageController::class, 'destroy'])->name('destroy');
        Route::put('venues/{venue}/images/{media}/cover', [VenueImageController::class, 'setCover'])->name('setCover');
    });
});

// Public discovery — no auth required.
Route::prefix('api')->middleware('api')->name('customer.venues.')->group(function () {
    Route::get('venues', [CustomerVenueController::class, 'index'])->name('index');
    Route::get('venues/{venue}', [CustomerVenueController::class, 'show'])->name('show');
});
