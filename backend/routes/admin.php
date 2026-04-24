<?php

use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('dashboard');

$modules = require base_path('config/modules.php');

foreach ($modules as $module) {
    $route = $module['routes']['web'] ?? null;

    if ($route !== null && file_exists(app_path($route))) {
        require app_path($route);
    }
}
