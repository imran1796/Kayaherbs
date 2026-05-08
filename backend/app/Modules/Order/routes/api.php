<?php

use App\Modules\Order\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('/orders/lookup', [OrderController::class, 'lookup'])
    ->middleware('throttle:auth.session')
    ->name('orders.lookup');

Route::prefix('orders')->name('orders.')->middleware(['auth:sanctum', 'admin'])->group(function (): void {
    Route::get('/', [OrderController::class, 'index'])->middleware('can:orders.view')->name('index');
    Route::get('/{id}', [OrderController::class, 'show'])->middleware('can:orders.view')->name('show');
    Route::patch('/{id}/status', [OrderController::class, 'updateStatus'])->middleware('can:orders.update_status')->name('status.update');
    Route::post('/{id}/notes', [OrderController::class, 'storeNote'])->middleware('can:orders.notes.create')->name('notes.store');
    Route::post('/{id}/cancel', [OrderController::class, 'cancel'])->middleware('can:orders.cancel')->name('cancel');
    Route::post('/{id}/return-requests', [OrderController::class, 'storeReturnRequest'])->middleware('can:orders.returns.create')->name('return-requests.store');
    Route::post('/{id}/invoice', [OrderController::class, 'generateInvoice'])->middleware('can:orders.invoices.generate')->name('invoice.generate');
    Route::post('/{id}/packing-slip', [OrderController::class, 'generatePackingSlip'])->middleware('can:orders.packing_slips.generate')->name('packing-slip.generate');
    Route::post('/{id}/shipments', [OrderController::class, 'storeShipment'])->middleware('can:orders.shipments.create')->name('shipments.store');
});
