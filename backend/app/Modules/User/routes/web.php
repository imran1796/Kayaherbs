<?php

use App\Modules\User\Controllers\UserManagementController;
use App\Modules\User\Controllers\RoleManagementController;
use Illuminate\Support\Facades\Route;

Route::prefix('roles')->name('roles.')->middleware(['auth', 'admin', 'can:roles.view'])->group(function (): void {
    Route::get('/', [RoleManagementController::class, 'index'])->name('index');
    Route::get('/create', [RoleManagementController::class, 'create'])->middleware('can:roles.create')->name('create');
    Route::post('/', [RoleManagementController::class, 'store'])->middleware('can:roles.create')->name('store');
    Route::get('/{id}/edit', [RoleManagementController::class, 'edit'])->middleware('can:roles.update')->name('edit');
    Route::put('/{id}', [RoleManagementController::class, 'update'])->middleware('can:roles.update')->name('update');
});

Route::prefix('users')->name('users.')->middleware(['auth', 'admin', 'can:users.view'])->group(function (): void {
    Route::get('/', [UserManagementController::class, 'index'])->name('index');
    Route::get('/create', [UserManagementController::class, 'create'])->middleware('can:users.create')->name('create');
    Route::post('/', [UserManagementController::class, 'store'])->middleware('can:users.create')->name('store');
    Route::get('/{id}/edit', [UserManagementController::class, 'edit'])->middleware('can:users.update')->name('edit');
    Route::put('/{id}', [UserManagementController::class, 'update'])->middleware('can:users.update')->name('update');
});
