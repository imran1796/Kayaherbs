<?php

use App\Modules\Shipping\Controllers\DeliveryRateController;
use App\Modules\Shipping\Controllers\DeliveryZoneController;
use App\Modules\Shipping\Controllers\ShippingManagementController;
use Illuminate\Support\Facades\Route;

Route::prefix('shipping')->name('shipping.')->middleware(['auth', 'admin'])->group(function (): void {
    Route::get('/', [ShippingManagementController::class, 'index'])
        ->middleware('can:shipping.view')
        ->name('index');

    Route::get('/zones/data', [DeliveryZoneController::class, 'index'])
        ->middleware('can:shipping.view')
        ->name('zones.data');
    Route::post('/zones', [DeliveryZoneController::class, 'store'])
        ->middleware('can:shipping.create')
        ->name('zones.store');
    Route::get('/zones/{id}', [DeliveryZoneController::class, 'show'])
        ->middleware('can:shipping.view')
        ->name('zones.show');
    Route::put('/zones/{id}', [DeliveryZoneController::class, 'update'])
        ->middleware('can:shipping.update')
        ->name('zones.update');
    Route::delete('/zones/{id}', [DeliveryZoneController::class, 'destroy'])
        ->middleware('can:shipping.delete')
        ->name('zones.destroy');

    Route::get('/rates/data', [DeliveryRateController::class, 'index'])
        ->middleware('can:shipping.view')
        ->name('rates.data');
    Route::post('/rates', [DeliveryRateController::class, 'store'])
        ->middleware('can:shipping.create')
        ->name('rates.store');
    Route::get('/rates/{id}', [DeliveryRateController::class, 'show'])
        ->middleware('can:shipping.view')
        ->name('rates.show');
    Route::put('/rates/{id}', [DeliveryRateController::class, 'update'])
        ->middleware('can:shipping.update')
        ->name('rates.update');
    Route::delete('/rates/{id}', [DeliveryRateController::class, 'destroy'])
        ->middleware('can:shipping.delete')
        ->name('rates.destroy');
});
