<?php

use App\Modules\Catalog\Controllers\CategoryController;
use App\Modules\Catalog\Controllers\CategoryManagementController;
use App\Modules\Catalog\Controllers\ProductController;
use App\Modules\Catalog\Controllers\ProductManagementController;
use Illuminate\Support\Facades\Route;

Route::prefix('categories')->name('categories.')->middleware(['auth', 'admin', 'can:categories.view'])->group(function (): void {
    Route::get('/', [CategoryManagementController::class, 'index'])->name('index');
    Route::get('/data', [CategoryController::class, 'index'])->name('data');
    Route::post('/', [CategoryController::class, 'store'])->middleware('can:categories.create')->name('store');
    Route::get('/{id}', [CategoryController::class, 'show'])->name('show');
    Route::put('/{id}', [CategoryController::class, 'update'])->middleware('can:categories.update')->name('update');
    Route::delete('/{id}', [CategoryController::class, 'destroy'])->middleware('can:categories.delete')->name('destroy');
});

Route::prefix('products')->name('products.')->middleware(['auth', 'admin', 'can:products.view'])->group(function (): void {
    Route::get('/', [ProductManagementController::class, 'index'])->name('index');
    Route::get('/data', [ProductController::class, 'index'])->name('data');
    Route::post('/', [ProductController::class, 'store'])->middleware('can:products.create')->name('store');
    Route::get('/{id}', [ProductController::class, 'show'])->name('show');
    Route::put('/{id}', [ProductController::class, 'update'])->middleware('can:products.update')->name('update');
    Route::post('/{id}/publish', [ProductController::class, 'publish'])->middleware('can:products.publish')->name('publish');
    Route::post('/{id}/unpublish', [ProductController::class, 'unpublish'])->middleware('can:products.publish')->name('unpublish');
    Route::delete('/{id}', [ProductController::class, 'destroy'])->middleware('can:products.delete')->name('destroy');
});
