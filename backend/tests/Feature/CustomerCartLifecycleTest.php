<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CustomerCartLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_create_and_load_own_cart(): void
    {
        [$customer, $token] = $this->customerToken();

        $this->withToken($token)
            ->getJson('/api/v1/customer/cart')
            ->assertOk()
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.items_count', 0);

        $this->assertDatabaseHas('carts', [
            'user_id' => $customer->id,
            'status' => 'active',
        ]);

        $this->withToken($token)
            ->getJson('/api/v1/customer/cart')
            ->assertOk();

        $this->assertSame(1, $customer->carts()->count());
    }

    public function test_customer_can_add_update_remove_and_clear_items(): void
    {
        [$customer, $token] = $this->customerToken();
        $variant = $this->sellableVariant(onHand: 8);

        $this->withToken($token)
            ->postJson('/api/v1/customer/cart/items', [
                'product_variant_id' => $variant->id,
                'quantity' => 2,
            ])
            ->assertOk()
            ->assertJsonPath('data.items_count', 2)
            ->assertJsonPath('data.subtotal', '200.00')
            ->assertJsonPath('data.items.0.sku', $variant->sku);

        $itemId = $customer->carts()->firstOrFail()->items()->firstOrFail()->id;

        $this->withToken($token)
            ->postJson('/api/v1/customer/cart/items', [
                'product_variant_id' => $variant->id,
                'quantity' => 3,
            ])
            ->assertOk()
            ->assertJsonPath('data.items_count', 5);

        $this->withToken($token)
            ->putJson('/api/v1/customer/cart/items/'.$itemId, [
                'quantity' => 4,
            ])
            ->assertOk()
            ->assertJsonPath('data.items_count', 4)
            ->assertJsonPath('data.subtotal', '400.00');

        $this->withToken($token)
            ->deleteJson('/api/v1/customer/cart/items/'.$itemId)
            ->assertOk()
            ->assertJsonPath('data.items_count', 0);

        $this->withToken($token)
            ->postJson('/api/v1/customer/cart/items', [
                'product_variant_id' => $variant->id,
                'quantity' => 1,
            ])
            ->assertOk();

        $this->withToken($token)
            ->deleteJson('/api/v1/customer/cart')
            ->assertOk()
            ->assertJsonPath('data.items_count', 0);
    }

    public function test_customer_cannot_mutate_another_customers_cart_item(): void
    {
        [$firstCustomer, $firstToken] = $this->customerToken('first@example.com');
        [, $secondToken] = $this->customerToken('second@example.com');
        $variant = $this->sellableVariant();

        $this->withToken($firstToken)
            ->postJson('/api/v1/customer/cart/items', [
                'product_variant_id' => $variant->id,
                'quantity' => 1,
            ])
            ->assertOk();

        $itemId = $firstCustomer->carts()->firstOrFail()->items()->firstOrFail()->id;

        app('auth')->forgetGuards();

        $this->withToken($secondToken)
            ->putJson('/api/v1/customer/cart/items/'.$itemId, [
                'quantity' => 2,
            ])
            ->assertNotFound();
    }

    public function test_customer_cart_rejects_unavailable_stock(): void
    {
        [, $token] = $this->customerToken();
        $variant = $this->sellableVariant(onHand: 2, reserved: 1);

        $this->withToken($token)
            ->postJson('/api/v1/customer/cart/items', [
                'product_variant_id' => $variant->id,
                'quantity' => 2,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.quantity.0', 'Requested quantity is not available.');
    }

    public function test_customer_cart_requires_customer_token_boundary(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);
        $adminToken = $admin->createToken('admin-api', ['admin'], now()->addHour())->plainTextToken;

        $this->getJson('/api/v1/customer/cart')->assertUnauthorized();

        $this->withToken($adminToken)
            ->getJson('/api/v1/customer/cart')
            ->assertForbidden();
    }

    public function test_customer_cart_routes_have_expected_boundaries(): void
    {
        foreach ([
            'api.v1.customer.cart.show',
            'api.v1.customer.cart.items.store',
            'api.v1.customer.cart.items.update',
            'api.v1.customer.cart.items.destroy',
            'api.v1.customer.cart.clear',
        ] as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Route [{$routeName}] is not registered.");
            $middleware = $route->gatherMiddleware();

            $this->assertContains('auth:sanctum', $middleware);
            $this->assertContains('customer.token', $middleware);
            $this->assertContains('throttle:auth.session', $middleware);
        }
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

    private function sellableVariant(int $onHand = 5, int $reserved = 0): ProductVariant
    {
        $category = Category::factory()->create(['status' => 'active']);
        $slug = 'customer-cart-product-'.uniqid();
        $product = Product::factory()->create([
            'name' => 'Customer Cart Product',
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
            'sku' => 'CCART-'.strtoupper(uniqid()),
            'price' => 100,
            'is_default' => true,
            'status' => 'active',
        ]);

        InventoryStock::query()->create([
            'product_variant_id' => $variant->id,
            'quantity_on_hand' => $onHand,
            'quantity_reserved' => $reserved,
        ]);

        return $variant;
    }
}
