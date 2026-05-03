<?php

namespace App\Modules\Payment\Repositories;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Collection;

class PaymentRepository
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function createForOrder(Order $order, array $data): Payment
    {
        /** @var Payment $payment */
        $payment = $order->payments()->create($data);

        return $payment;
    }

    public function lockForUpdate(int $paymentId): Payment
    {
        /** @var Payment $payment */
        $payment = Payment::query()
            ->whereKey($paymentId)
            ->lockForUpdate()
            ->firstOrFail();

        return $payment;
    }

    public function findOrFail(int $paymentId): Payment
    {
        /** @var Payment $payment */
        $payment = Payment::query()->with('order')->findOrFail($paymentId);

        return $payment;
    }

    public function findByProviderTransaction(string $provider, string $transactionId): ?Payment
    {
        /** @var Payment|null $payment */
        $payment = Payment::query()
            ->where('provider', $provider)
            ->where('transaction_id', $transactionId)
            ->first();

        return $payment;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Payment $payment, array $data): Payment
    {
        $payment->update($data);

        return $payment->refresh();
    }

    public function paymentsForOrder(Order $order): Collection
    {
        return $order->payments()->get();
    }

    public function lockOrder(int $orderId): Order
    {
        /** @var Order $order */
        $order = Order::query()
            ->whereKey($orderId)
            ->lockForUpdate()
            ->firstOrFail();

        return $order;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateOrder(Order $order, array $data): Order
    {
        $order->update($data);

        return $order->refresh();
    }
}
