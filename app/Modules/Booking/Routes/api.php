<?php

use App\Modules\Booking\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Modules\Booking\Http\Controllers\Customer\BookingController as CustomerBookingController;
use App\Modules\Booking\Http\Controllers\FieldAvailabilityController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/admin')->middleware(['api', 'auth:sanctum', 'admin.panel'])->name('admin.bookings.')->group(function () {
    Route::get('bookings', [AdminBookingController::class, 'index'])->name('index');
    Route::post('bookings', [AdminBookingController::class, 'store'])->name('store');
    Route::get('bookings/{booking}', [AdminBookingController::class, 'show'])->name('show');
    Route::post('bookings/{booking}/confirm', [AdminBookingController::class, 'confirm'])->name('confirm');
    Route::post('bookings/{booking}/cancel', [AdminBookingController::class, 'cancel'])->name('cancel');

    Route::get('fields/{field}/availability', FieldAvailabilityController::class)->name('fieldAvailability');
});

Route::prefix('api')->middleware('api')->group(function () {
    // Public — the same controller as the admin route above, reused verbatim
    // (see its own docblock). No auth required; viewing availability reveals
    // no financial/personal data.
    Route::get('fields/{field}/availability', FieldAvailabilityController::class)->name('customer.fields.availability');

    Route::middleware('auth:sanctum')->name('customer.bookings.')->group(function () {
        Route::get('bookings', [CustomerBookingController::class, 'index'])->name('index');
        Route::post('bookings', [CustomerBookingController::class, 'store'])->name('store');
        Route::get('bookings/{booking}', [CustomerBookingController::class, 'show'])->name('show');
        Route::post('bookings/{booking}/cancel', [CustomerBookingController::class, 'cancel'])->name('cancel');
    });
});
