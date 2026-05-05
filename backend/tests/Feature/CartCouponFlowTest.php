<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\CustomerAddress;
use App\Models\InventoryStock;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartCouponFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_apply_and_remove_coupon(): void
    {
        $variant = $this->sellableVariant(200);
        $coupon = $this->coupon(['discount_type' => Coupon::DISCOUNT_FIXED, 'discount_value' => 50]);
        $token = $this->postJson('/api/v1/cart/guest')->json('data.cart_token');

        $this->postJson('/api/v1/cart/guest/'.$token.'/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertOk();

        $this->postJson('/api/v1/cart/guest/'.$token.'/coupon', [
            'code' => strtolower($coupon->code),
        ])
            ->assertOk()
            ->assertJsonPath('data.coupon.code', $coupon->code)
            ->assertJsonPath('data.discount_total', '50.00')
            ->assertJsonPath('data.grand_total', '150.00');

        $this->deleteJson('/api/v1/cart/guest/'.$token.'/coupon')
            ->assertOk()
            ->assertJsonPath('data.coupon', null)
            ->assertJsonPath('data.discount_total', '0.00')
            ->assertJsonPath('data.grand_total', '200.00');
    }

    public function test_customer_cart_rejects_ineligible_coupon(): void
    {
        [, $token] = $this->customerToken();
        $this->addCustomerCartItem($token, 200);
        $coupon = $this->coupon([
            'code' => 'MINIMUM500',
            'minimum_order_value' => 500,
        ]);

        $this->withToken($token)
            ->postJson('/api/v1/customer/cart/coupon', ['code' => $coupon->code])
            ->assertUnprocessable()
            ->assertJsonPath('errors.code.0', 'minimum_order_value_not_met');
    }

    public function test_customer_cart_percentage_coupon_updates_totals(): void
    {
        [, $token] = $this->customerToken();
        $this->addCustomerCartItem($token, 300, 2);
        $coupon = $this->coupon([
            'code' => 'PERCENT10',
            'discount_type' => Coupon::DISCOUNT_PERCENTAGE,
            'discount_value' => 10,
        ]);

        $this->withToken($token)
            ->postJson('/api/v1/customer/cart/coupon', ['code' => $coupon->code])
            ->assertOk()
            ->assertJsonPath('data.subtotal', '600.00')
            ->assertJsonPath('data.discount_total', '60.00')
            ->assertJsonPath('data.grand_total', '540.00');
    }

    public function test_checkout_totals_include_applied_coupon_discount(): void
    {
        [$customer, $token] = $this->customerToken();
        $address = $this->addressFor($customer);
        $this->addCustomerCartItem($token, 250, 2);
        $coupon = $this->coupon([
            'code' => 'CHECKOUT100',
            'discount_type' => Coupon::DISCOUNT_FIXED,
            'discount_value' => 100,
        ]);

        $this->withToken($token)
            ->postJson('/api/v1/customer/cart/coupon', ['code' => $coupon->code])
            ->assertOk();

        $this->withToken($token)
            ->postJson('/api/v1/checkout/validate', [
                'shipping_address_id' => $address->id,
                'billing_same_as_shipping' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.totals.subtotal', '500.00')
            ->assertJsonPath('data.totals.shipping_total', '80.00')
            ->assertJsonPath('data.totals.discount_total', '100.00')
            ->assertJsonPath('data.totals.grand_total', '480.00');
    }

    public function test_checkout_submit_records_coupon_redemption_and_increments_usage_once(): void
    {
        [$customer, $token] = $this->customerToken();
        $address = $this->addressFor($customer);
        $this->addCustomerCartItem($token, 250, 2);
        $coupon = $this->coupon([
            'code' => 'REDEEM100',
            'discount_type' => Coupon::DISCOUNT_FIXED,
            'discount_value' => 100,
            'usage_limit' => 3,
            'per_customer_usage_limit' => 2,
        ]);
        $payload = [
            'shipping_address_id' => $address->id,
            'billing_same_as_shipping' => true,
            'idempotency_key' => 'coupon-redemption-key',
        ];

        $this->withToken($token)
            ->postJson('/api/v1/customer/cart/coupon', ['code' => $coupon->code])
            ->assertOk();

        $orderId = $this->withToken($token)
            ->postJson('/api/v1/checkout/submit', $payload)
            ->assertCreated()
            ->assertJsonPath('data.coupon.code', $coupon->code)
            ->assertJsonPath('data.totals.discount_total', '100.00')
            ->json('data.id');

        $this->assertDatabaseHas('coupon_redemptions', [
            'coupon_id' => $coupon->id,
            'order_id' => $orderId,
            'user_id' => $customer->id,
            'discount_amount' => '100.00',
        ]);
        $this->assertSame(1, $coupon->refresh()->used_count);

        $this->withToken($token)
            ->postJson('/api/v1/checkout/submit', $payload)
            ->assertOk()
            ->assertJsonPath('data.id', $orderId);

        $this->assertSame(1, CouponRedemption::query()->where('coupon_id', $coupon->id)->count());
        $this->assertSame(1, $coupon->refresh()->used_count);
    }

    public function test_checkout_submit_rechecks_coupon_usage_limit_inside_transaction(): void
    {
        [$customer, $token] = $this->customerToken();
        $address = $this->addressFor($customer);
        $this->addCustomerCartItem($token, 250, 2);
        $coupon = $this->coupon([
            'code' => 'LIMITED1',
            'discount_type' => Coupon::DISCOUNT_FIXED,
            'discount_value' => 100,
            'usage_limit' => 1,
            'used_count' => 0,
        ]);

        $this->withToken($token)
            ->postJson('/api/v1/customer/cart/coupon', ['code' => $coupon->code])
            ->assertOk();

        $coupon->update(['used_count' => 1]);

        $this->withToken($token)
            ->postJson('/api/v1/checkout/submit', [
                'shipping_address_id' => $address->id,
                'billing_same_as_shipping' => true,
                'idempotency_key' => 'coupon-limit-key',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.coupon.0', 'coupon_usage_limit_reached');

        $this->assertSame(0, Order::query()->count());
        $this->assertSame(0, CouponRedemption::query()->count());
        $this->assertSame(1, $coupon->refresh()->used_count);
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

    private function addCustomerCartItem(string $token, float $price, int $quantity = 1): ProductVariant
    {
        $variant = $this->sellableVariant($price);

        $this->withToken($token)
            ->postJson('/api/v1/customer/cart/items', [
                'product_variant_id' => $variant->id,
                'quantity' => $quantity,
            ])
            ->assertOk();

        return $variant;
    }

    private function sellableVariant(float $price): ProductVariant
    {
        $category = Category::factory()->create(['status' => 'active']);
        $slug = 'cart-coupon-product-'.uniqid();
        $product = Product::factory()->create([
            'name' => 'Cart Coupon Product',
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
            'sku' => 'CART-COUPON-'.strtoupper(uniqid()),
            'price' => $price,
            'is_default' => true,
            'status' => 'active',
        ]);

        InventoryStock::query()->create([
            'product_variant_id' => $variant->id,
            'quantity_on_hand' => 10,
            'quantity_reserved' => 0,
        ]);

        return $variant;
    }

    private function coupon(array $overrides = []): Coupon
    {
        return Coupon::query()->create(array_merge([
            'name' => 'Cart Coupon',
            'code' => 'CART'.strtoupper(uniqid()),
            'discount_type' => Coupon::DISCOUNT_FIXED,
            'discount_value' => 50,
            'status' => Coupon::STATUS_ACTIVE,
        ], $overrides));
    }

    private function addressFor(User $customer): CustomerAddress
    {
        return $customer->customerAddresses()->create([
            'label' => 'Home',
            'recipient_name' => 'Customer One',
            'phone' => '01700000000',
            'address_line_1' => 'House 10',
            'city' => 'Dhaka',
            'state' => 'Dhaka',
            'postal_code' => '1213',
            'country' => 'BD',
        ]);
    }
}
