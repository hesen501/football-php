<?php

use App\Modules\Item\Http\Controllers\Admin\ItemController as AdminItemController;
use App\Modules\Item\Http\Controllers\Customer\ItemController as CustomerItemController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/admin')->middleware(['api', 'auth:sanctum', 'admin.panel'])->group(function () {
    Route::apiResource('items', AdminItemController::class)->names('admin.items');
});

// Public discovery — no auth required. Active items only (see the
// controller); this is what a booking's "add item" UI would list from.
Route::prefix('api')->middleware('api')->name('customer.items.')->group(function () {
    Route::get('items', [CustomerItemController::class, 'index'])->name('index');
});
