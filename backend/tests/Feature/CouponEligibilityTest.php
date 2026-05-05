<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Modules\Promotion\Services\CouponEligibilityService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CouponEligibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RbacSeeder::class);
    }

    public function test_coupon_eligibility_fields_exist(): void
    {
        $this->assertTrue(Schema::hasColumn('coupons', 'minimum_order_value'));
        $this->assertTrue(Schema::hasColumn('coupons', 'eligible_product_ids'));
        $this->assertTrue(Schema::hasColumn('coupons', 'eligible_category_ids'));
        $this->assertTrue(Schema::hasColumn('coupons', 'usage_limit'));
        $this->assertTrue(Schema::hasColumn('coupons', 'per_customer_usage_limit'));
        $this->assertTrue(Schema::hasColumn('coupons', 'used_count'));
        $this->assertTrue(Schema::hasTable('coupon_redemptions'));
    }

    public function test_admin_can_create_coupon_with_eligibility_rules(): void
    {
        $admin = $this->adminWithRole('admin');
        [$product, $category] = $this->productWithCategory();

        $this->actingAs($admin)
            ->postJson('/api/v1/coupons', [
                'name' => 'Targeted Coupon',
                'code' => 'TARGETED',
                'discount_type' => Coupon::DISCOUNT_FIXED,
                'discount_value' => 100,
                'minimum_order_value' => 500,
                'eligible_product_ids' => [$product->id],
                'eligible_category_ids' => [$category->id],
                'usage_limit' => 10,
                'per_customer_usage_limit' => 1,
                'status' => Coupon::STATUS_ACTIVE,
            ])
            ->assertCreated()
            ->assertJsonPath('data.minimum_order_value', '500.00')
            ->assertJsonPath('data.eligible_product_ids.0', $product->id)
            ->assertJsonPath('data.eligible_category_ids.0', $category->id)
            ->assertJsonPath('data.usage_limit', 10)
            ->assertJsonPath('data.per_customer_usage_limit', 1);
    }

    public function test_coupon_minimum_order_value_is_validated_against_cart(): void
    {
        $cart = $this->cartWithLine(300);
        $coupon = $this->activeCoupon([
            'minimum_order_value' => 500,
        ]);

        $result = app(CouponEligibilityService::class)->validateForCart($coupon, $cart);

        $this->assertFalse($result['eligible']);
        $this->assertContains('minimum_order_value_not_met', $result['reasons']);
    }

    public function test_coupon_product_and_category_rules_are_validated_against_cart(): void
    {
        [$product, $category] = $this->productWithCategory();
        $cart = $this->cartWithLine(600, product: $product);

        $productCoupon = $this->activeCoupon([
            'eligible_product_ids' => [$product->id],
        ]);

        $categoryCoupon = $this->activeCoupon([
            'code' => 'CATEGORY-OK',
            'eligible_category_ids' => [$category->id],
        ]);

        $otherCoupon = $this->activeCoupon([
            'code' => 'OTHER-CATEGORY',
            'eligible_category_ids' => [Category::factory()->create()->id],
        ]);

        $service = app(CouponEligibilityService::class);

        $this->assertTrue($service->validateForCart($productCoupon, $cart)['eligible']);
        $this->assertTrue($service->validateForCart($categoryCoupon, $cart)['eligible']);

        $result = $service->validateForCart($otherCoupon, $cart);

        $this->assertFalse($result['eligible']);
        $this->assertContains('coupon_not_applicable_to_cart_items', $result['reasons']);
    }

    public function test_coupon_usage_limits_are_validated(): void
    {
        $customer = User::factory()->create(['status' => 'active']);
        $cart = $this->cartWithLine(600, customer: $customer);

        $usedCoupon = $this->activeCoupon([
            'usage_limit' => 2,
            'used_count' => 2,
        ]);

        $customerLimitedCoupon = $this->activeCoupon([
            'code' => 'CUSTOMER-LIMIT',
            'per_customer_usage_limit' => 1,
        ]);

        CouponRedemption::query()->create([
            'coupon_id' => $customerLimitedCoupon->id,
            'user_id' => $customer->id,
            'discount_amount' => 100,
            'redeemed_at' => now(),
        ]);

        $service = app(CouponEligibilityService::class);
        $usedResult = $service->validateForCart($usedCoupon, $cart, $customer);
        $customerResult = $service->validateForCart($customerLimitedCoupon, $cart, $customer);

        $this->assertFalse($usedResult['eligible']);
        $this->assertContains('coupon_usage_limit_reached', $usedResult['reasons']);
        $this->assertFalse($customerResult['eligible']);
        $this->assertContains('customer_usage_limit_reached', $customerResult['reasons']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function activeCoupon(array $overrides = []): Coupon
    {
        return Coupon::query()->create(array_merge([
            'name' => 'Eligible Coupon',
            'code' => 'ELIGIBLE-'.strtoupper(uniqid()),
            'discount_type' => Coupon::DISCOUNT_FIXED,
            'discount_value' => 100,
            'status' => Coupon::STATUS_ACTIVE,
        ], $overrides));
    }

    private function cartWithLine(float $lineTotal, ?Product $product = null, ?User $customer = null): Cart
    {
        $product ??= $this->productWithCategory()[0];
        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => 'Default',
            'sku' => 'COUPON-'.strtoupper(uniqid()),
            'price' => $lineTotal,
            'is_default' => true,
            'status' => 'active',
        ]);

        $cart = Cart::query()->create([
            'cart_token' => 'coupon-cart-'.uniqid(),
            'user_id' => $customer?->id,
            'status' => 'active',
        ]);

        CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_name' => $product->name,
            'variant_name' => $variant->name,
            'sku' => $variant->sku,
            'quantity' => 1,
            'unit_price' => $lineTotal,
            'line_total' => $lineTotal,
            'is_available' => true,
        ]);

        return $cart->load('items.product.categories');
    }

    /**
     * @return array{0: Product, 1: Category}
     */
    private function productWithCategory(): array
    {
        $category = Category::factory()->create(['status' => 'active']);
        $product = Product::factory()->create([
            'status' => 'published',
            'published_at' => now(),
        ]);
        $product->categories()->attach($category);

        return [$product, $category];
    }

    private function adminWithRole(string $role): User
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);

        $admin->assignRole($role);

        return $admin;
    }
}
