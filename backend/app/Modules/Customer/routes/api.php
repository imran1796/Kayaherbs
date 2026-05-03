<?php

use App\Modules\Customer\Controllers\CustomerAddressController;
use App\Modules\Customer\Controllers\CustomerOrderController;
use App\Modules\Customer\Controllers\CustomerProfileController;
use App\Modules\Customer\Controllers\CustomerSupportController;
use Illuminate\Support\Facades\Route;

Route::prefix('customer')->name('customer.')->middleware(['auth:sanctum', 'customer.token', 'throttle:auth.session'])->group(function (): void {
    Route::get('/profile', [CustomerProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [CustomerProfileController::class, 'update'])->name('profile.update');

    Route::get('/addresses', [CustomerAddressController::class, 'index'])->name('addresses.index');
    Route::post('/addresses', [CustomerAddressController::class, 'store'])->name('addresses.store');
    Route::get('/addresses/{id}', [CustomerAddressController::class, 'show'])->name('addresses.show');
    Route::put('/addresses/{id}', [CustomerAddressController::class, 'update'])->name('addresses.update');
    Route::delete('/addresses/{id}', [CustomerAddressController::class, 'destroy'])->name('addresses.destroy');

    Route::get('/orders', [CustomerOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{id}', [CustomerOrderController::class, 'show'])->name('orders.show');
});

Route::prefix('customers')->name('customers.')->middleware(['auth:sanctum', 'admin'])->group(function (): void {
    Route::get('/', [CustomerSupportController::class, 'index'])->middleware('can:customers.view')->name('index');
    Route::get('/{id}', [CustomerSupportController::class, 'show'])->middleware('can:customers.view')->name('show');
    Route::patch('/{id}/status', [CustomerSupportController::class, 'updateStatus'])->middleware('can:customers.update')->name('status.update');
    Route::post('/{id}/notes', [CustomerSupportController::class, 'storeNote'])->middleware('can:customers.notes.create')->name('notes.store');
    Route::put('/{id}/tags', [CustomerSupportController::class, 'syncTags'])->middleware('can:customers.tags.update')->name('tags.sync');
});
