<?php

use App\Modules\User\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::prefix('users')->name('users.')->middleware(['auth', 'admin', 'can:users.view'])->group(function (): void {
    Route::get('/', [UserManagementController::class, 'index'])->name('index');
    Route::get('/create', [UserManagementController::class, 'create'])->middleware('can:users.create')->name('create');
    Route::post('/', [UserManagementController::class, 'store'])->middleware('can:users.create')->name('store');
    Route::get('/{id}/edit', [UserManagementController::class, 'edit'])->middleware('can:users.update')->name('edit');
    Route::put('/{id}', [UserManagementController::class, 'update'])->middleware('can:users.update')->name('update');
});
