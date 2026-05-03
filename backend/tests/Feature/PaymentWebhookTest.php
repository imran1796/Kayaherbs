<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\PaymentWebhookLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.payment_webhooks.providers.manual_bank.secret', 'webhook-secret');
    }

    public function test_payment_webhook_log_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('payment_webhook_logs'));
        $this->assertTrue(Schema::hasColumns('payment_webhook_logs', [
            'provider',
            'event_id',
            'transaction_id',
            'payload_hash',
            'payload',
            'status',
            'failure_reason',
            'processed_at',
        ]));
    }

    public function test_valid_webhook_signature_processes_payment_once(): void
    {
        $order = $this->orderFor(User::factory()->create());
        $payment = $order->payments()->create([
            'provider' => 'manual_bank',
            'method_name' => 'Manual Bank Transfer',
            'status' => 'pending',
            'cod_status' => 'not_applicable',
            'amount' => $order->grand_total,
            'currency' => $order->currency,
            'transaction_id' => 'TXN-100',
        ]);
        $payload = [
            'event_id' => 'evt-paid-1',
            'transaction_id' => 'TXN-100',
            'status' => 'paid',
        ];

        $this->withHeaders($this->signatureHeaders($payload))
            ->postJson('/api/v1/payments/webhooks/manual_bank', $payload)
            ->assertOk()
            ->assertJsonPath('meta.duplicate', false)
            ->assertJsonPath('data.status', 'processed')
            ->assertJsonPath('data.event_id', 'evt-paid-1');

        $this->assertSame('paid', $payment->refresh()->status);
        $this->assertSame('paid', $order->refresh()->payment_status);
        $this->assertSame(1, PaymentWebhookLog::query()->count());
        $this->assertDatabaseHas('payment_webhook_logs', [
            'provider' => 'manual_bank',
            'event_id' => 'evt-paid-1',
            'transaction_id' => 'TXN-100',
            'status' => 'processed',
        ]);
    }

    public function test_invalid_webhook_signature_is_rejected_and_logged(): void
    {
        $payload = [
            'event_id' => 'evt-invalid',
            'transaction_id' => 'TXN-404',
            'status' => 'paid',
        ];

        $this->withHeaders(['X-Payment-Signature' => 'bad-signature'])
            ->postJson('/api/v1/payments/webhooks/manual_bank', $payload)
            ->assertUnauthorized()
            ->assertJsonPath('code', 'invalid_webhook_signature');

        $this->assertDatabaseHas('payment_webhook_logs', [
            'provider' => 'manual_bank',
            'event_id' => 'evt-invalid',
            'status' => 'rejected',
            'failure_reason' => 'invalid_signature',
        ]);
    }

    public function test_duplicate_webhook_event_is_idempotent(): void
    {
        $order = $this->orderFor(User::factory()->create());
        $payment = $order->payments()->create([
            'provider' => 'manual_bank',
            'method_name' => 'Manual Bank Transfer',
            'status' => 'pending',
            'cod_status' => 'not_applicable',
            'amount' => $order->grand_total,
            'currency' => $order->currency,
            'transaction_id' => 'TXN-200',
        ]);
        $payload = [
            'event_id' => 'evt-paid-duplicate',
            'transaction_id' => 'TXN-200',
            'status' => 'paid',
        ];

        $this->withHeaders($this->signatureHeaders($payload))
            ->postJson('/api/v1/payments/webhooks/manual_bank', $payload)
            ->assertOk()
            ->assertJsonPath('meta.duplicate', false);

        $this->withHeaders($this->signatureHeaders($payload))
            ->postJson('/api/v1/payments/webhooks/manual_bank', $payload)
            ->assertOk()
            ->assertJsonPath('meta.duplicate', true)
            ->assertJsonPath('data.status', 'processed');

        $this->assertSame('paid', $payment->refresh()->status);
        $this->assertSame(1, PaymentWebhookLog::query()->count());
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, string>
     */
    private function signatureHeaders(array $payload): array
    {
        return [
            'X-Payment-Signature' => hash_hmac('sha256', json_encode($payload), 'webhook-secret'),
        ];
    }

    private function orderFor(User $customer): Order
    {
        return Order::query()->create([
            'order_number' => 'ORD-WH-'.strtoupper(uniqid()),
            'user_id' => $customer->id,
            'idempotency_key' => 'webhook-'.uniqid(),
            'status' => 'pending',
            'payment_status' => 'pending',
            'fulfillment_status' => 'unfulfilled',
            'shipping_method_code' => 'standard',
            'shipping_method_name' => 'Standard',
            'payment_method_code' => 'manual_bank',
            'payment_method_name' => 'Manual Bank Transfer',
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
