<?php

use App\Modules\Setting\Controllers\ModuleToggleController;
use App\Modules\Setting\Controllers\StoreProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('settings')->name('settings.')->middleware(['auth:sanctum', 'admin'])->group(function (): void {
    Route::get('/store-profile', [StoreProfileController::class, 'show'])
        ->middleware('can:settings.view')
        ->name('store-profile.show');

    Route::put('/store-profile', [StoreProfileController::class, 'updateApi'])
        ->middleware('can:settings.update')
        ->name('store-profile.update');

    Route::get('/module-toggles', [ModuleToggleController::class, 'show'])
        ->middleware('can:modules.view')
        ->name('module-toggles.show');

    Route::put('/module-toggles', [ModuleToggleController::class, 'updateApi'])
        ->middleware('can:modules.update')
        ->name('module-toggles.update');
});
