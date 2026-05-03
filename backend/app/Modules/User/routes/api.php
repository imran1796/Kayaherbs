<?php

use App\Modules\User\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('users')->name('users.')->middleware(['auth:sanctum', 'admin'])->group(function (): void {
    Route::get('/', [UserController::class, 'index'])->middleware('can:users.view')->name('index');
    Route::post('/', [UserController::class, 'store'])->middleware('can:users.create')->name('store');
    Route::get('/{id}', [UserController::class, 'show'])->middleware('can:users.view')->name('show');
    Route::put('/{id}', [UserController::class, 'update'])->middleware('can:users.update')->name('update');
    Route::delete('/{id}', [UserController::class, 'destroy'])->middleware('can:users.delete')->name('destroy');
});
