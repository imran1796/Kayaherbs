<?php

use App\Modules\Auth\Controllers\CustomerAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->name('auth.')->group(function (): void {
    Route::prefix('customer')->name('customer.')->group(function (): void {
        Route::post('/register', [CustomerAuthController::class, 'register'])->middleware('throttle:auth.register')->name('register');
        Route::post('/login', [CustomerAuthController::class, 'login'])->middleware('throttle:auth.login')->name('login');
        Route::post('/password/forgot', [CustomerAuthController::class, 'forgotPassword'])->middleware('throttle:auth.password-reset')->name('password.forgot');
        Route::post('/password/reset', [CustomerAuthController::class, 'resetPassword'])->middleware('throttle:auth.password-reset')->name('password.reset');

        Route::middleware(['auth:sanctum', 'customer.token', 'throttle:auth.session'])->group(function (): void {
            Route::get('/me', [CustomerAuthController::class, 'me'])->name('me');
            Route::post('/logout', [CustomerAuthController::class, 'logout'])->name('logout');
            Route::post('/logout-all', [CustomerAuthController::class, 'logoutAll'])->name('logout.all');
        });
    });
});
