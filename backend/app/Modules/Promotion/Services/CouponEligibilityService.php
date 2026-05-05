<?php

namespace App\Modules\Promotion\Services;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\User;
use App\Modules\Promotion\Repositories\CouponRedemptionRepository;
use Illuminate\Support\Collection;

class CouponEligibilityService
{
    public function __construct(
        private readonly CouponRedemptionRepository $redemptions
    ) {}

    /**
     * @return array{eligible: bool, reasons: array<int, string>}
     */
    public function validateForCart(Coupon $coupon, Cart $cart, ?User $customer = null): array
    {
        $cart->loadMissing('items.product.categories');

        $reasons = [];

        if (! $coupon->isCurrentlyActive()) {
            $reasons[] = 'coupon_not_active';
        }

        if (! $this->meetsMinimumOrderValue($coupon, $cart)) {
            $reasons[] = 'minimum_order_value_not_met';
        }

        if (! $this->matchesProductOrCategoryRules($coupon, $cart)) {
            $reasons[] = 'coupon_not_applicable_to_cart_items';
        }

        if (! $this->hasRemainingUsage($coupon)) {
            $reasons[] = 'coupon_usage_limit_reached';
        }

        if ($customer !== null && ! $this->hasRemainingCustomerUsage($coupon, $customer)) {
            $reasons[] = 'customer_usage_limit_reached';
        }

        return [
            'eligible' => $reasons === [],
            'reasons' => $reasons,
        ];
    }

    private function meetsMinimumOrderValue(Coupon $coupon, Cart $cart): bool
    {
        if ($coupon->minimum_order_value === null) {
            return true;
        }

        return $this->eligibleSubtotal($cart) >= (float) $coupon->minimum_order_value;
    }

    private function matchesProductOrCategoryRules(Coupon $coupon, Cart $cart): bool
    {
        $productIds = $this->integerCollection($coupon->eligible_product_ids);
        $categoryIds = $this->integerCollection($coupon->eligible_category_ids);

        if ($productIds->isEmpty() && $categoryIds->isEmpty()) {
            return true;
        }

        foreach ($cart->items as $item) {
            if (! $this->cartItemIsAvailable($item)) {
                continue;
            }

            if ($productIds->contains((int) $item->product_id)) {
                return true;
            }

            $itemCategoryIds = $item->product?->categories?->pluck('id')->map(fn ($id): int => (int) $id) ?? collect();

            if ($itemCategoryIds->intersect($categoryIds)->isNotEmpty()) {
                return true;
            }
        }

        return false;
    }

    private function hasRemainingUsage(Coupon $coupon): bool
    {
        if ($coupon->usage_limit === null) {
            return true;
        }

        return $coupon->used_count < $coupon->usage_limit;
    }

    private function hasRemainingCustomerUsage(Coupon $coupon, User $customer): bool
    {
        if ($coupon->per_customer_usage_limit === null) {
            return true;
        }

        return $this->redemptions->countForCustomer($coupon, $customer) < $coupon->per_customer_usage_limit;
    }

    private function eligibleSubtotal(Cart $cart): float
    {
        return $cart->items
            ->filter(fn ($item): bool => $this->cartItemIsAvailable($item))
            ->sum(fn ($item): float => (float) $item->line_total);
    }

    private function cartItemIsAvailable($item): bool
    {
        return $item->getAttribute('is_available') !== false;
    }

    /**
     * @param  array<int, int|string>|null  $values
     * @return Collection<int, int>
     */
    private function integerCollection(?array $values): Collection
    {
        return collect($values ?? [])
            ->map(fn ($id): int => (int) $id)
            ->filter()
            ->values();
    }
}
