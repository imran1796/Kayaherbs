<?php

namespace App\Modules\Checkout\Repositories;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CheckoutRepository
{
    public function findCustomerAddress(User $customer, int $addressId): CustomerAddress
    {
        return $customer->customerAddresses()
            ->whereKey($addressId)
            ->firstOrFail();
    }

    public function findOrderByIdempotencyKey(User $customer, string $key): ?Order
    {
        return Order::query()
            ->where('user_id', $customer->id)
            ->where('idempotency_key', $key)
            ->with('items')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createOrder(array $data): Order
    {
        return Order::query()->create([
            ...$data,
            'order_number' => $this->nextOrderNumber(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createOrderItem(Order $order, array $data): void
    {
        $order->items()->create($data);
    }

    /**
     * @return Collection<int, CartItem>
     */
    public function availableCartItems(Cart $cart): Collection
    {
        return $cart->items
            ->where('is_available', true)
            ->values();
    }

    public function hasAvailableItems(Cart $cart): bool
    {
        return $this->availableCartItems($cart)->isNotEmpty();
    }

    public function hasUnavailableItems(Cart $cart): bool
    {
        return $cart->items
            ->where('is_available', false)
            ->isNotEmpty();
    }

    public function availableCartSubtotal(Cart $cart): float
    {
        return (float) $this->availableCartItems($cart)
            ->sum('line_total');
    }

    public function completeCart(Cart $cart): void
    {
        $cart->update([
            'status' => 'completed',
        ]);
    }

    public function loadOrder(Order $order): Order
    {
        return $order->refresh()->load(['items', 'payments']);
    }

    private function nextOrderNumber(): string
    {
        do {
            $number = 'ORD-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
        } while (Order::query()->where('order_number', $number)->exists());

        return $number;
    }
}
