<?php

use App\Modules\Order\Controllers\OrderController;
use App\Modules\Order\Controllers\OrderManagementController;
use Illuminate\Support\Facades\Route;

Route::prefix('orders')->name('orders.')->middleware(['auth', 'admin'])->group(function (): void {
    Route::get('/', [OrderManagementController::class, 'index'])
        ->middleware('can:orders.view')
        ->name('index');

    Route::get('/data', [OrderController::class, 'index'])
        ->middleware('can:orders.view')
        ->name('data');

    Route::get('/{id}', [OrderManagementController::class, 'show'])
        ->middleware('can:orders.view')
        ->name('show');

    Route::get('/{id}/data', [OrderController::class, 'show'])
        ->middleware('can:orders.view')
        ->name('show.data');

    Route::patch('/{id}/status', [OrderController::class, 'updateStatus'])
        ->middleware('can:orders.update_status')
        ->name('status.update');

    Route::post('/{id}/cancel', [OrderController::class, 'cancel'])
        ->middleware('can:orders.cancel')
        ->name('cancel');

    Route::post('/{id}/return-requests', [OrderController::class, 'storeReturnRequest'])
        ->middleware('can:orders.returns.create')
        ->name('return-requests.store');

    Route::post('/{id}/shipments', [OrderController::class, 'storeShipment'])
        ->middleware('can:orders.shipments.create')
        ->name('shipments.store');

    Route::post('/{id}/invoice', [OrderController::class, 'generateInvoice'])
        ->middleware('can:orders.invoices.generate')
        ->name('invoice.generate');

    Route::get('/{id}/invoice/print', [OrderManagementController::class, 'invoice'])
        ->middleware('can:orders.invoices.generate')
        ->name('invoice.print');

    Route::post('/{id}/packing-slip', [OrderController::class, 'generatePackingSlip'])
        ->middleware('can:orders.packing_slips.generate')
        ->name('packing-slip.generate');

    Route::get('/{id}/packing-slip/print', [OrderManagementController::class, 'packingSlip'])
        ->middleware('can:orders.packing_slips.generate')
        ->name('packing-slip.print');
});
