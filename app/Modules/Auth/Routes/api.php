<?php

use App\Modules\Auth\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Modules\Auth\Http\Controllers\Customer\AuthController as CustomerAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/admin/auth')->middleware('api')->name('admin.auth.')->group(function () {
    Route::post('login', [AdminAuthController::class, 'login'])->middleware('throttle:auth')->name('login');

    Route::middleware(['auth:sanctum', 'admin.panel'])->group(function () {
        Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');
        Route::get('me', [AdminAuthController::class, 'me'])->name('me');
    });
});

Route::prefix('api/auth')->middleware('api')->group(function () {
    // Not under a customer.auth. name prefix — its name is a hardcoded
    // default in Laravel's VerifyEmail notification
    // (`route('verification.verify', ...)`), so it must stay exactly this,
    // unprefixed. Signed URL is the credential here, not a Sanctum token —
    // see AuthController::verifyEmail. `signed` rejects a tampered/expired
    // link before the controller ever runs.
    Route::get('email/verify/{id}/{hash}', [CustomerAuthController::class, 'verifyEmail'])
        ->middleware('signed')
        ->name('verification.verify');

    Route::name('customer.auth.')->group(function () {
        Route::post('register', [CustomerAuthController::class, 'register'])->middleware('throttle:auth')->name('register');
        Route::post('login', [CustomerAuthController::class, 'login'])->middleware('throttle:auth')->name('login');
        Route::post('forgot-password', [CustomerAuthController::class, 'forgotPassword'])->middleware('throttle:auth')->name('forgotPassword');
        Route::post('reset-password', [CustomerAuthController::class, 'resetPassword'])->middleware('throttle:auth')->name('resetPassword');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [CustomerAuthController::class, 'logout'])->name('logout');
            Route::get('me', [CustomerAuthController::class, 'me'])->name('me');
            Route::post('email/resend', [CustomerAuthController::class, 'resendVerification'])->middleware('throttle:6,1')->name('resendVerification');
        });
    });
});
