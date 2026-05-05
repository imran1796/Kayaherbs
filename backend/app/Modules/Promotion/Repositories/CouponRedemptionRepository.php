<?php

namespace App\Modules\Promotion\Repositories;

use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Order;
use App\Models\User;

class CouponRedemptionRepository
{
    public function countForCustomer(Coupon $coupon, User $customer): int
    {
        return $coupon->redemptions()
            ->where('user_id', $customer->id)
            ->count();
    }

    public function countForCustomerWithLock(Coupon $coupon, User $customer): int
    {
        return $coupon->redemptions()
            ->where('user_id', $customer->id)
            ->lockForUpdate()
            ->count();
    }

    public function existsForOrder(Order $order): bool
    {
        return CouponRedemption::query()
            ->where('order_id', $order->id)
            ->exists();
    }

    public function create(Coupon $coupon, Order $order, User $customer, string|float|int $discountAmount): CouponRedemption
    {
        return CouponRedemption::query()->create([
            'coupon_id' => $coupon->id,
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'discount_amount' => $discountAmount,
            'redeemed_at' => now(),
        ]);
    }
}
