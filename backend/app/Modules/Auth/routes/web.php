<?php

use App\Modules\Auth\Controllers\AdminAuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AdminAuthController::class, 'show'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->middleware('throttle:auth.login')->name('login.store');
    Route::get('/forgot-password', [AdminAuthController::class, 'showForgotPasswordForm'])->name('password.request');
    Route::post('/forgot-password', [AdminAuthController::class, 'sendResetLink'])->middleware('throttle:auth.password-reset')->name('password.email');
    Route::get('/reset-password/{token}', [AdminAuthController::class, 'showResetPasswordForm'])->name('password.reset');
    Route::post('/reset-password', [AdminAuthController::class, 'resetPassword'])->middleware('throttle:auth.password-reset')->name('password.update');
});

Route::post('/logout', [AdminAuthController::class, 'logout'])
    ->middleware(['auth', 'admin', 'throttle:auth.session'])
    ->name('logout');
