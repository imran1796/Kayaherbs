<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Reporting\Services\ReportingService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly ReportingService $reports
    ) {}

    public function __invoke(): View
    {
        return view('admin.dashboard.index', $this->reports->adminDashboard());
    }
}
