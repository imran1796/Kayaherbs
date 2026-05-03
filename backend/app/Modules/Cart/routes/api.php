<?php

use App\Modules\Cart\Controllers\CustomerCartController;
use App\Modules\Cart\Controllers\GuestCartController;
use Illuminate\Support\Facades\Route;

Route::prefix('cart/guest')->name('cart.guest.')->middleware('throttle:auth.session')->group(function (): void {
    Route::post('/', [GuestCartController::class, 'store'])->name('store');
    Route::get('/{cartToken}', [GuestCartController::class, 'show'])->name('show');
    Route::post('/{cartToken}/items', [GuestCartController::class, 'addItem'])->name('items.store');
    Route::put('/{cartToken}/items/{itemId}', [GuestCartController::class, 'updateItem'])->name('items.update');
    Route::delete('/{cartToken}/items/{itemId}', [GuestCartController::class, 'removeItem'])->name('items.destroy');
    Route::delete('/{cartToken}', [GuestCartController::class, 'clear'])->name('clear');
});

Route::prefix('customer/cart')->name('customer.cart.')->middleware(['auth:sanctum', 'customer.token', 'throttle:auth.session'])->group(function (): void {
    Route::get('/', [CustomerCartController::class, 'show'])->name('show');
    Route::post('/items', [CustomerCartController::class, 'addItem'])->name('items.store');
    Route::put('/items/{itemId}', [CustomerCartController::class, 'updateItem'])->name('items.update');
    Route::delete('/items/{itemId}', [CustomerCartController::class, 'removeItem'])->name('items.destroy');
    Route::delete('/', [CustomerCartController::class, 'clear'])->name('clear');
});
