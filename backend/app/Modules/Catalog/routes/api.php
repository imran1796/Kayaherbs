<?php

use App\Modules\Catalog\Controllers\CategoryController;
use App\Modules\Catalog\Controllers\ProductController;
use App\Modules\Catalog\Controllers\PublicCatalogController;
use Illuminate\Support\Facades\Route;

Route::prefix('catalog')->name('catalog.')->group(function (): void {
    Route::get('/products', [PublicCatalogController::class, 'index'])->name('products.index');
    Route::get('/products/{slug}', [PublicCatalogController::class, 'show'])->name('products.show');
});

Route::prefix('categories')->name('categories.')->middleware(['auth:sanctum', 'admin'])->group(function (): void {
    Route::get('/', [CategoryController::class, 'index'])->middleware('can:categories.view')->name('index');
    Route::post('/', [CategoryController::class, 'store'])->middleware('can:categories.create')->name('store');
    Route::get('/{id}', [CategoryController::class, 'show'])->middleware('can:categories.view')->name('show');
    Route::put('/{id}', [CategoryController::class, 'update'])->middleware('can:categories.update')->name('update');
    Route::delete('/{id}', [CategoryController::class, 'destroy'])->middleware('can:categories.delete')->name('destroy');
});

Route::prefix('products')->name('products.')->middleware(['auth:sanctum', 'admin'])->group(function (): void {
    Route::get('/', [ProductController::class, 'index'])->middleware('can:products.view')->name('index');
    Route::post('/', [ProductController::class, 'store'])->middleware('can:products.create')->name('store');
    Route::get('/{id}', [ProductController::class, 'show'])->middleware('can:products.view')->name('show');
    Route::put('/{id}', [ProductController::class, 'update'])->middleware('can:products.update')->name('update');
    Route::post('/{id}/publish', [ProductController::class, 'publish'])->middleware('can:products.publish')->name('publish');
    Route::post('/{id}/unpublish', [ProductController::class, 'unpublish'])->middleware('can:products.publish')->name('unpublish');
    Route::delete('/{id}', [ProductController::class, 'destroy'])->middleware('can:products.delete')->name('destroy');
});
