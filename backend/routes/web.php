<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::prefix('admin')->name('admin.')->group(function (): void {
    require base_path('routes/admin.php');
});
