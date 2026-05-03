<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use App\Modules\Cart\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartQuantityAndPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_quantity_update_sets_exact_quantity_not_increment(): void
    {
        $variant = $this->sellableVariant(onHand: 20);
        $token = $this->postJson('/api/v1/cart/guest')->json('data.cart_token');

        $this->postJson('/api/v1/cart/guest/'.$token.'/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 5,
        ])->assertOk();

        $itemId = Cart::query()->where('cart_token', $token)->firstOrFail()->items()->firstOrFail()->id;

        $this->putJson('/api/v1/cart/guest/'.$token.'/items/'.$itemId, [
            'quantity' => 2,
        ])
            ->assertOk()
            ->assertJsonPath('data.items_count', 2)
            ->assertJsonPath('data.subtotal', '200.00');
    }

    public function test_quantity_update_rejects_zero_and_more_than_available_stock(): void
    {
        $variant = $this->sellableVariant(onHand: 3, reserved: 1);
        $token = $this->postJson('/api/v1/cart/guest')->json('data.cart_token');

        $this->postJson('/api/v1/cart/guest/'.$token.'/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertOk();

        $itemId = Cart::query()->where('cart_token', $token)->firstOrFail()->items()->firstOrFail()->id;

        $this->putJson('/api/v1/cart/guest/'.$token.'/items/'.$itemId, [
            'quantity' => 0,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.quantity.0', 'The quantity field must be at least 1.');

        $this->putJson('/api/v1/cart/guest/'.$token.'/items/'.$itemId, [
            'quantity' => 3,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.quantity.0', 'Requested quantity is not available.');
    }

    public function test_price_recalculation_refreshes_line_snapshot_when_cart_is_loaded(): void
    {
        $variant = $this->sellableVariant(price: 100, onHand: 20);
        $token = $this->postJson('/api/v1/cart/guest')->json('data.cart_token');

        $this->postJson('/api/v1/cart/guest/'.$token.'/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ])
            ->assertOk()
            ->assertJsonPath('data.subtotal', '200.00');

        $variant->product->update(['name' => 'Updated Product Name']);
        $variant->update([
            'name' => 'Updated Variant Name',
            'sku' => 'UPDATED-SKU-1',
            'price' => 125.50,
        ]);

        $this->getJson('/api/v1/cart/guest/'.$token)
            ->assertOk()
            ->assertJsonPath('data.subtotal', '251.00')
            ->assertJsonPath('data.items.0.product_name', 'Updated Product Name')
            ->assertJsonPath('data.items.0.variant_name', 'Updated Variant Name')
            ->assertJsonPath('data.items.0.sku', 'UPDATED-SKU-1')
            ->assertJsonPath('data.items.0.unit_price', '125.50')
            ->assertJsonPath('data.items.0.line_total', '251.00');

        $cartItem = Cart::query()->where('cart_token', $token)->firstOrFail()->items()->firstOrFail();

        $this->assertSame('125.50', $cartItem->unit_price);
        $this->assertSame('251.00', $cartItem->line_total);
    }

    public function test_customer_cart_price_recalculation_runs_after_quantity_update(): void
    {
        [, $token] = $this->customerToken();
        $variant = $this->sellableVariant(price: 80, onHand: 20);

        $this->withToken($token)
            ->postJson('/api/v1/customer/cart/items', [
                'product_variant_id' => $variant->id,
                'quantity' => 2,
            ])
            ->assertOk()
            ->assertJsonPath('data.subtotal', '160.00');

        $cart = Cart::query()->where('user_id', User::query()->firstOrFail()->id)->firstOrFail();
        $itemId = $cart->items()->firstOrFail()->id;
        $variant->update(['price' => 90]);

        $this->withToken($token)
            ->putJson('/api/v1/customer/cart/items/'.$itemId, [
                'quantity' => 3,
            ])
            ->assertOk()
            ->assertJsonPath('data.items_count', 3)
            ->assertJsonPath('data.subtotal', '270.00')
            ->assertJsonPath('data.items.0.unit_price', '90.00');
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

    private function sellableVariant(
        float $price = 100,
        int $onHand = 5,
        int $reserved = 0
    ): ProductVariant {
        $category = Category::factory()->create(['status' => 'active']);
        $slug = 'cart-price-product-'.uniqid();
        $product = Product::factory()->create([
            'name' => 'Cart Price Product',
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
            'sku' => 'PRICE-'.strtoupper(uniqid()),
            'price' => $price,
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
