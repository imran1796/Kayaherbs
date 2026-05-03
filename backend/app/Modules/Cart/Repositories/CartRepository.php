<?php

namespace App\Modules\Cart\Repositories;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Str;

class CartRepository
{
    public function createGuestCart(): Cart
    {
        return Cart::query()->create([
            'cart_token' => Str::random(64),
            'status' => 'active',
            'expires_at' => now()->addDays(30),
        ])->load('items');
    }

    public function findActiveGuestCart(string $token): Cart
    {
        return Cart::query()
            ->where('cart_token', $token)
            ->active()
            ->notExpired()
            ->with('items')
            ->firstOrFail();
    }

    public function refreshGuestCartExpiry(Cart $cart): Cart
    {
        if ($cart->user_id === null) {
            $cart->forceFill([
                'expires_at' => now()->addDays(30),
            ])->save();
        }

        return $cart->refresh()->load('items');
    }

    public function findOrCreateCustomerCart(User $customer): Cart
    {
        $cart = $this->activeCustomerCartQuery($customer)->first();

        if ($cart === null) {
            $cart = $customer->carts()->create([
                'cart_token' => Str::random(64),
                'status' => 'active',
            ]);
        }

        return $cart->load('items');
    }

    public function findActiveCustomerCart(User $customer): Cart
    {
        return $this->activeCustomerCartQuery($customer)
            ->firstOrFail()
            ->load('items');
    }

    public function findSellableVariant(int $variantId): ProductVariant
    {
        return ProductVariant::query()
            ->visibleToStorefront()
            ->whereKey($variantId)
            ->whereHas('product', function ($productQuery): void {
                $productQuery->visibleToStorefront();
            })
            ->with(['product', 'stock'])
            ->firstOrFail();
    }

    public function findVariantForPriceRefresh(int $variantId): ?ProductVariant
    {
        return ProductVariant::query()
            ->with(['product', 'stock'])
            ->find($variantId);
    }

    public function findItemByVariant(Cart $cart, int $variantId): ?CartItem
    {
        return $cart->items()
            ->where('product_variant_id', $variantId)
            ->first();
    }

    public function findItem(Cart $cart, int $itemId): CartItem
    {
        return $cart->items()->whereKey($itemId)->firstOrFail();
    }

    public function countItems(Cart $cart): int
    {
        return $cart->items()->count();
    }

    public function saveItem(Cart $cart, ProductVariant $variant, int $quantity): CartItem
    {
        $price = (float) $variant->price;

        return $cart->items()->updateOrCreate(
            ['product_variant_id' => $variant->id],
            [
                'product_id' => $variant->product_id,
                'product_name' => $variant->product->name,
                'variant_name' => $variant->name,
                'sku' => $variant->sku,
                'quantity' => $quantity,
                'unit_price' => $price,
                'line_total' => round($price * $quantity, 2),
                'is_available' => true,
                'unavailable_reason' => null,
            ]
        );
    }

    public function markItemUnavailable(CartItem $item, string $reason): CartItem
    {
        $item->update([
            'line_total' => 0,
            'is_available' => false,
            'unavailable_reason' => $reason,
        ]);

        return $item->refresh();
    }

    public function deleteItem(CartItem $item): void
    {
        $item->delete();
    }

    public function clearItems(Cart $cart): void
    {
        $cart->items()->delete();
    }

    public function reload(Cart $cart): Cart
    {
        return $cart->refresh()->load('items');
    }

    private function activeCustomerCartQuery(User $customer)
    {
        return $customer->carts()
            ->active()
            ->orderByDesc('id');
    }
}
