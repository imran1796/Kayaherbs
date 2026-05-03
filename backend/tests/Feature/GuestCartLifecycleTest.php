<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GuestCartLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cart_tables_exist(): void
    {
        $this->assertTrue(Schema::hasTable('carts'));
        $this->assertTrue(Schema::hasTable('cart_items'));
    }

    public function test_guest_can_create_and_load_cart(): void
    {
        $token = $this->postJson('/api/v1/cart/guest')
            ->assertCreated()
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.items_count', 0)
            ->json('data.cart_token');

        $this->assertNotEmpty($token);

        $this->getJson('/api/v1/cart/guest/'.$token)
            ->assertOk()
            ->assertJsonPath('data.cart_token', $token);
    }

    public function test_guest_can_add_update_remove_and_clear_items(): void
    {
        $variant = $this->sellableVariant(onHand: 8);
        $token = $this->postJson('/api/v1/cart/guest')->json('data.cart_token');

        $this->postJson('/api/v1/cart/guest/'.$token.'/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ])
            ->assertOk()
            ->assertJsonPath('data.items_count', 2)
            ->assertJsonPath('data.subtotal', '200.00')
            ->assertJsonPath('data.items.0.sku', $variant->sku);

        $itemId = Cart::query()->where('cart_token', $token)->firstOrFail()->items()->firstOrFail()->id;

        $this->postJson('/api/v1/cart/guest/'.$token.'/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 3,
        ])
            ->assertOk()
            ->assertJsonPath('data.items_count', 5)
            ->assertJsonPath('data.subtotal', '500.00');

        $this->putJson('/api/v1/cart/guest/'.$token.'/items/'.$itemId, [
            'quantity' => 4,
        ])
            ->assertOk()
            ->assertJsonPath('data.items_count', 4)
            ->assertJsonPath('data.subtotal', '400.00');

        $this->deleteJson('/api/v1/cart/guest/'.$token.'/items/'.$itemId)
            ->assertOk()
            ->assertJsonPath('data.items_count', 0)
            ->assertJsonPath('data.subtotal', '0.00');

        $this->postJson('/api/v1/cart/guest/'.$token.'/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertOk();

        $this->deleteJson('/api/v1/cart/guest/'.$token)
            ->assertOk()
            ->assertJsonPath('data.items_count', 0);
    }

    public function test_guest_cannot_add_hidden_or_unavailable_variant(): void
    {
        $draftVariant = $this->sellableVariant(productOverrides: ['status' => 'draft', 'published_at' => null]);
        $outOfStockVariant = $this->sellableVariant(onHand: 2, reserved: 2);
        $token = $this->postJson('/api/v1/cart/guest')->json('data.cart_token');

        $this->postJson('/api/v1/cart/guest/'.$token.'/items', [
            'product_variant_id' => $draftVariant->id,
            'quantity' => 1,
        ])->assertNotFound();

        $this->postJson('/api/v1/cart/guest/'.$token.'/items', [
            'product_variant_id' => $outOfStockVariant->id,
            'quantity' => 1,
        ])->assertNotFound();
    }

    public function test_guest_cannot_exceed_available_stock(): void
    {
        $variant = $this->sellableVariant(onHand: 3, reserved: 1);
        $token = $this->postJson('/api/v1/cart/guest')->json('data.cart_token');

        $this->postJson('/api/v1/cart/guest/'.$token.'/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 3,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'validation_failed')
            ->assertJsonPath('errors.quantity.0', 'Requested quantity is not available.');
    }

    public function test_expired_cart_token_is_not_usable(): void
    {
        $cart = Cart::query()->create([
            'cart_token' => 'expired-cart-token',
            'status' => 'active',
            'expires_at' => now()->subMinute(),
        ]);

        $this->getJson('/api/v1/cart/guest/'.$cart->cart_token)
            ->assertNotFound();
    }

    public function test_guest_cart_routes_are_public_and_rate_limited(): void
    {
        foreach ([
            'api.v1.cart.guest.store',
            'api.v1.cart.guest.show',
            'api.v1.cart.guest.items.store',
            'api.v1.cart.guest.items.update',
            'api.v1.cart.guest.items.destroy',
            'api.v1.cart.guest.clear',
        ] as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Route [{$routeName}] is not registered.");
            $middleware = $route->gatherMiddleware();

            $this->assertContains('throttle:auth.session', $middleware);
            $this->assertNotContains('auth:sanctum', $middleware);
        }
    }

    private function sellableVariant(
        int $onHand = 5,
        int $reserved = 0,
        array $productOverrides = []
    ): ProductVariant {
        $category = Category::factory()->create(['status' => 'active']);
        $slug = 'cart-product-'.uniqid();
        $product = Product::factory()->create(array_replace([
            'name' => 'Cart Product',
            'slug' => $slug,
            'status' => 'published',
            'published_at' => now(),
        ], $productOverrides));

        $product->categories()->attach($category);
        ProductImage::query()->create([
            'product_id' => $product->id,
            'path' => '/storage/products/'.$slug.'.jpg',
            'is_primary' => true,
        ]);

        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => 'Default',
            'sku' => 'CART-'.strtoupper(uniqid()),
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
