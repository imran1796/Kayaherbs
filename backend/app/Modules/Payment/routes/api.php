<?php

use App\Modules\Payment\Controllers\PaymentController;
use App\Modules\Payment\Controllers\PaymentWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/payments/webhooks/{provider}', [PaymentWebhookController::class, 'handle'])
    ->name('payments.webhooks.handle');

Route::prefix('payments')->name('payments.')->middleware(['auth:sanctum', 'admin'])->group(function (): void {
    Route::patch('/{id}/status', [PaymentController::class, 'updateStatus'])->middleware('can:payments.update')->name('status.update');
    Route::post('/{id}/cod/collect', [PaymentController::class, 'collectCod'])->middleware('can:payments.cod.collect')->name('cod.collect');
});
