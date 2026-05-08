<?php

namespace App\Modules\Reporting\Services;

use App\Modules\Reporting\Repositories\ReportingRepository;

class ReportingService
{
    public function __construct(
        private readonly ReportingRepository $reports
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function dashboardKpis(?string $from = null, ?string $to = null): array
    {
        $orders = $this->reports->totalOrders($from, $to);
        $statusCounts = $this->reports->orderStatusCounts($from, $to);
        $grossSales = $this->reports->grossSales($from, $to);
        $paidSales = $this->reports->paidSales($from, $to);

        return [
            'total_orders' => $orders,
            'pending_orders' => $statusCounts['pending'] ?? 0,
            'cancelled_orders' => $statusCounts['cancelled'] ?? 0,
            'delivered_orders' => $statusCounts['delivered'] ?? 0,
            'gross_sales' => $grossSales,
            'paid_sales' => $paidSales,
            'average_order_value' => $orders > 0
                ? number_format((float) $grossSales / $orders, 2, '.', '')
                : '0.00',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function ordersReport(?string $from = null, ?string $to = null): array
    {
        return [
            'total_orders' => $this->reports->totalOrders($from, $to),
            'status_counts' => $this->reports->orderStatusCounts($from, $to),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function salesReport(?string $from = null, ?string $to = null): array
    {
        return [
            'gross_sales' => $this->reports->grossSales($from, $to),
            'paid_sales' => $this->reports->paidSales($from, $to),
            'daily' => $this->reports->salesByDay($from, $to)
                ->map(fn ($row): array => [
                    'date' => $row->date,
                    'orders_count' => (int) $row->orders_count,
                    'gross_sales' => number_format((float) $row->gross_sales, 2, '.', ''),
                    'paid_sales' => number_format((float) $row->paid_sales, 2, '.', ''),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function inventoryReport(): array
    {
        return [
            'summary' => $this->reports->inventorySummary(),
            'low_stock' => $this->reports->lowStockRows()
                ->map(fn ($stock): array => [
                    'product_variant_id' => $stock->product_variant_id,
                    'product_name' => $stock->variant?->product?->name,
                    'variant_name' => $stock->variant?->name,
                    'sku' => $stock->variant?->sku,
                    'quantity_on_hand' => $stock->quantity_on_hand,
                    'quantity_reserved' => $stock->quantity_reserved,
                    'available_quantity' => $stock->available_quantity,
                    'low_stock_threshold' => $stock->low_stock_threshold,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function customerReport(?string $from = null, ?string $to = null): array
    {
        return [
            'summary' => $this->reports->customerSummary($from, $to),
            'top_customers' => $this->reports->topCustomers($from, $to)
                ->map(fn ($customer): array => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'orders_count' => (int) $customer->orders_count,
                    'total_spent' => number_format((float) $customer->total_spent, 2, '.', ''),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function couponReport(?string $from = null, ?string $to = null): array
    {
        return [
            'summary' => $this->reports->couponSummary($from, $to),
            'coupons' => $this->reports->couponPerformanceRows($from, $to)
                ->map(fn ($coupon): array => [
                    'id' => $coupon->id,
                    'code' => $coupon->code,
                    'name' => $coupon->name,
                    'status' => $coupon->status,
                    'lifecycle_status' => $coupon->lifecycle_status,
                    'discount_type' => $coupon->discount_type,
                    'used_count' => $coupon->used_count,
                    'redemptions_count' => (int) $coupon->redemptions_count,
                    'discount_total' => number_format((float) $coupon->discount_total, 2, '.', ''),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function adminDashboard(): array
    {
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        $orders = $this->ordersReport();
        $inventory = $this->inventoryReport();
        $customers = $this->customerReport();
        $coupons = $this->couponReport();
        $payments = $this->reports->paymentSummary();
        $expiringCoupons = $this->reports->expiringCoupons();
        $statusCounts = $orders['status_counts'];

        return [
            'stats' => $this->reports->userSummary(),
            'sales' => [
                'today_sales' => $this->reports->grossSales($today, $today),
                'today_orders' => $this->reports->totalOrders($today, $today),
                'month_sales' => $this->reports->grossSales($monthStart, $today),
                'month_orders' => $this->reports->totalOrders($monthStart, $today),
                'average_order_value' => $orders['total_orders'] > 0
                    ? number_format((float) $this->reports->grossSales() / $orders['total_orders'], 2, '.', '')
                    : '0.00',
            ],
            'orders' => $orders,
            'customers' => $customers['summary'],
            'top_customers' => $customers['top_customers'],
            'inventory' => $inventory['summary'],
            'low_stock_rows' => $inventory['low_stock'],
            'payments' => $payments,
            'coupons' => $coupons['summary'],
            'coupon_rows' => $coupons['coupons'],
            'recent_orders' => $this->reports->recentOrders()
                ->map(fn ($order): array => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_name' => $order->customer?->name,
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                    'grand_total' => number_format((float) $order->grand_total, 2, '.', ''),
                    'placed_at' => $order->placed_at?->format('d M, h:i A'),
                ])
                ->values()
                ->all(),
            'recent_users' => $this->reports->recentUsers(),
            'expiring_coupons' => $expiringCoupons
                ->map(fn ($coupon): array => [
                    'id' => $coupon->id,
                    'code' => $coupon->code,
                    'name' => $coupon->name,
                    'ends_at' => $coupon->ends_at?->format('d M Y'),
                ])
                ->values()
                ->all(),
            'actions' => [
                [
                    'label' => 'Orders waiting for confirmation',
                    'count' => $statusCounts['pending'] ?? 0,
                    'route' => 'admin.orders.index',
                    'tone' => 'primary',
                ],
                [
                    'label' => 'Orders in processing or packing',
                    'count' => ($statusCounts['processing'] ?? 0) + ($statusCounts['packed'] ?? 0),
                    'route' => 'admin.orders.index',
                    'tone' => 'info',
                ],
                [
                    'label' => 'Low-stock variants need attention',
                    'count' => $inventory['summary']['low_stock_count'],
                    'route' => 'admin.inventory.index',
                    'tone' => 'warning',
                ],
                [
                    'label' => 'COD payments pending collection',
                    'count' => $payments['cod_pending_count'],
                    'route' => 'admin.orders.index',
                    'tone' => 'success',
                ],
                [
                    'label' => 'Coupons expiring soon',
                    'count' => $expiringCoupons->count(),
                    'route' => 'admin.coupons.index',
                    'tone' => 'secondary',
                ],
            ],
        ];
    }

    /**
     * @return array{filename: string, headers: list<string>, rows: list<list<string|int|float|null>>}
     */
    public function export(string $report, ?string $from = null, ?string $to = null): array
    {
        return match ($report) {
            'sales' => $this->salesExport($from, $to),
            'orders' => $this->ordersExport($from, $to),
            'inventory' => $this->inventoryExport(),
            'customers' => $this->customersExport($from, $to),
            'coupons' => $this->couponsExport($from, $to),
            default => abort(404),
        };
    }

    private function salesExport(?string $from, ?string $to): array
    {
        $report = $this->salesReport($from, $to);

        return [
            'filename' => 'sales-report.csv',
            'headers' => ['date', 'orders_count', 'gross_sales', 'paid_sales'],
            'rows' => collect($report['daily'])->map(fn (array $row): array => [
                $row['date'],
                $row['orders_count'],
                $row['gross_sales'],
                $row['paid_sales'],
            ])->all(),
        ];
    }

    private function ordersExport(?string $from, ?string $to): array
    {
        $report = $this->ordersReport($from, $to);

        return [
            'filename' => 'orders-report.csv',
            'headers' => ['status', 'orders_count'],
            'rows' => collect($report['status_counts'])->map(fn (int $count, string $status): array => [
                $status,
                $count,
            ])->values()->all(),
        ];
    }

    private function inventoryExport(): array
    {
        $report = $this->inventoryReport();

        return [
            'filename' => 'inventory-report.csv',
            'headers' => ['product_name', 'variant_name', 'sku', 'quantity_on_hand', 'quantity_reserved', 'available_quantity', 'low_stock_threshold'],
            'rows' => collect($report['low_stock'])->map(fn (array $row): array => [
                $row['product_name'],
                $row['variant_name'],
                $row['sku'],
                $row['quantity_on_hand'],
                $row['quantity_reserved'],
                $row['available_quantity'],
                $row['low_stock_threshold'],
            ])->all(),
        ];
    }

    private function customersExport(?string $from, ?string $to): array
    {
        $report = $this->customerReport($from, $to);

        return [
            'filename' => 'customers-report.csv',
            'headers' => ['name', 'email', 'orders_count', 'total_spent'],
            'rows' => collect($report['top_customers'])->map(fn (array $row): array => [
                $row['name'],
                $row['email'],
                $row['orders_count'],
                $row['total_spent'],
            ])->all(),
        ];
    }

    private function couponsExport(?string $from, ?string $to): array
    {
        $report = $this->couponReport($from, $to);

        return [
            'filename' => 'coupons-report.csv',
            'headers' => ['code', 'name', 'status', 'discount_type', 'redemptions_count', 'discount_total'],
            'rows' => collect($report['coupons'])->map(fn (array $row): array => [
                $row['code'],
                $row['name'],
                $row['status'],
                $row['discount_type'],
                $row['redemptions_count'],
                $row['discount_total'],
            ])->all(),
        ];
    }
}
