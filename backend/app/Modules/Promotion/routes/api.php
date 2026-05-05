<?php

use App\Modules\Promotion\Controllers\CouponController;
use Illuminate\Support\Facades\Route;

Route::prefix('coupons')->name('coupons.')->middleware(['auth:sanctum', 'admin'])->group(function (): void {
    Route::get('/', [CouponController::class, 'index'])
        ->middleware('can:coupons.view')
        ->name('index');
    Route::post('/', [CouponController::class, 'store'])
        ->middleware('can:coupons.create')
        ->name('store');
    Route::get('/{id}', [CouponController::class, 'show'])
        ->middleware('can:coupons.view')
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
