<?php

use App\Modules\User\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::prefix('users')->name('users.')->group(function (): void {
    Route::get('/', [UserManagementController::class, 'index'])->name('index');
});
