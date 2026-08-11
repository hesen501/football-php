<?php

use App\Modules\Field\Http\Controllers\Admin\FieldController as AdminFieldController;
use App\Modules\Field\Http\Controllers\Customer\FieldController as CustomerFieldController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/admin')->middleware(['api', 'auth:sanctum', 'admin.panel'])->name('admin.fields.')->group(function () {
    Route::get('fields', [AdminFieldController::class, 'index'])->name('index');
    Route::get('fields/{field}', [AdminFieldController::class, 'show'])->name('show');
    Route::put('fields/{field}', [AdminFieldController::class, 'update'])->name('update');
    Route::patch('fields/{field}', [AdminFieldController::class, 'update'])->name('updatePartial');
    Route::delete('fields/{field}', [AdminFieldController::class, 'destroy'])->name('destroy');

    // Creation is nested under its parent venue — a field can't exist
    // without one, and this keeps FieldPolicy::create's ownership check
    // (against the venue, not a not-yet-existing field) natural.
    Route::post('venues/{venue}/fields', [AdminFieldController::class, 'store'])->name('store');
});

// Public discovery — no auth required.
Route::prefix('api')->middleware('api')->name('customer.fields.')->group(function () {
    Route::get('venues/{venue}/fields', [CustomerFieldController::class, 'indexForVenue'])->name('indexForVenue');
    Route::get('fields/{field}', [CustomerFieldController::class, 'show'])->name('show');
});
