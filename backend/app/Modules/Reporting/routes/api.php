<?php

use App\Modules\Reporting\Controllers\ReportingController;
use Illuminate\Support\Facades\Route;

Route::prefix('reports')->name('reports.')->middleware(['auth:sanctum', 'admin', 'can:reports.view'])->group(function (): void {
    Route::get('/dashboard', [ReportingController::class, 'dashboard'])->name('dashboard');
    Route::get('/orders', [ReportingController::class, 'orders'])->name('orders');
    Route::get('/sales', [ReportingController::class, 'sales'])->name('sales');
    Route::get('/inventory', [ReportingController::class, 'inventory'])->name('inventory');
    Route::get('/customers', [ReportingController::class, 'customers'])->name('customers');
    Route::get('/coupons', [ReportingController::class, 'coupons'])->name('coupons');
});
