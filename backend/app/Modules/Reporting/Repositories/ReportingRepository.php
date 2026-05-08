<?php

namespace App\Modules\Reporting\Repositories;

use App\Models\InventoryStock;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReportingRepository
{
    /**
     * @return array<string, int>
     */
    public function orderStatusCounts(?string $from = null, ?string $to = null): array
    {
        return $this->ordersInRange($from, $to)
            ->get()
            ->countBy('status')
            ->map(fn ($total): int => (int) $total)
            ->all();
    }

    public function totalOrders(?string $from = null, ?string $to = null): int
    {
        return $this->ordersInRange($from, $to)->count();
    }

    public function grossSales(?string $from = null, ?string $to = null): string
    {
        $total = $this->ordersInRange($from, $to)
            ->notCancelled()
            ->sum('grand_total');

        return $this->money((float) $total);
    }

    public function paidSales(?string $from = null, ?string $to = null): string
    {
        $total = $this->ordersInRange($from, $to)
            ->where('payment_status', 'paid')
            ->notCancelled()
            ->sum('grand_total');

        return $this->money((float) $total);
    }

    /**
     * @return Collection<int, object>
     */
    public function salesByDay(?string $from = null, ?string $to = null): Collection
    {
        return $this->ordersInRange($from, $to)
            ->notCancelled()
            ->orderBy('placed_at')
            ->get()
            ->groupBy(fn (Order $order): string => $order->placed_at->toDateString())
            ->map(fn (Collection $orders, string $date): object => (object) [
                'date' => $date,
                'orders_count' => $orders->count(),
                'gross_sales' => $orders->sum('grand_total'),
                'paid_sales' => $orders
                    ->where('payment_status', 'paid')
                    ->sum('grand_total'),
            ])
            ->values();
    }

    public function inventorySummary(): array
    {
        $stocks = InventoryStock::query()->get();
        $trackedStocks = $stocks->where('track_inventory', true);

        return [
            'tracked_variants' => $trackedStocks->count(),
            'total_on_hand' => (int) $stocks->sum('quantity_on_hand'),
            'total_reserved' => (int) $stocks->sum('quantity_reserved'),
            'total_available' => (int) $stocks->sum('available_quantity'),
            'low_stock_count' => $trackedStocks
                ->filter(fn (InventoryStock $stock): bool => $stock->is_low_stock)
                ->count(),
        ];
    }

    public function lowStockRows(int $limit = 10): Collection
    {
        return InventoryStock::query()
            ->with('variant.product')
            ->tracked()
            ->get()
            ->filter(fn (InventoryStock $stock): bool => $stock->is_low_stock)
            ->sortBy('available_quantity')
            ->take($limit)
            ->values();
    }

    public function customerSummary(?string $from = null, ?string $to = null): array
    {
        $customers = User::query()->customers()->get();
        $ordersByCustomer = $this->ordersInRange($from, $to)
            ->get()
            ->groupBy('user_id');

        return [
            'total_customers' => $customers->count(),
            'active_customers' => $customers->where('status', 'active')->count(),
            'customers_with_orders' => $ordersByCustomer
                ->filter(fn (Collection $orders): bool => $orders->isNotEmpty())
                ->count(),
            'repeat_customers' => $ordersByCustomer
                ->filter(fn (Collection $orders): bool => $orders->count() > 1)
                ->count(),
        ];
    }

    public function topCustomers(?string $from = null, ?string $to = null, int $limit = 10): Collection
    {
        return User::query()
            ->customers()
            ->with(['orders' => fn ($query) => $query->placedBetween($from, $to)->notCancelled()])
            ->get()
            ->map(function (User $customer): User {
                $customer->orders_count = $customer->orders->count();
                $customer->total_spent = $customer->orders->sum('grand_total');

                return $customer;
            })
            ->filter(fn (User $customer): bool => $customer->orders_count > 0)
            ->sortByDesc('total_spent')
            ->take($limit)
            ->values();
    }

    public function couponSummary(?string $from = null, ?string $to = null): array
    {
        $redemptions = $this->couponRedemptionsInRange($from, $to);

        return [
            'total_coupons' => Coupon::query()->count(),
            'active_coupons' => Coupon::query()->where('status', Coupon::STATUS_ACTIVE)->count(),
            'total_redemptions' => $redemptions->count(),
            'total_discount' => $this->money((float) $redemptions->sum('discount_amount')),
        ];
    }

    public function couponPerformanceRows(?string $from = null, ?string $to = null, int $limit = 20): Collection
    {
        $redemptions = $this->couponRedemptionsInRange($from, $to)
            ->get()
            ->groupBy('coupon_id');

        return Coupon::query()
            ->orderBy('code')
            ->get()
            ->map(function (Coupon $coupon) use ($redemptions): Coupon {
                $couponRedemptions = $redemptions->get($coupon->id, collect());
                $coupon->redemptions_count = $couponRedemptions->count();
                $coupon->discount_total = $couponRedemptions->sum('discount_amount');

                return $coupon;
            })
            ->sortByDesc('discount_total')
            ->take($limit)
            ->values();
    }

    /**
     * @return array<string, int>
     */
    public function userSummary(): array
    {
        $users = User::query();

        return [
            'total_users' => (clone $users)->count(),
            'active_users' => (clone $users)->where('status', 'active')->count(),
            'inactive_users' => (clone $users)->where('status', '!=', 'active')->count(),
        ];
    }

    public function paymentSummary(): array
    {
        $payments = Payment::query()->get();

        return [
            'total_payments' => $payments->count(),
            'paid_amount' => $this->money((float) $payments->where('status', 'paid')->sum('amount')),
            'pending_amount' => $this->money((float) $payments->where('status', 'pending')->sum('amount')),
            'failed_count' => $payments->where('status', 'failed')->count(),
            'cod_pending_count' => $payments->where('cod_status', 'pending')->count(),
            'cod_collected_count' => $payments->where('cod_status', 'collected')->count(),
        ];
    }

    public function recentOrders(int $limit = 6): Collection
    {
        return Order::query()
            ->with('customer')
            ->latest('placed_at')
            ->latest('id')
            ->take($limit)
            ->get();
    }

    public function expiringCoupons(int $days = 7, int $limit = 5): Collection
    {
        return Coupon::query()
            ->where('status', Coupon::STATUS_ACTIVE)
            ->whereNotNull('ends_at')
            ->whereBetween('ends_at', [Carbon::now(), Carbon::now()->addDays($days)])
            ->orderBy('ends_at')
            ->take($limit)
            ->get();
    }

    public function recentUsers(int $limit = 5): Collection
    {
        return User::query()
            ->latest()
            ->take($limit)
            ->get(['id', 'name', 'email', 'status', 'created_at']);
    }

    private function ordersInRange(?string $from = null, ?string $to = null)
    {
        return Order::query()->placedBetween($from, $to);
    }

    private function couponRedemptionsInRange(?string $from = null, ?string $to = null)
    {
        $query = CouponRedemption::query();

        if ($from !== null) {
            $query->whereDate('redeemed_at', '>=', $from);
        }

        if ($to !== null) {
            $query->whereDate('redeemed_at', '<=', $to);
        }

        return $query;
    }

    private function money(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }
}
