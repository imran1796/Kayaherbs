<?php

use App\Modules\Promotion\Controllers\CouponController;
use App\Modules\Promotion\Controllers\CouponManagementController;
use Illuminate\Support\Facades\Route;

Route::prefix('coupons')->name('coupons.')->middleware(['auth', 'admin', 'can:coupons.view'])->group(function (): void {
    Route::get('/', [CouponManagementController::class, 'index'])
        ->name('index');
    Route::get('/data', [CouponController::class, 'index'])
        ->name('data');
    Route::post('/', [CouponController::class, 'store'])
        ->middleware('can:coupons.create')
        ->name('store');
    Route::get('/{id}', [CouponController::class, 'show'])
        ->name('show');
    Route::put('/{id}', [CouponController::class, 'update'])
        ->middleware('can:coupons.update')
        ->name('update');
    Route::patch('/{id}/activate', [CouponController::class, 'activate'])
        ->middleware('can:coupons.update')
        ->name('activate');
    Route::patch('/{id}/deactivate', [CouponController::class, 'deactivate'])
        ->middleware('can:coupons.update')
        ->name('deactivate');
    Route::delete('/{id}', [CouponController::class, 'destroy'])
        ->middleware('can:coupons.delete')
        ->name('destroy');
});
