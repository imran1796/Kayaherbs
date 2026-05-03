<?php

namespace App\Modules\Reporting\Controllers;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Reporting\Requests\ReportDateRangeRequest;
use App\Modules\Reporting\Resources\DashboardKpiResource;
use App\Modules\Reporting\Services\ReportingService;

class ReportingController extends Controller
{
    public function __construct(
        private readonly ReportingService $reports
    ) {}

    public function dashboard(ReportDateRangeRequest $request)
    {
        return ApiResponse::success(
            new DashboardKpiResource($this->reports->dashboardKpis(
                $request->validated('from'),
                $request->validated('to')
            )),
            'Dashboard KPIs fetched successfully.'
        );
    }

    public function orders(ReportDateRangeRequest $request)
    {
        return ApiResponse::success(
            $this->reports->ordersReport($request->validated('from'), $request->validated('to')),
            'Orders report fetched successfully.'
        );
    }

    public function sales(ReportDateRangeRequest $request)
    {
        return ApiResponse::success(
            $this->reports->salesReport($request->validated('from'), $request->validated('to')),
            'Sales report fetched successfully.'
        );
    }

    public function inventory()
    {
        return ApiResponse::success(
            $this->reports->inventoryReport(),
            'Inventory report fetched successfully.'
        );
    }

    public function customers(ReportDateRangeRequest $request)
    {
        return ApiResponse::success(
            $this->reports->customerReport($request->validated('from'), $request->validated('to')),
            'Customer report fetched successfully.'
        );
    }

    public function export(ReportDateRangeRequest $request, string $report)
    {
        $export = $this->reports->export(
            $report,
            $request->validated('from'),
            $request->validated('to')
        );

        return response()->streamDownload(function () use ($export): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $export['headers']);

            foreach ($export['rows'] as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $export['filename'], [
            'Content-Type' => 'text/csv',
        ]);
    }
}
