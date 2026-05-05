<?php

namespace App\Modules\Promotion\Services;

use App\Models\Cart;
use App\Models\Coupon;

class CouponDiscountService
{
    public function __construct(
        private readonly CouponEligibilityService $eligibility
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function cartTotals(Cart $cart): array
    {
        return $this->totals($cart, 0);
    }

    /**
     * @return array<string, mixed>
     */
    public function checkoutTotals(Cart $cart, float $shippingTotal): array
    {
        return $this->totals($cart, $shippingTotal);
    }

    /**
     * @return array<string, mixed>
     */
    private function totals(Cart $cart, float $shippingTotal): array
    {
        $cart->loadMissing(['coupon', 'items.product.categories']);

        $subtotal = $this->subtotal($cart);
        $coupon = $cart->coupon;
        $discountTotal = 0.0;
        $shippingDiscountTotal = 0.0;
        $eligibility = ['eligible' => false, 'reasons' => []];

        if ($coupon !== null) {
            $eligibility = $this->eligibility->validateForCart($coupon, $cart, $cart->user);

            if ($eligibility['eligible']) {
                $discountTotal = $this->subtotalDiscount($coupon, $subtotal);
                $shippingDiscountTotal = $coupon->discount_type === Coupon::DISCOUNT_FREE_DELIVERY
                    ? $shippingTotal
                    : 0.0;
            }
        }

        $totalDiscount = min($subtotal + $shippingTotal, $discountTotal + $shippingDiscountTotal);

        return [
            'subtotal' => $this->money($subtotal),
            'discount_total' => $this->money($totalDiscount),
            'shipping_discount_total' => $this->money($shippingDiscountTotal),
            'grand_total' => $this->money(max(0, $subtotal + $shippingTotal - $totalDiscount)),
            'coupon' => $coupon === null ? null : [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'discount_type' => $coupon->discount_type,
                'discount_value' => $coupon->discount_value,
                'eligible' => $eligibility['eligible'],
                'reasons' => $eligibility['reasons'],
            ],
        ];
    }

    private function subtotal(Cart $cart): float
    {
        return $cart->items
            ->filter(fn ($item): bool => $item->getAttribute('is_available') !== false)
            ->sum(fn ($item): float => (float) $item->line_total);
    }

    private function subtotalDiscount(Coupon $coupon, float $subtotal): float
    {
        if ($coupon->discount_type === Coupon::DISCOUNT_FIXED) {
            return min($subtotal, (float) $coupon->discount_value);
        }

        if ($coupon->discount_type === Coupon::DISCOUNT_PERCENTAGE) {
            return round($subtotal * ((float) $coupon->discount_value / 100), 2);
        }

        return 0.0;
    }

    private function money(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }
}
