<?php

namespace Tests\Feature;

use App\Events\OrderConfirmationGenerated;
use App\Models\AuditEvent;
use App\Models\Cart;
use App\Models\Category;
use App\Models\CustomerAddress;
use App\Models\InventoryStock;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CheckoutOrderCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_tables_exist(): void
    {
        $this->assertTrue(Schema::hasTable('orders'));
        $this->assertTrue(Schema::hasTable('order_items'));
        $this->assertTrue(Schema::hasTable('order_status_histories'));
    }

    public function test_checkout_submit_creates_order_snapshots_and_reserves_stock(): void
    {
        [$customer, $token] = $this->customerToken();
        $address = $this->addressFor($customer);
        [$variant, $stock] = $this->addCartItem($token, price: 125, quantity: 2);

        $this->withToken($token)
            ->postJson('/api/v1/checkout/submit', [
                'shipping_address_id' => $address->id,
                'billing_same_as_shipping' => true,
                'shipping_method' => 'express',
                'payment_method' => 'manual_bank',
                'idempotency_key' => 'checkout-key-1',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.payment_status', 'pending')
            ->assertJsonPath('data.fulfillment_status', 'unfulfilled')
            ->assertJsonPath('data.shipping_method.code', 'express')
            ->assertJsonPath('data.payment_method.code', 'manual_bank')
            ->assertJsonPath('data.totals.subtotal', '250.00')
            ->assertJsonPath('data.totals.shipping_total', '150.00')
            ->assertJsonPath('data.totals.grand_total', '400.00')
            ->assertJsonPath('data.items.0.product_name', 'Checkout Order Product')
            ->assertJsonPath('data.items.0.quantity', 2)
            ->assertJsonPath('data.items.0.unit_price', '125.00');

        $order = Order::query()->where('idempotency_key', 'checkout-key-1')->firstOrFail();

        $this->assertSame($customer->id, $order->user_id);
        $this->assertTrue($customer->orders()->firstOrFail()->is($order));
        $this->assertSame('completed', Cart::query()->findOrFail($order->cart_id)->status);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_variant_id' => $variant->id,
            'sku' => $variant->sku,
            'quantity' => 2,
        ]);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'actor_id' => $customer->id,
            'from_status' => null,
            'to_status' => 'pending',
            'note' => 'Order placed during checkout.',
        ]);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'provider' => 'manual_bank',
            'method_name' => 'Manual Bank Transfer',
            'status' => 'pending',
            'amount' => '400.00',
            'currency' => 'BDT',
        ]);
        $this->assertSame(1, Payment::query()->where('order_id', $order->id)->count());
        $this->assertSame(2, $stock->refresh()->quantity_reserved);
    }

    public function test_checkout_submit_is_idempotent(): void
    {
        [$customer, $token] = $this->customerToken();
        $address = $this->addressFor($customer);
        [, $stock] = $this->addCartItem($token, quantity: 2);
        $payload = [
            'shipping_address_id' => $address->id,
            'billing_same_as_shipping' => true,
            'idempotency_key' => 'same-submit-key',
        ];

        $firstOrderId = $this->withToken($token)
            ->postJson('/api/v1/checkout/submit', $payload)
            ->assertCreated()
            ->json('data.id');

        $this->withToken($token)
            ->postJson('/api/v1/checkout/submit', $payload)
            ->assertOk()
            ->assertJsonPath('data.id', $firstOrderId);

        $this->assertSame(1, Order::query()->count());
        $this->assertSame(1, OrderItem::query()->count());
        $this->assertSame(1, Payment::query()->count());
        $this->assertSame(2, $stock->refresh()->quantity_reserved);
    }

    public function test_order_item_snapshot_does_not_change_after_product_changes(): void
    {
        [$customer, $token] = $this->customerToken();
        $address = $this->addressFor($customer);
        [$variant] = $this->addCartItem($token, price: 100, quantity: 1);

        $this->withToken($token)
            ->postJson('/api/v1/checkout/submit', [
                'shipping_address_id' => $address->id,
                'billing_same_as_shipping' => true,
                'idempotency_key' => 'snapshot-key',
            ])
            ->assertCreated();

        $variant->product->update(['name' => 'Changed Product']);
        $variant->update([
            'name' => 'Changed Variant',
            'sku' => 'CHANGED-SKU',
            'price' => 999,
        ]);

        $item = OrderItem::query()->firstOrFail();

        $this->assertSame('Checkout Order Product', $item->product_name);
        $this->assertSame('Default', $item->variant_name);
        $this->assertNotSame('CHANGED-SKU', $item->sku);
        $this->assertSame('100.00', $item->unit_price);
        $this->assertSame('Checkout Order Product', $item->snapshot['product_name']);
    }

    public function test_checkout_submit_rolls_back_when_validation_fails(): void
    {
        [$customer, $token] = $this->customerToken();
        $address = $this->addressFor($customer, ['country' => 'US']);
        [, $stock] = $this->addCartItem($token, quantity: 2);

        $this->withToken($token)
            ->postJson('/api/v1/checkout/submit', [
                'shipping_address_id' => $address->id,
                'billing_same_as_shipping' => true,
                'shipping_method' => 'standard',
                'idempotency_key' => 'rollback-key',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.shipping_method.0', 'Selected shipping method is not available for this address.');

        $this->assertSame(0, Order::query()->count());
        $this->assertSame(0, OrderItem::query()->count());
        $this->assertSame(0, $stock->refresh()->quantity_reserved);
        $this->assertSame('active', Cart::query()->where('user_id', $customer->id)->firstOrFail()->status);
    }

    public function test_checkout_submit_route_requires_idempotency_key(): void
    {
        [$customer, $token] = $this->customerToken();
        $address = $this->addressFor($customer);
        $this->addCartItem($token);

        $this->withToken($token)
            ->postJson('/api/v1/checkout/submit', [
                'shipping_address_id' => $address->id,
                'billing_same_as_shipping' => true,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.idempotency_key.0', 'The idempotency key field is required.');
    }

    public function test_checkout_submit_generates_order_confirmation_event_once(): void
    {
        Event::fake([OrderConfirmationGenerated::class]);

        [$customer, $token] = $this->customerToken();
        $address = $this->addressFor($customer);
        $this->addCartItem($token, quantity: 1);
        $payload = [
            'shipping_address_id' => $address->id,
            'billing_same_as_shipping' => true,
            'idempotency_key' => 'confirmation-key',
        ];

        $orderId = $this->withToken($token)
            ->postJson('/api/v1/checkout/submit', $payload)
            ->assertCreated()
            ->json('data.id');

        $this->withToken($token)
            ->postJson('/api/v1/checkout/submit', $payload)
            ->assertOk()
            ->assertJsonPath('data.id', $orderId);

        Event::assertDispatchedTimes(OrderConfirmationGenerated::class, 1);
        Event::assertDispatched(
            OrderConfirmationGenerated::class,
            fn (OrderConfirmationGenerated $event): bool => $event->order->id === $orderId
        );

        $order = Order::query()->findOrFail($orderId);
        $auditEvent = AuditEvent::query()
            ->where('event', 'order.confirmation.generated')
            ->firstOrFail();

        $this->assertSame($customer->id, $auditEvent->actor_id);
        $this->assertSame($order->id, $auditEvent->auditable_id);
        $this->assertSame($order->order_number, $auditEvent->metadata['order_number']);
    }

    /**
     * @return array{0: User, 1: string}
     */
    private function customerToken(): array
    {
        $customer = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
        ]);

        return [
            $customer,
            $customer->createToken('storefront', ['customer'], now()->addHour())->plainTextToken,
        ];
    }

    private function addressFor(User $customer, array $overrides = []): CustomerAddress
    {
        return $customer->customerAddresses()->create(array_merge([
            'label' => 'Home',
            'recipient_name' => 'Customer One',
            'phone' => '01700000000',
            'address_line_1' => 'House 10',
            'city' => 'Dhaka',
            'state' => 'Dhaka',
            'postal_code' => '1213',
            'country' => 'BD',
        ], $overrides));
    }

    /**
     * @return array{0: ProductVariant, 1: InventoryStock}
     */
    private function addCartItem(string $token, float $price = 100, int $quantity = 1): array
    {
        [$variant, $stock] = $this->sellableVariant($price);

        $this->withToken($token)
            ->postJson('/api/v1/customer/cart/items', [
                'product_variant_id' => $variant->id,
                'quantity' => $quantity,
            ])
            ->assertOk();

        return [$variant, $stock];
    }

    /**
     * @return array{0: ProductVariant, 1: InventoryStock}
     */
    private function sellableVariant(float $price): array
    {
        $category = Category::factory()->create(['status' => 'active']);
        $slug = 'checkout-order-product-'.uniqid();
        $product = Product::factory()->create([
            'name' => 'Checkout Order Product',
            'slug' => $slug,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $product->categories()->attach($category);
        ProductImage::query()->create([
            'product_id' => $product->id,
            'path' => '/storage/products/'.$slug.'.jpg',
            'is_primary' => true,
        ]);

        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => 'Default',
            'sku' => 'ORDER-'.strtoupper(uniqid()),
            'price' => $price,
            'is_default' => true,
            'status' => 'active',
        ]);

        $stock = InventoryStock::query()->create([
            'product_variant_id' => $variant->id,
            'quantity_on_hand' => 10,
            'quantity_reserved' => 0,
        ]);

        return [$variant->load('product'), $stock];
    }
}
