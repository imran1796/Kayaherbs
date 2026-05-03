<?php

use App\Modules\Payment\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('payments')->name('payments.')->middleware(['auth', 'admin'])->group(function (): void {
    Route::patch('/{id}/status', [PaymentController::class, 'updateStatus'])
        ->middleware('can:payments.update')
        ->name('status.update');

    Route::post('/{id}/cod/collect', [PaymentController::class, 'collectCod'])
        ->middleware('can:payments.cod.collect')
        ->name('cod.collect');
});
