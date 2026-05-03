<?php

use App\Modules\Customer\Controllers\CustomerManagementController;
use App\Modules\Customer\Controllers\CustomerAddressController;
use App\Modules\Customer\Controllers\CustomerSupportController;
use Illuminate\Support\Facades\Route;

Route::prefix('customers')->name('customers.')->middleware(['auth', 'admin'])->group(function (): void {
    Route::get('/', [CustomerManagementController::class, 'index'])
        ->middleware('can:customers.view')
        ->name('index');

    Route::get('/data', [CustomerSupportController::class, 'index'])
        ->middleware('can:customers.view')
        ->name('data');

    Route::get('/{id}', [CustomerManagementController::class, 'show'])
        ->middleware('can:customers.view')
        ->name('show');

    Route::get('/{id}/data', [CustomerSupportController::class, 'show'])
        ->middleware('can:customers.view')
        ->name('show.data');

    Route::post('/{id}/addresses', [CustomerAddressController::class, 'adminStore'])
        ->middleware('can:customers.update')
        ->name('addresses.store');

    Route::put('/{id}/addresses/{addressId}', [CustomerAddressController::class, 'adminUpdate'])
        ->middleware('can:customers.update')
        ->name('addresses.update');

    Route::delete('/{id}/addresses/{addressId}', [CustomerAddressController::class, 'adminDestroy'])
        ->middleware('can:customers.update')
        ->name('addresses.destroy');

    Route::post('/{id}/notes', [CustomerSupportController::class, 'storeNote'])
        ->middleware('can:customers.notes.create')
        ->name('notes.store');

    Route::put('/{id}/tags', [CustomerSupportController::class, 'syncTags'])
        ->middleware('can:customers.tags.update')
        ->name('tags.sync');
});
