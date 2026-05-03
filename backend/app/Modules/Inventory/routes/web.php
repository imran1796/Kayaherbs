<?php

use App\Modules\Inventory\Controllers\InventoryManagementController;
use App\Modules\Inventory\Controllers\InventoryStockController;
use Illuminate\Support\Facades\Route;

Route::prefix('inventory')->name('inventory.')->middleware(['auth', 'admin'])->group(function (): void {
    Route::get('/', [InventoryManagementController::class, 'index'])
        ->middleware('can:inventory.view')
        ->name('index');

    Route::get('/data', [InventoryStockController::class, 'index'])
        ->middleware('can:inventory.view')
        ->name('data');

    Route::get('/low-stock/data', [InventoryStockController::class, 'lowStock'])
        ->middleware('can:inventory.view')
        ->name('low-stock.data');

    Route::get('/history', [InventoryManagementController::class, 'history'])
        ->middleware('can:inventory.view')
        ->name('history');

    Route::get('/history/data', [InventoryStockController::class, 'history'])
        ->middleware('can:inventory.view')
        ->name('history.data');

    Route::post('/variants/{variantId}/adjust', [InventoryStockController::class, 'adjust'])
        ->middleware('can:inventory.adjust')
        ->name('variants.adjust');

    Route::post('/variants/{variantId}/reserve', [InventoryStockController::class, 'reserve'])
        ->middleware('can:inventory.reserve')
        ->name('variants.reserve');

    Route::post('/variants/{variantId}/release', [InventoryStockController::class, 'release'])
        ->middleware('can:inventory.release')
        ->name('variants.release');
});
