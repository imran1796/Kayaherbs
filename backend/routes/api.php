<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    $modules = require base_path('config/modules.php');

    foreach ($modules as $module) {
        $route = $module['routes']['api'] ?? null;

        if ($route !== null && file_exists(app_path($route))) {
            require app_path($route);
        }
    }
});
