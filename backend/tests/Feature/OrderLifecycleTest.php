<?php

namespace Tests\Feature;

use App\Models\AuditEvent;
use App\Models\InventoryStock;
use App\Models\Order;
use App\Models\OrderInvoice;
use App\Models\OrderItem;
use App\Models\OrderNote;
use App\Models\OrderPackingSlip;
use App\Models\OrderReturnRequest;
use App\Models\OrderShipment;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class OrderLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RbacSeeder::class);
    }

    public function test_order_lifecycle_tables_and_columns_exist(): void
    {
        $this->assertTrue(Schema::hasTable('order_status_histories'));
        $this->assertTrue(Schema::hasTable('order_notes'));
        $this->assertTrue(Schema::hasTable('order_return_requests'));
        $this->assertTrue(Schema::hasTable('order_invoices'));
        $this->assertTrue(Schema::hasTable('order_packing_slips'));
        $this->assertTrue(Schema::hasTable('order_shipments'));
        $this->assertTrue(Schema::hasColumns('orders', [
            'confirmed_at',
            'processing_at',
            'packed_at',
            'shipped_at',
            'delivered_at',
            'cancelled_at',
        ]));
    }

    public function test_admin_can_move_order_through_allowed_status_transition(): void
    {
        $admin = $this->adminWithOrderPermissions(['orders.view', 'orders.update_status']);
        $order = $this->orderFor(User::factory()->create());

        $this->withToken($admin->createToken('admin-api')->plainTextToken)
            ->patchJson("/api/v1/orders/{$order->id}/status", [
                'status' => 'confirmed',
                'note' => 'Payment checked by admin.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmed')
            ->assertJsonPath('data.status_history.0.from_status', 'pending')
            ->assertJsonPath('data.status_history.0.to_status', 'confirmed')
            ->assertJsonPath('data.status_history.0.actor.id', $admin->id);

        $order->refresh();

        $this->assertSame('confirmed', $order->status);
        $this->assertSame('unfulfilled', $order->fulfillment_status);
        $this->assertNotNull($order->confirmed_at);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'actor_id' => $admin->id,
            'from_status' => 'pending',
            'to_status' => 'confirmed',
            'note' => 'Payment checked by admin.',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'event' => 'order.status.changed',
            'actor_id' => $admin->id,
            'auditable_id' => $order->id,
        ]);
    }

    public function test_order_status_transition_syncs_fulfillment_status(): void
    {
        $admin = $this->adminWithOrderPermissions(['orders.view', 'orders.update_status']);
        $order = $this->orderFor(User::factory()->create(), [
            'status' => 'processing',
            'fulfillment_status' => 'processing',
            'processing_at' => now(),
        ]);

        $this->withToken($admin->createToken('admin-api')->plainTextToken)
            ->patchJson("/api/v1/orders/{$order->id}/status", [
                'status' => 'packed',
                'note' => 'Packed by warehouse.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'packed')
            ->assertJsonPath('data.fulfillment_status', 'packed');

        $order->refresh();

        $this->assertSame('packed', $order->status);
        $this->assertSame('packed', $order->fulfillment_status);
        $this->assertNotNull($order->packed_at);
    }

    public function test_admin_cannot_skip_blocked_order_status_transition(): void
    {
        $admin = $this->adminWithOrderPermissions(['orders.view', 'orders.update_status']);
        $order = $this->orderFor(User::factory()->create());

        $this->withToken($admin->createToken('admin-api')->plainTextToken)
            ->patchJson("/api/v1/orders/{$order->id}/status", [
                'status' => 'delivered',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.status.0', 'Order cannot transition from pending to delivered.');

        $this->assertSame('pending', $order->refresh()->status);
        $this->assertSame(0, OrderStatusHistory::query()->count());
        $this->assertSame(0, AuditEvent::query()->where('event', 'order.status.changed')->count());
    }

    public function test_admin_can_view_order_with_lifecycle_and_history(): void
    {
        $admin = $this->adminWithOrderPermission('orders.view');
        $order = $this->orderFor(User::factory()->create());
        $order->statusHistories()->create([
            'to_status' => 'pending',
            'note' => 'Order created.',
        ]);

        $this->withToken($admin->createToken('admin-api')->plainTextToken)
            ->getJson("/api/v1/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.status_history.0.to_status', 'pending')
            ->assertJsonStructure([
                'data' => [
                    'lifecycle' => [
                        'placed_at',
                        'confirmed_at',
                        'processing_at',
                        'packed_at',
                        'shipped_at',
                        'delivered_at',
                        'cancelled_at',
                    ],
                ],
            ]);
    }

    public function test_admin_can_view_order_list_screen(): void
    {
        $admin = $this->adminWithOrderPermission('orders.view');

        $this->actingAs($admin)
            ->get('/admin/orders')
            ->assertOk()
            ->assertSee('Orders')
            ->assertSee('order-filter-form')
            ->assertSee('order-table-body');
    }

    public function test_admin_can_filter_order_list_data(): void
    {
        $admin = $this->adminWithOrderPermission('orders.view');
        $matchingCustomer = User::factory()->create([
            'name' => 'Amina Rahman',
            'email' => 'amina@example.com',
        ]);
        $otherCustomer = User::factory()->create([
            'name' => 'Farhan Ahmed',
            'email' => 'farhan@example.com',
        ]);
        $matching = $this->orderFor($matchingCustomer, [
            'order_number' => 'ORD-FILTER-1001',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'fulfillment_status' => 'processing',
        ]);
        $this->orderFor($otherCustomer, [
            'order_number' => 'ORD-FILTER-2002',
            'status' => 'pending',
            'payment_status' => 'pending',
            'fulfillment_status' => 'unfulfilled',
        ]);

        $this->actingAs($admin)
            ->getJson('/admin/orders/data?search=amina&status=confirmed&payment_status=paid&fulfillment_status=processing')
            ->assertOk()
            ->assertJsonPath('message', 'Orders fetched successfully.')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matching->id)
            ->assertJsonPath('data.0.customer.email', 'amina@example.com');
    }

    public function test_admin_order_web_routes_require_permissions(): void
    {
        foreach (['admin.orders.index', 'admin.orders.data', 'admin.orders.show', 'admin.orders.show.data'] as $routeName) {
            $middleware = $this->middlewareFor($routeName);

            $this->assertRouteHasMiddleware($middleware, 'auth');
            $this->assertRouteHasMiddleware($middleware, 'admin');
            $this->assertRouteHasMiddleware($middleware, 'can:orders.view');
        }

        $this->assertRouteHasMiddleware($this->middlewareFor('admin.orders.status.update'), 'can:orders.update_status');
        $this->assertRouteHasMiddleware($this->middlewareFor('admin.orders.cancel'), 'can:orders.cancel');
        $this->assertRouteHasMiddleware($this->middlewareFor('admin.orders.return-requests.store'), 'can:orders.returns.create');
        $this->assertRouteHasMiddleware($this->middlewareFor('admin.orders.shipments.store'), 'can:orders.shipments.create');
        $this->assertRouteHasMiddleware($this->middlewareFor('admin.orders.invoice.generate'), 'can:orders.invoices.generate');
        $this->assertRouteHasMiddleware($this->middlewareFor('admin.orders.packing-slip.generate'), 'can:orders.packing_slips.generate');
        $this->assertRouteHasMiddleware($this->middlewareFor('admin.orders.invoice.print'), 'can:orders.invoices.generate');
        $this->assertRouteHasMiddleware($this->middlewareFor('admin.orders.packing-slip.print'), 'can:orders.packing_slips.generate');
    }

    public function test_admin_can_view_order_detail_screen(): void
    {
        $admin = $this->adminWithOrderPermissions([
            'orders.view',
            'orders.update_status',
            'orders.cancel',
            'orders.returns.create',
            'orders.shipments.create',
            'orders.invoices.generate',
            'orders.packing_slips.generate',
            'payments.update',
            'payments.cod.collect',
        ]);
        $order = $this->orderFor(User::factory()->create());

        $this->actingAs($admin)
            ->get("/admin/orders/{$order->id}")
            ->assertOk()
            ->assertSee('Order Detail')
            ->assertSee('order-summary-panel')
            ->assertSee('order-timeline-panel')
            ->assertSee('order-status-form')
            ->assertSee('order-items-table-body')
            ->assertSee('order-customer-panel')
            ->assertSee('order-cancel-form')
            ->assertSee('order-return-form')
            ->assertSee('order-shipment-form')
            ->assertSee('order-return-requests-panel')
            ->assertSee('order-shipments-panel')
            ->assertSee('order-documents-panel')
            ->assertSee('generate-invoice')
            ->assertSee('generate-packing-slip')
            ->assertSee('payment-controls-panel');
    }

    public function test_admin_can_fetch_order_detail_data_from_web_route(): void
    {
        $admin = $this->adminWithOrderPermission('orders.view');
        $order = $this->orderFor(User::factory()->create());
        $order->statusHistories()->create([
            'to_status' => 'pending',
            'note' => 'Order created.',
        ]);
        $this->reservedOrderItem($order, 1, 1);

        $this->actingAs($admin)
            ->getJson("/admin/orders/{$order->id}/data")
            ->assertOk()
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.status_history.0.to_status', 'pending')
            ->assertJsonPath('data.items.0.quantity', 1);
    }

    public function test_admin_can_update_order_status_from_web_route(): void
    {
        $admin = $this->adminWithOrderPermissions(['orders.view', 'orders.update_status']);
        $order = $this->orderFor(User::factory()->create());

        $this->actingAs($admin)
            ->patchJson("/admin/orders/{$order->id}/status", [
                'status' => 'confirmed',
                'note' => 'Checked from admin screen.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmed')
            ->assertJsonPath('data.status_history.0.to_status', 'confirmed')
            ->assertJsonPath('data.status_history.0.actor.id', $admin->id);

        $this->assertSame('confirmed', $order->refresh()->status);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'actor_id' => $admin->id,
            'to_status' => 'confirmed',
            'note' => 'Checked from admin screen.',
        ]);
    }

    public function test_admin_can_cancel_order_from_web_route(): void
    {
        $admin = $this->adminWithOrderPermission('orders.cancel');
        $order = $this->orderFor(User::factory()->create());
        [, $stock] = $this->reservedOrderItem($order, quantity: 1, reserved: 1);

        $this->actingAs($admin)
            ->postJson("/admin/orders/{$order->id}/cancel", [
                'reason' => 'Customer requested cancellation from admin screen.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertSame('cancelled', $order->refresh()->status);
        $this->assertSame(0, $stock->refresh()->quantity_reserved);
    }

    public function test_admin_can_create_return_request_from_web_route(): void
    {
        $admin = $this->adminWithOrderPermission('orders.returns.create');
        $order = $this->orderFor(User::factory()->create(), [
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);

        $this->actingAs($admin)
            ->postJson("/admin/orders/{$order->id}/return-requests", [
                'reason' => 'Customer reported damaged item from admin screen.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.reason', 'Customer reported damaged item from admin screen.')
            ->assertJsonPath('data.requested_by.id', $admin->id);

        $this->assertSame('return_requested', $order->refresh()->status);
    }

    public function test_admin_can_link_shipment_from_web_route(): void
    {
        $admin = $this->adminWithOrderPermission('orders.shipments.create');
        $order = $this->orderFor(User::factory()->create(), [
            'status' => 'packed',
            'packed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->postJson("/admin/orders/{$order->id}/shipments", [
                'carrier_name' => 'Pathao',
                'tracking_number' => 'TRK-WEB-1',
                'tracking_url' => 'https://example.com/track/TRK-WEB-1',
                'status' => 'shipped',
            ])
            ->assertCreated()
            ->assertJsonPath('data.carrier_name', 'Pathao')
            ->assertJsonPath('data.tracking_number', 'TRK-WEB-1')
            ->assertJsonPath('data.created_by.id', $admin->id);

        $this->assertSame('shipped', $order->refresh()->status);
    }

    public function test_admin_can_generate_invoice_from_web_route(): void
    {
        $admin = $this->adminWithOrderPermission('orders.invoices.generate');
        $order = $this->orderFor(User::factory()->create(), [
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->postJson("/admin/orders/{$order->id}/invoice")
            ->assertCreated()
            ->assertJsonPath('data.status', 'issued')
            ->assertJsonPath('data.issued_by.id', $admin->id);

        $this->assertSame(1, OrderInvoice::query()->where('order_id', $order->id)->count());
    }

    public function test_admin_can_generate_packing_slip_from_web_route(): void
    {
        $admin = $this->adminWithOrderPermission('orders.packing_slips.generate');
        $order = $this->orderFor(User::factory()->create(), [
            'status' => 'processing',
            'processing_at' => now(),
        ]);
        $this->reservedOrderItem($order, quantity: 2, reserved: 2);

        $this->actingAs($admin)
            ->postJson("/admin/orders/{$order->id}/packing-slip")
            ->assertCreated()
            ->assertJsonPath('data.status', 'generated')
            ->assertJsonPath('data.generated_by.id', $admin->id);

        $this->assertSame(1, OrderPackingSlip::query()->where('order_id', $order->id)->count());
    }

    public function test_admin_can_view_printable_invoice(): void
    {
        $admin = $this->adminWithOrderPermission('orders.invoices.generate');
        $order = $this->orderFor(User::factory()->create(), [
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);
        $this->reservedOrderItem($order, quantity: 1, reserved: 1);
        $invoice = app(\App\Modules\Order\Services\OrderLifecycleService::class)->generateInvoice($order, $admin);

        $this->actingAs($admin)
            ->get("/admin/orders/{$order->id}/invoice/print")
            ->assertOk()
            ->assertSee('Invoice')
            ->assertSee($invoice->invoice_number)
            ->assertSee('Print / Save PDF')
            ->assertSee($order->order_number);
    }

    public function test_admin_can_view_printable_packing_slip(): void
    {
        $admin = $this->adminWithOrderPermission('orders.packing_slips.generate');
        $order = $this->orderFor(User::factory()->create(), [
            'status' => 'processing',
            'processing_at' => now(),
        ]);
        $this->reservedOrderItem($order, quantity: 2, reserved: 2);
        $packingSlip = app(\App\Modules\Order\Services\OrderLifecycleService::class)->generatePackingSlip($order, $admin);

        $this->actingAs($admin)
            ->get("/admin/orders/{$order->id}/packing-slip/print")
            ->assertOk()
            ->assertSee('Packing Slip')
            ->assertSee($packingSlip->packing_slip_number)
            ->assertSee('Print / Save PDF')
            ->assertSee($order->order_number);
    }

    public function test_admin_can_add_internal_order_note(): void
    {
        $admin = $this->adminWithOrderPermission('orders.notes.create');
        $order = $this->orderFor(User::factory()->create());

        $this->withToken($admin->createToken('admin-api')->plainTextToken)
            ->postJson("/api/v1/orders/{$order->id}/notes", [
                'note' => 'Customer asked for delivery after 6 PM.',
                'metadata' => ['source' => 'phone'],
            ])
            ->assertCreated()
            ->assertJsonPath('data.note', 'Customer asked for delivery after 6 PM.')
            ->assertJsonPath('data.author.id', $admin->id)
            ->assertJsonPath('data.metadata.source', 'phone');

        $this->assertSame(1, OrderNote::query()->count());
        $this->assertDatabaseHas('order_notes', [
            'order_id' => $order->id,
            'author_id' => $admin->id,
            'note' => 'Customer asked for delivery after 6 PM.',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'event' => 'order.note.created',
            'actor_id' => $admin->id,
            'auditable_id' => $order->id,
        ]);
    }

    public function test_admin_can_cancel_order_and_release_reserved_stock(): void
    {
        $admin = $this->adminWithOrderPermission('orders.cancel');
        $order = $this->orderFor(User::factory()->create());
        [$variant, $stock] = $this->reservedOrderItem($order, quantity: 2, reserved: 2);

        $this->withToken($admin->createToken('admin-api')->plainTextToken)
            ->postJson("/api/v1/orders/{$order->id}/cancel", [
                'reason' => 'Customer requested cancellation.',
                'metadata' => ['channel' => 'support'],
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.fulfillment_status', 'cancelled')
            ->assertJsonPath('data.status_history.0.from_status', 'pending')
            ->assertJsonPath('data.status_history.0.to_status', 'cancelled');

        $order->refresh();

        $this->assertSame('cancelled', $order->status);
        $this->assertSame('cancelled', $order->fulfillment_status);
        $this->assertNotNull($order->cancelled_at);
        $this->assertSame(0, $stock->refresh()->quantity_reserved);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'actor_id' => $admin->id,
            'from_status' => 'pending',
            'to_status' => 'cancelled',
            'note' => 'Customer requested cancellation.',
        ]);
        $this->assertDatabaseHas('inventory_transactions', [
            'inventory_stock_id' => $stock->id,
            'product_variant_id' => $variant->id,
            'actor_id' => $admin->id,
            'type' => 'release',
            'quantity_delta' => 2,
            'quantity_reserved_after' => 0,
            'note' => 'Order cancelled.',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'event' => 'order.cancelled',
            'actor_id' => $admin->id,
            'auditable_id' => $order->id,
        ]);
    }

    public function test_shipped_order_cannot_be_cancelled_by_normal_workflow(): void
    {
        $admin = $this->adminWithOrderPermission('orders.cancel');
        $order = $this->orderFor(User::factory()->create(), [
            'status' => 'shipped',
            'shipped_at' => now(),
        ]);
        [, $stock] = $this->reservedOrderItem($order, quantity: 1, reserved: 1);

        $this->withToken($admin->createToken('admin-api')->plainTextToken)
            ->postJson("/api/v1/orders/{$order->id}/cancel", [
                'reason' => 'Too late cancellation request.',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.status.0', 'Order cannot transition from shipped to cancelled.');

        $this->assertSame('shipped', $order->refresh()->status);
        $this->assertSame(1, $stock->refresh()->quantity_reserved);
        $this->assertSame(0, OrderStatusHistory::query()->count());
    }

    public function test_admin_can_create_return_request_for_delivered_order(): void
    {
        $admin = $this->adminWithOrderPermission('orders.returns.create');
        $order = $this->orderFor(User::factory()->create(), [
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);

        $this->withToken($admin->createToken('admin-api')->plainTextToken)
            ->postJson("/api/v1/orders/{$order->id}/return-requests", [
                'reason' => 'Customer reported damaged item.',
                'metadata' => ['channel' => 'support'],
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'requested')
            ->assertJsonPath('data.reason', 'Customer reported damaged item.')
            ->assertJsonPath('data.requested_by.id', $admin->id);

        $order->refresh();

        $this->assertSame('return_requested', $order->status);
        $this->assertNotNull($order->return_requested_at);
        $this->assertSame(1, OrderReturnRequest::query()->count());
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'actor_id' => $admin->id,
            'from_status' => 'delivered',
            'to_status' => 'return_requested',
            'note' => 'Customer reported damaged item.',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'event' => 'order.return.requested',
            'actor_id' => $admin->id,
            'auditable_id' => $order->id,
        ]);
    }

    public function test_return_request_requires_delivered_order(): void
    {
        $admin = $this->adminWithOrderPermission('orders.returns.create');
        $order = $this->orderFor(User::factory()->create());

        $this->withToken($admin->createToken('admin-api')->plainTextToken)
            ->postJson("/api/v1/orders/{$order->id}/return-requests", [
                'reason' => 'Customer changed mind.',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.status.0', 'Only delivered orders can receive a return request.');

        $this->assertSame('pending', $order->refresh()->status);
        $this->assertSame(0, OrderReturnRequest::query()->count());
    }

    public function test_admin_can_generate_invoice_once_per_order(): void
    {
        $admin = $this->adminWithOrderPermission('orders.invoices.generate');
        $order = $this->orderFor(User::factory()->create(), [
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);
        $token = $admin->createToken('admin-api')->plainTextToken;

        $invoiceId = $this->withToken($token)
            ->postJson("/api/v1/orders/{$order->id}/invoice", [
                'metadata' => ['printed_by' => 'front-desk'],
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'issued')
            ->assertJsonPath('data.totals.grand_total', '160.00')
            ->assertJsonPath('data.issued_by.id', $admin->id)
            ->json('data.id');

        $this->withToken($token)
            ->postJson("/api/v1/orders/{$order->id}/invoice")
            ->assertOk()
            ->assertJsonPath('data.id', $invoiceId);

        $this->assertSame(1, OrderInvoice::query()->count());
        $this->assertDatabaseHas('order_invoices', [
            'order_id' => $order->id,
            'issued_by_id' => $admin->id,
            'status' => 'issued',
            'grand_total' => '160.00',
            'currency' => 'BDT',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'event' => 'order.invoice.generated',
            'actor_id' => $admin->id,
            'auditable_id' => $order->id,
        ]);
    }

    public function test_cancelled_order_cannot_receive_invoice(): void
    {
        $admin = $this->adminWithOrderPermission('orders.invoices.generate');
        $order = $this->orderFor(User::factory()->create(), [
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        $this->withToken($admin->createToken('admin-api')->plainTextToken)
            ->postJson("/api/v1/orders/{$order->id}/invoice")
            ->assertUnprocessable()
            ->assertJsonPath('errors.status.0', 'Cancelled orders cannot receive an invoice.');

        $this->assertSame(0, OrderInvoice::query()->count());
    }

    public function test_admin_can_generate_packing_slip_once_per_order(): void
    {
        $admin = $this->adminWithOrderPermission('orders.packing_slips.generate');
        $order = $this->orderFor(User::factory()->create(), [
            'status' => 'processing',
            'processing_at' => now(),
        ]);
        $this->reservedOrderItem($order, quantity: 2, reserved: 2);
        $token = $admin->createToken('admin-api')->plainTextToken;

        $packingSlipId = $this->withToken($token)
            ->postJson("/api/v1/orders/{$order->id}/packing-slip", [
                'metadata' => ['station' => 'A1'],
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'generated')
            ->assertJsonPath('data.items.0.quantity', 2)
            ->assertJsonPath('data.generated_by.id', $admin->id)
            ->json('data.id');

        $this->withToken($token)
            ->postJson("/api/v1/orders/{$order->id}/packing-slip")
            ->assertOk()
            ->assertJsonPath('data.id', $packingSlipId);

        $this->assertSame(1, OrderPackingSlip::query()->count());
        $this->assertDatabaseHas('order_packing_slips', [
            'order_id' => $order->id,
            'generated_by_id' => $admin->id,
            'status' => 'generated',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'event' => 'order.packing_slip.generated',
            'actor_id' => $admin->id,
            'auditable_id' => $order->id,
        ]);
    }

    public function test_cancelled_order_cannot_receive_packing_slip(): void
    {
        $admin = $this->adminWithOrderPermission('orders.packing_slips.generate');
        $order = $this->orderFor(User::factory()->create(), [
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        $this->withToken($admin->createToken('admin-api')->plainTextToken)
            ->postJson("/api/v1/orders/{$order->id}/packing-slip")
            ->assertUnprocessable()
            ->assertJsonPath('errors.status.0', 'Cancelled orders cannot receive a packing slip.');

        $this->assertSame(0, OrderPackingSlip::query()->count());
    }

    public function test_admin_can_link_pending_shipment_to_order(): void
    {
        $admin = $this->adminWithOrderPermission('orders.shipments.create');
        $order = $this->orderFor(User::factory()->create(), [
            'status' => 'packed',
            'packed_at' => now(),
        ]);

        $this->withToken($admin->createToken('admin-api')->plainTextToken)
            ->postJson("/api/v1/orders/{$order->id}/shipments", [
                'carrier_name' => 'Pathao',
                'tracking_number' => 'TRK123',
                'tracking_url' => 'https://example.com/track/TRK123',
                'metadata' => ['pickup_slot' => 'morning'],
            ])
            ->assertCreated()
            ->assertJsonPath('data.carrier_name', 'Pathao')
            ->assertJsonPath('data.tracking_number', 'TRK123')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.created_by.id', $admin->id);

        $this->assertSame('packed', $order->refresh()->status);
        $this->assertSame(1, OrderShipment::query()->count());
        $this->assertDatabaseHas('audit_events', [
            'event' => 'order.shipment.linked',
            'actor_id' => $admin->id,
            'auditable_id' => $order->id,
        ]);
    }

    public function test_shipped_shipment_moves_packed_order_to_shipped(): void
    {
        $admin = $this->adminWithOrderPermission('orders.shipments.create');
        $order = $this->orderFor(User::factory()->create(), [
            'status' => 'packed',
            'packed_at' => now(),
        ]);

        $this->withToken($admin->createToken('admin-api')->plainTextToken)
            ->postJson("/api/v1/orders/{$order->id}/shipments", [
                'carrier_name' => 'RedX',
                'tracking_number' => 'SHIP123',
                'status' => 'shipped',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'shipped')
            ->assertJsonPath('data.tracking_number', 'SHIP123');

        $order->refresh();

        $this->assertSame('shipped', $order->status);
        $this->assertSame('shipped', $order->fulfillment_status);
        $this->assertNotNull($order->shipped_at);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'actor_id' => $admin->id,
            'from_status' => 'packed',
            'to_status' => 'shipped',
            'note' => 'Shipment linked.',
        ]);
    }

    public function test_cancelled_order_cannot_receive_shipment(): void
    {
        $admin = $this->adminWithOrderPermission('orders.shipments.create');
        $order = $this->orderFor(User::factory()->create(), [
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        $this->withToken($admin->createToken('admin-api')->plainTextToken)
            ->postJson("/api/v1/orders/{$order->id}/shipments", [
                'carrier_name' => 'RedX',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.status.0', 'This order cannot receive a shipment.');

        $this->assertSame(0, OrderShipment::query()->count());
    }

    private function adminWithOrderPermission(string $permission): User
    {
        return $this->adminWithOrderPermissions([$permission]);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function adminWithOrderPermissions(array $permissions): User
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);

        $admin->givePermissionTo($permissions);

        return $admin;
    }

    private function orderFor(User $customer, array $overrides = []): Order
    {
        return Order::query()->create(array_merge([
            'order_number' => 'ORD-TEST-'.strtoupper(uniqid()),
            'user_id' => $customer->id,
            'idempotency_key' => 'order-life-'.uniqid(),
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

    /**
     * @return array{0: ProductVariant, 1: InventoryStock, 2: OrderItem}
     */
    private function reservedOrderItem(Order $order, int $quantity, int $reserved): array
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => 'Default',
            'sku' => 'ORDER-CANCEL-'.strtoupper(uniqid()),
            'price' => 100,
            'is_default' => true,
            'status' => 'active',
        ]);
        $stock = InventoryStock::query()->create([
            'product_variant_id' => $variant->id,
            'quantity_on_hand' => 10,
            'quantity_reserved' => $reserved,
        ]);
        $item = $order->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_name' => $product->name,
            'variant_name' => $variant->name,
            'sku' => $variant->sku,
            'quantity' => $quantity,
            'unit_price' => '100.00',
            'line_total' => number_format($quantity * 100, 2, '.', ''),
            'snapshot' => ['source' => 'test'],
        ]);

        return [$variant, $stock, $item];
    }
}
