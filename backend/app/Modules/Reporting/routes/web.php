<?php

use App\Modules\Reporting\Controllers\ReportManagementController;
use App\Modules\Reporting\Controllers\ReportingController;
use Illuminate\Support\Facades\Route;

Route::prefix('reports')->name('reports.')->middleware(['auth', 'admin', 'can:reports.view'])->group(function (): void {
    Route::get('/sales', [ReportManagementController::class, 'sales'])->name('sales');
    Route::get('/sales/data', [ReportingController::class, 'sales'])->name('sales.data');

    Route::get('/orders', [ReportManagementController::class, 'orders'])->name('orders');
    Route::get('/orders/data', [ReportingController::class, 'orders'])->name('orders.data');

    Route::get('/inventory', [ReportManagementController::class, 'inventory'])->name('inventory');
    Route::get('/inventory/data', [ReportingController::class, 'inventory'])->name('inventory.data');

    Route::get('/customers', [ReportManagementController::class, 'customers'])->name('customers');
    Route::get('/customers/data', [ReportingController::class, 'customers'])->name('customers.data');

    Route::get('/{report}/export', [ReportingController::class, 'export'])
        ->whereIn('report', ['sales', 'orders', 'inventory', 'customers'])
        ->name('export');
});
