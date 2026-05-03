<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CustomerAddress;
use App\Models\DeliveryRate;
use App\Models\DeliveryZone;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutShippingPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_validation_resolves_default_shipping_and_payment(): void
    {
        [$customer, $token] = $this->customerToken();
        $address = $this->addressFor($customer);
        $this->addCartItem($token, price: 100, quantity: 2);

        $this->withToken($token)
            ->postJson('/api/v1/checkout/validate', [
                'shipping_address_id' => $address->id,
                'billing_same_as_shipping' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.steps.shipping_method', 'passed')
            ->assertJsonPath('data.steps.payment_method', 'passed')
            ->assertJsonPath('data.shipping_method.code', 'standard')
            ->assertJsonPath('data.shipping_method.amount', '80.00')
            ->assertJsonPath('data.payment_method.code', 'cod')
            ->assertJsonPath('data.totals.subtotal', '200.00')
            ->assertJsonPath('data.totals.shipping_total', '80.00')
            ->assertJsonPath('data.totals.grand_total', '280.00')
            ->assertJsonPath('data.totals.currency', 'BDT');
    }

    public function test_checkout_validation_resolves_selected_shipping_and_payment_methods(): void
    {
        [$customer, $token] = $this->customerToken();
        $address = $this->addressFor($customer);
        $this->addCartItem($token, price: 125, quantity: 2);

        $this->withToken($token)
            ->postJson('/api/v1/checkout/validate', [
                'shipping_address_id' => $address->id,
                'billing_same_as_shipping' => true,
                'shipping_method' => 'express',
                'payment_method' => 'manual_bank',
            ])
            ->assertOk()
            ->assertJsonPath('data.shipping_method.code', 'express')
            ->assertJsonPath('data.shipping_method.amount', '150.00')
            ->assertJsonPath('data.payment_method.code', 'manual_bank')
            ->assertJsonPath('data.totals.subtotal', '250.00')
            ->assertJsonPath('data.totals.shipping_total', '150.00')
            ->assertJsonPath('data.totals.grand_total', '400.00');
    }

    public function test_checkout_validation_rejects_unknown_shipping_and_payment_methods(): void
    {
        [$customer, $token] = $this->customerToken();
        $address = $this->addressFor($customer);
        $this->addCartItem($token);

        $this->withToken($token)
            ->postJson('/api/v1/checkout/validate', [
                'shipping_address_id' => $address->id,
                'billing_same_as_shipping' => true,
                'shipping_method' => 'drone',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.shipping_method.0', 'Selected shipping method is not available.');

        $this->withToken($token)
            ->postJson('/api/v1/checkout/validate', [
                'shipping_address_id' => $address->id,
                'billing_same_as_shipping' => true,
                'payment_method' => 'crypto',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.payment_method.0', 'Selected payment method is not available.');
    }

    public function test_checkout_validation_rejects_shipping_method_for_unsupported_country(): void
    {
        [$customer, $token] = $this->customerToken();
        $address = $this->addressFor($customer, ['country' => 'US']);
        $this->addCartItem($token);

        $this->withToken($token)
            ->postJson('/api/v1/checkout/validate', [
                'shipping_address_id' => $address->id,
                'billing_same_as_shipping' => true,
                'shipping_method' => 'standard',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.shipping_method.0', 'Selected shipping method is not available for this address.');
    }

    public function test_checkout_validation_uses_admin_configured_delivery_rate(): void
    {
        [$customer, $token] = $this->customerToken();
        $address = $this->addressFor($customer, ['city' => 'Dhaka']);
        $this->addCartItem($token, price: 150);
        $zone = DeliveryZone::query()->create([
            'name' => 'Dhaka City',
            'code' => 'dhaka-city',
            'country' => 'BD',
            'cities' => ['Dhaka'],
            'status' => 'active',
        ]);
        DeliveryRate::query()->create([
            'delivery_zone_id' => $zone->id,
            'name' => 'Inside Dhaka',
            'code' => 'inside-dhaka',
            'amount' => 60,
            'status' => 'active',
        ]);

        $this->withToken($token)
            ->postJson('/api/v1/checkout/validate', [
                'shipping_address_id' => $address->id,
                'billing_same_as_shipping' => true,
                'shipping_method' => 'inside-dhaka',
            ])
            ->assertOk()
            ->assertJsonPath('data.shipping_method.code', 'inside-dhaka')
            ->assertJsonPath('data.shipping_method.amount', '60.00')
            ->assertJsonPath('data.shipping_method.zone.code', 'dhaka-city')
            ->assertJsonPath('data.totals.subtotal', '150.00')
            ->assertJsonPath('data.totals.shipping_total', '60.00')
            ->assertJsonPath('data.totals.grand_total', '210.00');
    }

    public function test_checkout_validation_treats_all_delivery_zone_lists_as_wildcards(): void
    {
        [$customer, $token] = $this->customerToken();
        $address = $this->addressFor($customer, ['city' => 'Dhaka']);
        $this->addCartItem($token, price: 150);
        $zone = DeliveryZone::query()->create([
            'name' => 'All Bangladesh',
            'code' => 'all-bd',
            'country' => 'BD',
            'cities' => ['ALL'],
            'states' => ['ALL'],
            'postal_codes' => ['ALL'],
            'status' => 'active',
        ]);
        DeliveryRate::query()->create([
            'delivery_zone_id' => $zone->id,
            'name' => 'Standard Delivery',
            'code' => 'standard',
            'amount' => 50,
            'status' => 'active',
        ]);

        $this->withToken($token)
            ->postJson('/api/v1/checkout/validate', [
                'shipping_address_id' => $address->id,
                'billing_same_as_shipping' => true,
                'shipping_method' => 'standard',
            ])
            ->assertOk()
            ->assertJsonPath('data.shipping_method.code', 'standard')
            ->assertJsonPath('data.totals.shipping_total', '50.00')
            ->assertJsonPath('data.totals.grand_total', '200.00');
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

    private function addCartItem(string $token, float $price = 100, int $quantity = 1): void
    {
        $variant = $this->sellableVariant($price);

        $this->withToken($token)
            ->postJson('/api/v1/customer/cart/items', [
                'product_variant_id' => $variant->id,
                'quantity' => $quantity,
            ])
            ->assertOk();
    }

    private function sellableVariant(float $price): ProductVariant
    {
        $category = Category::factory()->create(['status' => 'active']);
        $slug = 'checkout-shipping-product-'.uniqid();
        $product = Product::factory()->create([
            'name' => 'Checkout Shipping Product',
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
            'sku' => 'SHIP-'.strtoupper(uniqid()),
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
}
