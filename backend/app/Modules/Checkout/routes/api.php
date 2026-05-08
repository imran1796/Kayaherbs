<?php

use App\Modules\Checkout\Controllers\CheckoutController;
use Illuminate\Support\Facades\Route;

Route::prefix('checkout')->name('checkout.')->middleware('throttle:auth.session')->group(function (): void {
    Route::get('/shipping-methods', [CheckoutController::class, 'shippingMethods'])->name('shipping-methods');
    Route::post('/guest/submit', [CheckoutController::class, 'submitGuest'])->name('guest.submit');
});

Route::prefix('checkout')->name('checkout.')->middleware(['auth:sanctum', 'customer.token', 'throttle:auth.session'])->group(function (): void {
    Route::post('/validate', [CheckoutController::class, 'validate'])->name('validate');
    Route::post('/submit', [CheckoutController::class, 'submit'])->name('submit');
});
