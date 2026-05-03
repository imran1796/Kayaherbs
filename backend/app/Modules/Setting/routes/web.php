<?php

use App\Modules\Setting\Controllers\ModuleToggleController;
use App\Modules\Setting\Controllers\StoreProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('settings')->name('settings.')->middleware(['auth', 'admin'])->group(function (): void {
    Route::get('/store-profile', [StoreProfileController::class, 'edit'])
        ->middleware('can:settings.view')
        ->name('store-profile.edit');

    Route::put('/store-profile', [StoreProfileController::class, 'update'])
        ->middleware('can:settings.update')
        ->name('store-profile.update');

    Route::get('/module-toggles', [ModuleToggleController::class, 'edit'])
        ->middleware('can:modules.view')
        ->name('module-toggles.edit');

    Route::put('/module-toggles', [ModuleToggleController::class, 'update'])
        ->middleware('can:modules.update')
        ->name('module-toggles.update');
});
