<?php

namespace App\Modules\Promotion\Services;

use App\Models\Order;
use App\Models\User;
use App\Modules\Promotion\Repositories\CouponRedemptionRepository;
use App\Modules\Promotion\Repositories\CouponRepository;
use Illuminate\Validation\ValidationException;

class CouponRedemptionService
{
    public function __construct(
        private readonly CouponRepository $coupons,
        private readonly CouponRedemptionRepository $redemptions
    ) {}

    public function recordForOrder(Order $order, User $customer): void
    {
        if ($order->coupon_id === null || (float) $order->discount_total <= 0) {
            return;
        }

        if ($this->redemptions->existsForOrder($order)) {
            return;
        }

        $coupon = $this->coupons->lockForUpdate($order->coupon_id);

        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
            throw ValidationException::withMessages([
                'coupon' => ['coupon_usage_limit_reached'],
            ]);
        }

        if ($coupon->per_customer_usage_limit !== null) {
            $customerUsage = $this->redemptions->countForCustomerWithLock($coupon, $customer);

            if ($customerUsage >= $coupon->per_customer_usage_limit) {
                throw ValidationException::withMessages([
                    'coupon' => ['customer_usage_limit_reached'],
                ]);
            }
        }

        $this->redemptions->create($coupon, $order, $customer, $order->discount_total);
        $this->coupons->incrementUsedCount($coupon);
    }
}
