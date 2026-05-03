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

class CartLineItemRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_adding_same_variant_updates_one_cart_line(): void
    {
        $variant = $this->sellableVariant(onHand: 200);
        $token = $this->postJson('/api/v1/cart/guest')->json('data.cart_token');

        $this->postJson('/api/v1/cart/guest/'.$token.'/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ])->assertOk();

        $this->postJson('/api/v1/cart/guest/'.$token.'/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 3,
        ])
            ->assertOk()
            ->assertJsonPath('data.items_count', 5)
            ->assertJsonCount(1, 'data.items');

        $cart = Cart::query()->where('cart_token', $token)->firstOrFail();

        $this->assertSame(1, $cart->items()->count());
        $this->assertSame(5, $cart->items()->firstOrFail()->quantity);
    }

    public function test_cart_line_quantity_limit_includes_existing_quantity(): void
    {
        $variant = $this->sellableVariant(onHand: 200);
        $token = $this->postJson('/api/v1/cart/guest')->json('data.cart_token');

        $this->postJson('/api/v1/cart/guest/'.$token.'/items', [
            'product_variant_id' => $variant->id,
            'quantity' => CartService::MAX_LINE_QUANTITY,
        ])->assertOk();

        $this->postJson('/api/v1/cart/guest/'.$token.'/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.quantity.0', 'Cart line quantity cannot be greater than '.CartService::MAX_LINE_QUANTITY.'.');
    }

    public function test_cart_rejects_zero_price_variant(): void
    {
        $variant = $this->sellableVariant(price: 0, onHand: 5);
        $token = $this->postJson('/api/v1/cart/guest')->json('data.cart_token');

        $this->postJson('/api/v1/cart/guest/'.$token.'/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.product_variant_id.0', 'This product variant cannot be purchased.');
    }

    public function test_cart_has_distinct_line_limit(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

        [, $token] = $this->customerToken();

        for ($line = 0; $line < CartService::MAX_CART_LINES; $line++) {
            $this->withToken($token)
                ->postJson('/api/v1/customer/cart/items', [
                    'product_variant_id' => $this->sellableVariant(skuPrefix: 'LIMIT-'.$line.'-')->id,
                    'quantity' => 1,
                ])
                ->assertOk();
        }

        $this->withToken($token)
            ->postJson('/api/v1/customer/cart/items', [
                'product_variant_id' => $this->sellableVariant(skuPrefix: 'LIMIT-LAST-')->id,
                'quantity' => 1,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.cart.0', 'Cart cannot contain more than '.CartService::MAX_CART_LINES.' different items.');
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
        int $reserved = 0,
        string $skuPrefix = 'RULE-'
    ): ProductVariant {
        $category = Category::factory()->create(['status' => 'active']);
        $slug = 'cart-rule-product-'.uniqid();
        $product = Product::factory()->create([
            'name' => 'Cart Rule Product',
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
            'sku' => $skuPrefix.strtoupper(uniqid()),
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
