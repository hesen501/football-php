<?php

use App\Modules\Booking\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Modules\Booking\Http\Controllers\Admin\BookingItemController as AdminBookingItemController;
use App\Modules\Booking\Http\Controllers\Customer\BookingController as CustomerBookingController;
use App\Modules\Booking\Http\Controllers\Customer\BookingItemController as CustomerBookingItemController;
use App\Modules\Booking\Http\Controllers\FieldAvailabilityController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/admin')->middleware(['api', 'auth:sanctum', 'admin.panel'])->name('admin.bookings.')->group(function () {
    Route::get('bookings', [AdminBookingController::class, 'index'])->name('index');
    Route::post('bookings', [AdminBookingController::class, 'store'])->name('store');
    Route::get('bookings/{booking}', [AdminBookingController::class, 'show'])->name('show');
    Route::post('bookings/{booking}/confirm', [AdminBookingController::class, 'confirm'])->name('confirm');
    Route::post('bookings/{booking}/cancel', [AdminBookingController::class, 'cancel'])->name('cancel');

    Route::name('items.')->group(function () {
        Route::post('bookings/{booking}/items', [AdminBookingItemController::class, 'store'])->name('store');
        // withTrashed(): an item can be soft-deleted from the catalog after
        // being added to a booking — removing it must still work, so {item}
        // needs to resolve even when no longer visible to a normal query.
        Route::delete('bookings/{booking}/items/{item}', [AdminBookingItemController::class, 'destroy'])->name('destroy')->withTrashed();
    });

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

        Route::name('items.')->group(function () {
            Route::post('bookings/{booking}/items', [CustomerBookingItemController::class, 'store'])->name('store');
            // See the admin route above for why this needs withTrashed().
            Route::delete('bookings/{booking}/items/{item}', [CustomerBookingItemController::class, 'destroy'])->name('destroy')->withTrashed();
        });
    });
});
