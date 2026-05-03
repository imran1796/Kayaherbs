<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Reporting\Services\ReportingService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly ReportingService $reports
    ) {}

    public function __invoke(): View
    {
        $users = User::query();
        $inventoryReport = $this->reports->inventoryReport();
        $ordersReport = $this->reports->ordersReport();
        $customerReport = $this->reports->customerReport();

        return view('admin.dashboard.index', [
            'stats' => [
                'total_users' => (clone $users)->count(),
                'active_users' => (clone $users)->where('status', 'active')->count(),
                'inactive_users' => (clone $users)->where('status', '!=', 'active')->count(),
            ],
            'recentUsers' => User::query()
                ->latest()
                ->take(5)
                ->get(['id', 'name', 'email', 'status', 'created_at']),
            'orderSummary' => $ordersReport,
            'customerSummary' => $customerReport['summary'],
            'lowStockSummary' => $inventoryReport['summary'],
            'lowStockRows' => $inventoryReport['low_stock'],
        ]);
    }
}
