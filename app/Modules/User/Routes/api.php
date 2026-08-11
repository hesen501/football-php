<?php

use App\Modules\User\Http\Controllers\Admin\UserController;
use App\Modules\User\Http\Controllers\Customer\ProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/admin')->middleware(['api', 'auth:sanctum', 'admin.panel'])->group(function () {
    Route::apiResource('users', UserController::class)->names('admin.users');
});

Route::prefix('api')->middleware(['api', 'auth:sanctum'])->name('customer.profile.')->group(function () {
    Route::get('profile', [ProfileController::class, 'show'])->name('show');
    Route::put('profile', [ProfileController::class, 'update'])->name('update');
    Route::patch('profile', [ProfileController::class, 'update'])->name('updatePartial');
});
