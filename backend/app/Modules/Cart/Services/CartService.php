<?php

namespace App\Modules\Cart\Services;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\ProductVariant;
use App\Models\User;
use App\Modules\Cart\Repositories\CartRepository;
use App\Modules\Promotion\Repositories\CouponRepository;
use App\Modules\Promotion\Services\CouponEligibilityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartService
{
    public const MAX_LINE_QUANTITY = 99;
    public const MAX_CART_LINES = 50;

    public function __construct(
        private readonly CartRepository $carts,
        private readonly CouponRepository $coupons,
        private readonly CouponEligibilityService $couponEligibility
    ) {}

    public function createGuestCart(): Cart
    {
        return $this->carts->createGuestCart();
    }

    public function getGuestCart(string $token): Cart
    {
        return DB::transaction(function () use ($token): Cart {
            $cart = $this->carts->refreshGuestCartExpiry($this->carts->findActiveGuestCart($token));

            return $this->recalculateCart($cart);
        });
    }

    public function getCustomerCart(User $customer): Cart
    {
        return DB::transaction(function () use ($customer): Cart {
            return $this->recalculateCart($this->carts->findOrCreateCustomerCart($customer));
        });
    }

    public function addGuestItem(string $token, int $variantId, int $quantity): Cart
    {
        return DB::transaction(function () use ($token, $variantId, $quantity): Cart {
            $cart = $this->carts->refreshGuestCartExpiry($this->carts->findActiveGuestCart($token));

            return $this->recalculateCart($this->addItemToCart($cart, $variantId, $quantity));
        });
    }

    public function addCustomerItem(User $customer, int $variantId, int $quantity): Cart
    {
        return DB::transaction(function () use ($customer, $variantId, $quantity): Cart {
            $cart = $this->carts->findOrCreateCustomerCart($customer);

            return $this->recalculateCart($this->addItemToCart($cart, $variantId, $quantity));
        });
    }

    public function updateGuestItem(string $token, int $itemId, int $quantity): Cart
    {
        return DB::transaction(function () use ($token, $itemId, $quantity): Cart {
            $cart = $this->carts->refreshGuestCartExpiry($this->carts->findActiveGuestCart($token));

            return $this->recalculateCart($this->updateItemInCart($cart, $itemId, $quantity));
        });
    }

    public function updateCustomerItem(User $customer, int $itemId, int $quantity): Cart
    {
        return DB::transaction(function () use ($customer, $itemId, $quantity): Cart {
            $cart = $this->carts->findActiveCustomerCart($customer);

            return $this->recalculateCart($this->updateItemInCart($cart, $itemId, $quantity));
        });
    }

    public function removeGuestItem(string $token, int $itemId): Cart
    {
        return DB::transaction(function () use ($token, $itemId): Cart {
            $cart = $this->carts->refreshGuestCartExpiry($this->carts->findActiveGuestCart($token));
            $this->carts->deleteItem($this->carts->findItem($cart, $itemId));

            return $this->carts->reload($cart);
        });
    }

    public function removeCustomerItem(User $customer, int $itemId): Cart
    {
        return DB::transaction(function () use ($customer, $itemId): Cart {
            $cart = $this->carts->findActiveCustomerCart($customer);
            $this->carts->deleteItem($this->carts->findItem($cart, $itemId));

            return $this->carts->reload($cart);
        });
    }

    public function clearGuestCart(string $token): Cart
    {
        return DB::transaction(function () use ($token): Cart {
            $cart = $this->carts->refreshGuestCartExpiry($this->carts->findActiveGuestCart($token));
            $this->carts->clearItems($cart);

            return $this->carts->removeCoupon($cart);
        });
    }

    public function clearCustomerCart(User $customer): Cart
    {
        return DB::transaction(function () use ($customer): Cart {
            $cart = $this->carts->findActiveCustomerCart($customer);
            $this->carts->clearItems($cart);

            return $this->carts->removeCoupon($cart);
        });
    }

    public function applyGuestCoupon(string $token, string $code): Cart
    {
        return DB::transaction(function () use ($token, $code): Cart {
            $cart = $this->recalculateCart($this->carts->refreshGuestCartExpiry($this->carts->findActiveGuestCart($token)));
            $coupon = $this->coupons->findByCodeOrFail($code);

            $this->ensureCouponCanApply($coupon, $cart);

            return $this->carts->applyCoupon($cart, $coupon->id, $coupon->code);
        });
    }

    public function removeGuestCoupon(string $token): Cart
    {
        return DB::transaction(function () use ($token): Cart {
            $cart = $this->carts->refreshGuestCartExpiry($this->carts->findActiveGuestCart($token));

            return $this->carts->removeCoupon($cart);
        });
    }

    public function applyCustomerCoupon(User $customer, string $code): Cart
    {
        return DB::transaction(function () use ($customer, $code): Cart {
            $cart = $this->recalculateCart($this->carts->findActiveCustomerCart($customer));
            $coupon = $this->coupons->findByCodeOrFail($code);

            $this->ensureCouponCanApply($coupon, $cart, $customer);

            return $this->carts->applyCoupon($cart, $coupon->id, $coupon->code);
        });
    }

    public function removeCustomerCoupon(User $customer): Cart
    {
        return DB::transaction(function () use ($customer): Cart {
            return $this->carts->removeCoupon($this->carts->findActiveCustomerCart($customer));
        });
    }

    private function addItemToCart(Cart $cart, int $variantId, int $quantity): Cart
    {
        $variant = $this->carts->findSellableVariant($variantId);
        $item = $this->carts->findItemByVariant($cart, $variant->id);
        $newQuantity = ($item?->quantity ?? 0) + $quantity;

        $this->ensureCartHasRoomForNewLine($cart, $item !== null);
        $this->ensureLineQuantityIsAllowed($newQuantity);
        $this->ensureVariantCanBePurchased($variant);
        $this->ensureStockIsAvailable($variant, $newQuantity);
        $this->carts->saveItem($cart, $variant, $newQuantity);

        return $this->carts->reload($cart);
    }

    private function updateItemInCart(Cart $cart, int $itemId, int $quantity): Cart
    {
        $item = $this->carts->findItem($cart, $itemId);
        $variant = $this->carts->findSellableVariant($item->product_variant_id);

        $this->ensureLineQuantityIsAllowed($quantity);
        $this->ensureVariantCanBePurchased($variant);
        $this->ensureStockIsAvailable($variant, $quantity);
        $this->carts->saveItem($cart, $variant, $quantity);

        return $this->carts->reload($cart);
    }

    private function ensureCartHasRoomForNewLine(Cart $cart, bool $lineAlreadyExists): void
    {
        if ($lineAlreadyExists) {
            return;
        }

        if ($this->carts->countItems($cart) >= self::MAX_CART_LINES) {
            throw ValidationException::withMessages([
                'cart' => ['Cart cannot contain more than '.self::MAX_CART_LINES.' different items.'],
            ]);
        }
    }

    private function ensureLineQuantityIsAllowed(int $quantity): void
    {
        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => ['Cart line quantity must be at least 1.'],
            ]);
        }

        if ($quantity > self::MAX_LINE_QUANTITY) {
            throw ValidationException::withMessages([
                'quantity' => ['Cart line quantity cannot be greater than '.self::MAX_LINE_QUANTITY.'.'],
            ]);
        }
    }

    private function ensureVariantCanBePurchased(ProductVariant $variant): void
    {
        if ((float) $variant->price <= 0) {
            throw ValidationException::withMessages([
                'product_variant_id' => ['This product variant cannot be purchased.'],
            ]);
        }
    }

    private function ensureStockIsAvailable(ProductVariant $variant, int $quantity): void
    {
        $stock = $variant->stock;

        if ($stock === null || ! $stock->track_inventory || $stock->allow_backorder) {
            return;
        }

        if ($quantity > $stock->available_quantity) {
            throw ValidationException::withMessages([
                'quantity' => ['Requested quantity is not available.'],
            ]);
        }
    }

    private function ensureCouponCanApply(Coupon $coupon, Cart $cart, ?User $customer = null): void
    {
        $result = $this->couponEligibility->validateForCart($coupon, $cart, $customer);

        if (! $result['eligible']) {
            throw ValidationException::withMessages([
                'code' => $result['reasons'],
            ]);
        }
    }

    private function recalculateCart(Cart $cart): Cart
    {
        foreach ($cart->items as $item) {
            $variant = $this->carts->findVariantForPriceRefresh($item->product_variant_id);

            if ($variant === null) {
                $this->carts->markItemUnavailable($item, 'variant_removed');
                continue;
            }

            $unavailableReason = $this->unavailableReason($variant, $item->quantity);

            if ($unavailableReason !== null) {
                $this->carts->markItemUnavailable($item, $unavailableReason);
                continue;
            }

            $this->carts->saveItem($cart, $variant, $item->quantity);
        }

        return $this->carts->reload($cart);
    }

    private function unavailableReason(ProductVariant $variant, int $quantity): ?string
    {
        if ($variant->status !== 'active') {
            return 'variant_unavailable';
        }

        if (! $variant->product || $variant->product->status !== 'published' || $variant->product->published_at === null) {
            return 'product_unavailable';
        }

        if ((float) $variant->price <= 0) {
            return 'price_unavailable';
        }

        $stock = $variant->stock;

        if ($stock !== null && $stock->track_inventory && ! $stock->allow_backorder && $quantity > $stock->available_quantity) {
            return 'insufficient_stock';
        }

        return null;
    }
}
