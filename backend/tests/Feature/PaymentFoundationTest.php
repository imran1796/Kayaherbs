<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Modules\Payment\Services\PaymentService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PaymentFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RbacSeeder::class);
    }

    public function test_payment_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('payments'));
        $this->assertTrue(Schema::hasColumns('payments', [
            'order_id',
            'provider',
            'method_name',
            'status',
            'cod_status',
            'amount',
            'currency',
            'transaction_id',
            'provider_reference',
            'paid_at',
            'collected_at',
        ]));
    }

    public function test_initial_payment_creation_syncs_order_payment_status(): void
    {
        $customer = User::factory()->create();
        $order = $this->orderFor($customer, [
            'payment_status' => 'pending',
            'payment_method_code' => 'cod',
            'payment_method_name' => 'Cash on Delivery',
        ]);

        $payment = app(PaymentService::class)->createInitialPaymentForOrder($order, $customer);

        $this->assertSame('cod', $payment->provider);
        $this->assertSame('pending', $payment->status);
        $this->assertSame('pending', $payment->cod_status);
        $this->assertSame('160.00', $payment->amount);
        $this->assertSame('pending', $order->refresh()->payment_status);
        $this->assertDatabaseHas('audit_events', [
            'event' => 'payment.created',
            'actor_id' => $customer->id,
            'auditable_id' => $payment->id,
        ]);
    }

    public function test_paid_payment_syncs_order_to_paid(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);
        $order = $this->orderFor(User::factory()->create());
        $payment = $order->payments()->create([
            'provider' => 'manual_bank',
            'method_name' => 'Manual Bank Transfer',
            'status' => 'pending',
            'amount' => $order->grand_total,
            'currency' => $order->currency,
        ]);

        app(PaymentService::class)->transitionStatus($payment, 'paid', $admin, [
            'source' => 'manual-admin',
        ], 'BANK-123', 'BANK-REF-1');

        $this->assertSame('paid', $payment->refresh()->status);
        $this->assertNotNull($payment->paid_at);
        $this->assertSame('BANK-123', $payment->transaction_id);
        $this->assertSame('BANK-REF-1', $payment->provider_reference);
        $this->assertSame('paid', $order->refresh()->payment_status);
        $this->assertDatabaseHas('audit_events', [
            'event' => 'payment.status.changed',
            'actor_id' => $admin->id,
            'auditable_id' => $payment->id,
        ]);
    }

    public function test_payment_status_machine_blocks_invalid_transition(): void
    {
        $order = $this->orderFor(User::factory()->create());
        $payment = $order->payments()->create([
            'provider' => 'manual_bank',
            'method_name' => 'Manual Bank Transfer',
            'status' => 'pending',
            'amount' => $order->grand_total,
            'currency' => $order->currency,
        ]);

        $this->expectExceptionMessage('Payment cannot transition from pending to refunded.');

        app(PaymentService::class)->transitionStatus($payment, 'refunded');
    }

    public function test_admin_api_can_update_payment_status_manually(): void
    {
        $admin = $this->adminWithPermission('payments.update');
        $order = $this->orderFor(User::factory()->create());
        $payment = $order->payments()->create([
            'provider' => 'manual_bank',
            'method_name' => 'Manual Bank Transfer',
            'status' => 'pending',
            'cod_status' => 'not_applicable',
            'amount' => $order->grand_total,
            'currency' => $order->currency,
        ]);

        $this->withToken($admin->createToken('admin-api')->plainTextToken)
            ->patchJson("/api/v1/payments/{$payment->id}/status", [
                'status' => 'paid',
                'transaction_id' => 'MANUAL-PAID-1',
                'provider_reference' => 'deposit-slip-22',
                'metadata' => ['note' => 'Bank deposit verified.'],
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath('data.transaction_id', 'MANUAL-PAID-1')
            ->assertJsonPath('data.provider_reference', 'deposit-slip-22');

        $this->assertSame('paid', $payment->refresh()->status);
        $this->assertSame('paid', $order->refresh()->payment_status);
    }

    public function test_admin_api_can_collect_cod_payment(): void
    {
        $admin = $this->adminWithPermission('payments.cod.collect');
        $order = $this->orderFor(User::factory()->create(), [
            'payment_method_code' => 'cod',
            'payment_method_name' => 'Cash on Delivery',
        ]);
        $payment = $order->payments()->create([
            'provider' => 'cod',
            'method_name' => 'Cash on Delivery',
            'status' => 'pending',
            'cod_status' => 'pending',
            'amount' => $order->grand_total,
            'currency' => $order->currency,
        ]);

        $this->withToken($admin->createToken('admin-api')->plainTextToken)
            ->postJson("/api/v1/payments/{$payment->id}/cod/collect", [
                'metadata' => ['collected_by' => 'courier'],
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath('data.cod_status', 'collected');

        $payment->refresh();

        $this->assertSame('paid', $payment->status);
        $this->assertSame('collected', $payment->cod_status);
        $this->assertNotNull($payment->paid_at);
        $this->assertNotNull($payment->collected_at);
        $this->assertSame('paid', $order->refresh()->payment_status);
        $this->assertDatabaseHas('audit_events', [
            'event' => 'payment.cod.collected',
            'actor_id' => $admin->id,
            'auditable_id' => $payment->id,
        ]);
    }

    public function test_admin_web_can_update_payment_status_manually(): void
    {
        $admin = $this->adminWithPermission('payments.update');
        $order = $this->orderFor(User::factory()->create());
        $payment = $order->payments()->create([
            'provider' => 'manual_bank',
            'method_name' => 'Manual Bank Transfer',
            'status' => 'pending',
            'cod_status' => 'not_applicable',
            'amount' => $order->grand_total,
            'currency' => $order->currency,
        ]);

        $this->actingAs($admin)
            ->patchJson("/admin/payments/{$payment->id}/status", [
                'status' => 'paid',
                'transaction_id' => 'WEB-PAID-1',
                'provider_reference' => 'web-reference-1',
                'metadata' => ['note' => 'Verified from admin order screen.'],
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath('data.transaction_id', 'WEB-PAID-1')
            ->assertJsonPath('data.provider_reference', 'web-reference-1');

        $this->assertSame('paid', $payment->refresh()->status);
        $this->assertSame('paid', $order->refresh()->payment_status);
        $this->assertDatabaseHas('audit_events', [
            'event' => 'payment.status.changed',
            'actor_id' => $admin->id,
            'auditable_id' => $payment->id,
        ]);
    }

    public function test_admin_web_can_collect_cod_payment(): void
    {
        $admin = $this->adminWithPermission('payments.cod.collect');
        $order = $this->orderFor(User::factory()->create(), [
            'payment_method_code' => 'cod',
            'payment_method_name' => 'Cash on Delivery',
        ]);
        $payment = $order->payments()->create([
            'provider' => 'cod',
            'method_name' => 'Cash on Delivery',
            'status' => 'pending',
            'cod_status' => 'pending',
            'amount' => $order->grand_total,
            'currency' => $order->currency,
        ]);

        $this->actingAs($admin)
            ->postJson("/admin/payments/{$payment->id}/cod/collect", [
                'metadata' => ['collected_by' => 'admin'],
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath('data.cod_status', 'collected');

        $this->assertSame('paid', $payment->refresh()->status);
        $this->assertSame('collected', $payment->cod_status);
        $this->assertSame('paid', $order->refresh()->payment_status);
        $this->assertDatabaseHas('audit_events', [
            'event' => 'payment.cod.collected',
            'actor_id' => $admin->id,
            'auditable_id' => $payment->id,
        ]);
    }

    public function test_admin_payment_web_routes_require_permissions(): void
    {
        $statusMiddleware = $this->middlewareFor('admin.payments.status.update');
        $codMiddleware = $this->middlewareFor('admin.payments.cod.collect');

        $this->assertRouteHasMiddleware($statusMiddleware, 'auth');
        $this->assertRouteHasMiddleware($statusMiddleware, 'admin');
        $this->assertRouteHasMiddleware($statusMiddleware, 'can:payments.update');
        $this->assertRouteHasMiddleware($codMiddleware, 'auth');
        $this->assertRouteHasMiddleware($codMiddleware, 'admin');
        $this->assertRouteHasMiddleware($codMiddleware, 'can:payments.cod.collect');
    }

    public function test_cod_collection_rejects_non_cod_payment(): void
    {
        $admin = $this->adminWithPermission('payments.cod.collect');
        $order = $this->orderFor(User::factory()->create());
        $payment = $order->payments()->create([
            'provider' => 'manual_bank',
            'method_name' => 'Manual Bank Transfer',
            'status' => 'pending',
            'cod_status' => 'not_applicable',
            'amount' => $order->grand_total,
            'currency' => $order->currency,
        ]);

        $this->withToken($admin->createToken('admin-api')->plainTextToken)
            ->postJson("/api/v1/payments/{$payment->id}/cod/collect")
            ->assertUnprocessable()
            ->assertJsonPath('errors.provider.0', 'Only COD payments can be collected through this workflow.');
    }

    private function adminWithPermission(string $permission): User
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);

        $admin->givePermissionTo($permission);

        return $admin;
    }

    /**
     * @return list<string>
     */
    private function middlewareFor(string $routeName): array
    {
        $route = \Illuminate\Support\Facades\Route::getRoutes()->getByName($routeName);

        $this->assertNotNull($route, "Route [{$routeName}] is not registered.");

        return $route->gatherMiddleware();
    }

    /**
     * @param  list<string>  $middleware
     */
    private function assertRouteHasMiddleware(array $middleware, string $expected): void
    {
        $this->assertTrue(
            collect($middleware)->contains(fn (string $entry): bool => $entry === $expected || str_starts_with($entry, $expected)),
            'Expected middleware ['.$expected.'] not found in ['.implode(', ', $middleware).'].'
        );
    }

    private function orderFor(User $customer, array $overrides = []): Order
    {
        return Order::query()->create(array_merge([
            'order_number' => 'ORD-PAY-'.strtoupper(uniqid()),
            'user_id' => $customer->id,
            'idempotency_key' => 'payment-'.uniqid(),
            'status' => 'pending',
            'payment_status' => 'pending',
            'fulfillment_status' => 'unfulfilled',
            'shipping_method_code' => 'standard',
            'shipping_method_name' => 'Standard',
            'payment_method_code' => 'cod',
            'payment_method_name' => 'Cash on Delivery',
            'subtotal' => '100.00',
            'shipping_total' => '60.00',
            'grand_total' => '160.00',
            'currency' => 'BDT',
            'shipping_address' => ['city' => 'Dhaka', 'country' => 'BD'],
            'billing_address' => ['city' => 'Dhaka', 'country' => 'BD'],
            'placed_at' => now(),
        ], $overrides));
    }
}
