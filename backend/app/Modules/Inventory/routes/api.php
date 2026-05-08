<?php

use App\Modules\Inventory\Controllers\InventoryStockController;
use Illuminate\Support\Facades\Route;

Route::prefix('inventory')->name('inventory.')->middleware(['auth:sanctum', 'admin'])->group(function (): void {
    Route::post('/variants/{variantId}/adjust', [InventoryStockController::class, 'adjust'])
        ->middleware('can:inventory.adjust')
        ->name('variants.adjust');

    Route::post('/variants/{variantId}/reserve', [InventoryStockController::class, 'reserve'])
        ->middleware('can:inventory.reserve')
        ->name('variants.reserve');

    Route::post('/variants/{variantId}/release', [InventoryStockController::class, 'release'])
        ->middleware('can:inventory.release')
        ->name('variants.release');

    Route::patch('/variants/{variantId}/threshold', [InventoryStockController::class, 'updateThreshold'])
        ->middleware('can:inventory.adjust')
        ->name('variants.threshold.update');
});
