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
use Tests\TestCase;

class CartUnavailableAndRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_marks_item_unavailable_when_stock_becomes_insufficient(): void
    {
        [$variant, $stock] = $this->sellableVariant(onHand: 5);
        $token = $this->postJson('/api/v1/cart/guest')->json('data.cart_token');

        $this->postJson('/api/v1/cart/guest/'.$token.'/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 3,
        ])->assertOk();

        $stock->update([
            'quantity_on_hand' => 2,
            'quantity_reserved' => 0,
        ]);

        $this->getJson('/api/v1/cart/guest/'.$token)
            ->assertOk()
            ->assertJsonPath('data.items_count', 0)
            ->assertJsonPath('data.unavailable_items_count', 1)
            ->assertJsonPath('data.subtotal', '0.00')
            ->assertJsonPath('data.items.0.is_available', false)
            ->assertJsonPath('data.items.0.unavailable_reason', 'insufficient_stock');
    }

    public function test_cart_item_can_recover_when_product_becomes_available_again(): void
    {
        [$variant] = $this->sellableVariant(onHand: 5);
        $token = $this->postJson('/api/v1/cart/guest')->json('data.cart_token');

        $this->postJson('/api/v1/cart/guest/'.$token.'/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ])->assertOk();

        $variant->update(['status' => 'inactive']);

        $this->getJson('/api/v1/cart/guest/'.$token)
            ->assertOk()
            ->assertJsonPath('data.items.0.is_available', false)
            ->assertJsonPath('data.items.0.unavailable_reason', 'variant_unavailable');

        $variant->update(['status' => 'active']);

        $this->getJson('/api/v1/cart/guest/'.$token)
            ->assertOk()
            ->assertJsonPath('data.items_count', 2)
            ->assertJsonPath('data.unavailable_items_count', 0)
            ->assertJsonPath('data.items.0.is_available', true)
            ->assertJsonPath('data.items.0.unavailable_reason', null);
    }

    public function test_guest_cart_activity_extends_recovery_window(): void
    {
        [$variant] = $this->sellableVariant();
        $token = $this->postJson('/api/v1/cart/guest')->json('data.cart_token');
        $cart = Cart::query()->where('cart_token', $token)->firstOrFail();

        $cart->update(['expires_at' => now()->addDay()]);

        $this->postJson('/api/v1/cart/guest/'.$token.'/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertOk();

        $this->assertTrue($cart->refresh()->expires_at->greaterThan(now()->addDays(20)));
    }

    public function test_customer_cart_recovers_existing_active_cart(): void
    {
        [$customer, $token] = $this->customerToken();
        [$variant] = $this->sellableVariant();

        $cart = Cart::query()->create([
            'cart_token' => 'existing-customer-cart',
            'user_id' => $customer->id,
            'status' => 'active',
        ]);
        $cart->items()->create([
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'product_name' => $variant->product->name,
            'variant_name' => $variant->name,
            'sku' => $variant->sku,
            'quantity' => 2,
            'unit_price' => 100,
            'line_total' => 200,
        ]);

        $this->withToken($token)
            ->getJson('/api/v1/customer/cart')
            ->assertOk()
            ->assertJsonPath('data.cart_token', 'existing-customer-cart')
            ->assertJsonPath('data.items_count', 2);

        $this->assertSame(1, $customer->carts()->count());
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

    /**
     * @return array{0: ProductVariant, 1: InventoryStock}
     */
    private function sellableVariant(int $onHand = 5): array
    {
        $category = Category::factory()->create(['status' => 'active']);
        $slug = 'cart-recovery-product-'.uniqid();
        $product = Product::factory()->create([
            'name' => 'Cart Recovery Product',
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
            'sku' => 'RECOVERY-'.strtoupper(uniqid()),
            'price' => 100,
            'is_default' => true,
            'status' => 'active',
        ]);

        $stock = InventoryStock::query()->create([
            'product_variant_id' => $variant->id,
            'quantity_on_hand' => $onHand,
            'quantity_reserved' => 0,
        ]);

        return [$variant->load('product'), $stock];
    }
}
