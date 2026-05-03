<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Modules\Payment\Contracts\PaymentGatewayAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentGatewayAdapterContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_gateway_adapter_contract_shape(): void
    {
        $order = $this->orderFor(User::factory()->create());
        $payment = $order->payments()->create([
            'provider' => 'fake_gateway',
            'method_name' => 'Fake Gateway',
            'status' => 'pending',
            'cod_status' => 'not_applicable',
            'amount' => $order->grand_total,
            'currency' => $order->currency,
        ]);
        $adapter = new FakePaymentGatewayAdapter;

        $this->assertSame('fake_gateway', $adapter->provider());
        $this->assertSame('pending', $adapter->createPayment($order, $payment)['status']);
        $this->assertTrue($adapter->verifyWebhook(['x-fake-signature' => 'valid'], '{"event_id":"evt_1"}'));
        $this->assertFalse($adapter->verifyWebhook(['x-fake-signature' => 'invalid'], '{"event_id":"evt_1"}'));
        $this->assertSame('paid', $adapter->parseWebhook([], '{"event_id":"evt_1"}')['status']);
        $this->assertSame('refunded', $adapter->refund($payment, '10.00')['status']);
    }

    private function orderFor(User $customer): Order
    {
        return Order::query()->create([
            'order_number' => 'ORD-GW-'.strtoupper(uniqid()),
            'user_id' => $customer->id,
            'idempotency_key' => 'gateway-'.uniqid(),
            'status' => 'pending',
            'payment_status' => 'pending',
            'fulfillment_status' => 'unfulfilled',
            'shipping_method_code' => 'standard',
            'shipping_method_name' => 'Standard',
            'payment_method_code' => 'fake_gateway',
            'payment_method_name' => 'Fake Gateway',
            'subtotal' => '100.00',
            'shipping_total' => '60.00',
            'grand_total' => '160.00',
            'currency' => 'BDT',
            'shipping_address' => ['city' => 'Dhaka', 'country' => 'BD'],
            'billing_address' => ['city' => 'Dhaka', 'country' => 'BD'],
            'placed_at' => now(),
        ]);
    }
}

class FakePaymentGatewayAdapter implements PaymentGatewayAdapter
{
    public function provider(): string
    {
        return 'fake_gateway';
    }

    public function createPayment(Order $order, Payment $payment): array
    {
        return [
            'provider' => $this->provider(),
            'payment_id' => 'fake-'.$payment->id,
            'redirect_url' => 'https://example.com/pay/'.$order->order_number,
            'status' => 'pending',
            'raw' => ['order_number' => $order->order_number],
        ];
    }

    public function verifyWebhook(array $headers, string $payload): bool
    {
        return ($headers['x-fake-signature'] ?? null) === 'valid';
    }

    public function parseWebhook(array $headers, string $payload): array
    {
        return [
            'event_id' => 'evt_1',
            'transaction_id' => 'txn_1',
            'provider_reference' => 'ref_1',
            'status' => 'paid',
            'amount' => '160.00',
            'currency' => 'BDT',
            'raw' => json_decode($payload, true) ?: [],
        ];
    }

    public function refund(Payment $payment, string|float|int $amount, array $metadata = []): array
    {
        return [
            'provider' => $this->provider(),
            'refund_id' => 'refund-'.$payment->id,
            'status' => 'refunded',
            'amount' => $amount,
            'raw' => $metadata,
        ];
    }
}
