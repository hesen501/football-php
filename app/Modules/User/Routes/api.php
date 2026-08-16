<?php

use App\Modules\User\Http\Controllers\Admin\UserAvatarController;
use App\Modules\User\Http\Controllers\Admin\UserController;
use App\Modules\User\Http\Controllers\Customer\AvatarController;
use App\Modules\User\Http\Controllers\Customer\ProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/admin')->middleware(['api', 'auth:sanctum', 'admin.panel'])->group(function () {
    Route::apiResource('users', UserController::class)->names('admin.users');

    // Lets an admin manage another user's avatar (e.g. remove an
    // inappropriate one) — see UserAvatarController's docblock.
    Route::post('users/{user}/avatar', [UserAvatarController::class, 'store'])->name('admin.users.avatar.store');
    Route::delete('users/{user}/avatar', [UserAvatarController::class, 'destroy'])->name('admin.users.avatar.destroy');
});

Route::prefix('api')->middleware(['api', 'auth:sanctum'])->name('customer.profile.')->group(function () {
    Route::get('profile', [ProfileController::class, 'show'])->name('show');
    Route::put('profile', [ProfileController::class, 'update'])->name('update');
    Route::patch('profile', [ProfileController::class, 'update'])->name('updatePartial');

    // Self-service — always the authenticated user's own avatar (see
    // Customer\AvatarController), same trust boundary as profile above.
    Route::post('profile/avatar', [AvatarController::class, 'store'])->name('avatar.store');
    Route::delete('profile/avatar', [AvatarController::class, 'destroy'])->name('avatar.destroy');
});
