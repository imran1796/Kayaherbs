<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CustomerAddress;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CheckoutValidationAddressTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_validation_rejects_empty_cart(): void
    {
        [, $token] = $this->customerToken();
        $address = $this->addressFor(User::query()->firstOrFail());

        $this->withToken($token)
            ->postJson('/api/v1/checkout/validate', [
                'shipping_address_id' => $address->id,
                'billing_same_as_shipping' => true,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.cart.0', 'Cart must contain at least one available item.');
    }

    public function test_checkout_validation_accepts_existing_customer_address(): void
    {
        [$customer, $token] = $this->customerToken();
        $address = $this->addressFor($customer);
        $this->addCartItem($token);

        $this->withToken($token)
            ->postJson('/api/v1/checkout/validate', [
                'shipping_address_id' => $address->id,
                'billing_same_as_shipping' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.checkout_ready', true)
            ->assertJsonPath('data.steps.cart', 'passed')
            ->assertJsonPath('data.shipping_address.id', $address->id)
            ->assertJsonPath('data.billing_address.id', $address->id)
            ->assertJsonPath('data.cart.items_count', 2);
    }

    public function test_checkout_validation_can_create_shipping_and_billing_addresses(): void
    {
        [$customer, $token] = $this->customerToken();
        $this->addCartItem($token);

        $this->withToken($token)
            ->postJson('/api/v1/checkout/validate', [
                'shipping_address' => $this->addressPayload(['label' => 'Shipping']),
                'billing_same_as_shipping' => false,
                'billing_address' => $this->addressPayload([
                    'label' => 'Billing',
                    'recipient_name' => 'Billing Person',
                ]),
            ])
            ->assertOk()
            ->assertJsonPath('data.shipping_address.label', 'Shipping')
            ->assertJsonPath('data.billing_address.label', 'Billing')
            ->assertJsonPath('data.billing_address.recipient_name', 'Billing Person');

        $this->assertSame(2, $customer->customerAddresses()->count());
    }

    public function test_checkout_validation_rejects_another_customers_address(): void
    {
        [, $firstToken] = $this->customerToken('first@example.com');
        [$secondCustomer] = $this->customerToken('second@example.com');
        $address = $this->addressFor($secondCustomer);
        $this->addCartItem($firstToken);

        app('auth')->forgetGuards();

        $this->withToken($firstToken)
            ->postJson('/api/v1/checkout/validate', [
                'shipping_address_id' => $address->id,
                'billing_same_as_shipping' => true,
            ])
            ->assertNotFound();
    }

    public function test_checkout_validation_rejects_unavailable_cart_items(): void
    {
        [$customer, $token] = $this->customerToken();
        $address = $this->addressFor($customer);
        [$variant, $stock] = $this->addCartItem($token);

        $stock->update([
            'quantity_on_hand' => 1,
            'quantity_reserved' => 0,
        ]);

        $this->withToken($token)
            ->getJson('/api/v1/customer/cart')
            ->assertOk()
            ->assertJsonPath('data.items.0.is_available', false);

        $this->withToken($token)
            ->postJson('/api/v1/checkout/validate', [
                'shipping_address_id' => $address->id,
                'billing_same_as_shipping' => true,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.cart.0', 'Cart must contain at least one available item.');
    }

    public function test_checkout_validation_requires_customer_token_boundary(): void
    {
        $this->postJson('/api/v1/checkout/validate')
            ->assertUnauthorized();

        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);
        $adminToken = $admin->createToken('admin-api', ['admin'], now()->addHour())->plainTextToken;

        $this->withToken($adminToken)
            ->postJson('/api/v1/checkout/validate')
            ->assertForbidden();
    }

    public function test_checkout_validation_route_has_expected_boundaries(): void
    {
        $route = Route::getRoutes()->getByName('api.v1.checkout.validate');

        $this->assertNotNull($route);
        $middleware = $route->gatherMiddleware();

        $this->assertContains('auth:sanctum', $middleware);
        $this->assertContains('customer.token', $middleware);
        $this->assertContains('throttle:auth.session', $middleware);
    }

    /**
     * @return array{0: User, 1: string}
     */
    private function customerToken(string $email = 'customer@example.com'): array
    {
        $customer = User::factory()->create([
            'email' => $email,
            'is_admin' => false,
            'status' => 'active',
        ]);

        return [
            $customer,
            $customer->createToken('storefront', ['customer'], now()->addHour())->plainTextToken,
        ];
    }

    private function addressFor(User $customer): CustomerAddress
    {
        return $customer->customerAddresses()->create($this->addressPayload());
    }

    /**
     * @return array<string, mixed>
     */
    private function addressPayload(array $overrides = []): array
    {
        return array_merge([
            'label' => 'Home',
            'recipient_name' => 'Customer One',
            'phone' => '01700000000',
            'address_line_1' => 'House 10',
            'address_line_2' => 'Road 5',
            'city' => 'Dhaka',
            'state' => 'Dhaka',
            'postal_code' => '1213',
            'country' => 'BD',
        ], $overrides);
    }

    /**
     * @return array{0: ProductVariant, 1: InventoryStock}
     */
    private function addCartItem(string $token): array
    {
        [$variant, $stock] = $this->sellableVariant();

        $this->withToken($token)
            ->postJson('/api/v1/customer/cart/items', [
                'product_variant_id' => $variant->id,
                'quantity' => 2,
            ])
            ->assertOk();

        return [$variant, $stock];
    }

    /**
     * @return array{0: ProductVariant, 1: InventoryStock}
     */
    private function sellableVariant(): array
    {
        $category = Category::factory()->create(['status' => 'active']);
        $slug = 'checkout-product-'.uniqid();
        $product = Product::factory()->create([
            'name' => 'Checkout Product',
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
            'sku' => 'CHECKOUT-'.strtoupper(uniqid()),
            'price' => 100,
            'is_default' => true,
            'status' => 'active',
        ]);

        $stock = InventoryStock::query()->create([
            'product_variant_id' => $variant->id,
            'quantity_on_hand' => 10,
            'quantity_reserved' => 0,
        ]);

        return [$variant, $stock];
    }
}
