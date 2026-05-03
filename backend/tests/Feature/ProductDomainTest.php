<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ProductDomainTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RbacSeeder::class);
    }

    public function test_product_variant_image_and_category_tables_exist(): void
    {
        $this->assertTrue(Schema::hasTable('products'));
        $this->assertTrue(Schema::hasTable('product_variants'));
        $this->assertTrue(Schema::hasTable('product_images'));
        $this->assertTrue(Schema::hasTable('category_product'));
    }

    public function test_product_relationships_work(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create();
        $product->categories()->attach($category);
        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => 'Default',
            'sku' => 'SKU-REL-1',
            'price' => 120,
            'is_default' => true,
            'status' => 'active',
        ]);
        $image = ProductImage::query()->create([
            'product_id' => $product->id,
            'path' => '/storage/products/product.jpg',
            'is_primary' => true,
        ]);

        $product->load(['categories', 'variants', 'images']);

        $this->assertTrue($product->categories->first()->is($category));
        $this->assertTrue($product->variants->first()->is($variant));
        $this->assertTrue($product->images->first()->is($image));
    }

    public function test_admin_ajax_endpoints_can_create_update_and_publish_product(): void
    {
        $admin = $this->adminWithRole('admin');
        $category = Category::factory()->create();

        $this->actingAs($admin)
            ->postJson('/admin/products', $this->payload([
                'category_ids' => [$category->id],
                'name' => 'Herbal Shampoo',
                'slug' => 'herbal-shampoo',
                'variants' => [[
                    'name' => '250ml',
                    'sku' => 'HS-250',
                    'price' => 350,
                    'is_default' => true,
                    'status' => 'active',
                ], [
                    'name' => '500ml',
                    'sku' => 'HS-500',
                    'price' => 650,
                    'is_default' => false,
                    'status' => 'active',
                ]],
                'images' => [[
                    'path' => '/storage/products/herbal-shampoo.jpg',
                    'alt_text' => 'Herbal Shampoo',
                    'is_primary' => true,
                ]],
            ]))
            ->assertCreated()
            ->assertJsonPath('data.name', 'Herbal Shampoo')
            ->assertJsonPath('data.variants.0.sku', 'HS-250')
            ->assertJsonPath('data.variants.1.sku', 'HS-500')
            ->assertJsonPath('data.images.0.path', '/storage/products/herbal-shampoo.jpg');

        $product = Product::query()->where('slug', 'herbal-shampoo')->firstOrFail();

        $this->assertTrue($product->categories()->whereKey($category->id)->exists());

        $this->actingAs($admin)
            ->putJson('/admin/products/'.$product->id, $this->payload([
                'name' => 'Herbal Shampoo Updated',
                'slug' => 'herbal-shampoo-updated',
                'variants' => [[
                    'name' => '500ml',
                    'sku' => 'HS-500',
                    'price' => 650,
                    'is_default' => true,
                    'status' => 'active',
                ]],
                'images' => [[
                    'path' => '/storage/products/herbal-shampoo-500.jpg',
                    'alt_text' => 'Herbal Shampoo Updated',
                    'is_primary' => true,
                ]],
            ]))
            ->assertOk()
            ->assertJsonPath('data.name', 'Herbal Shampoo Updated')
            ->assertJsonPath('data.variants.0.sku', 'HS-500');

        $this->actingAs($admin)
            ->postJson('/admin/products/'.$product->id.'/publish')
            ->assertOk()
            ->assertJsonPath('data.status', 'published');

        $this->actingAs($admin)
            ->putJson('/admin/products/'.$product->id, $this->payload([
                'name' => 'Published Product Edited',
                'slug' => 'published-product-edited',
                'status' => 'published',
                'variants' => [[
                    'name' => '500ml',
                    'sku' => 'HS-500',
                    'price' => 675,
                    'is_default' => true,
                    'status' => 'active',
                ]],
                'images' => [[
                    'path' => '/storage/products/herbal-shampoo-500.jpg',
                    'alt_text' => 'Published Product Edited',
                    'is_primary' => true,
                ]],
            ]))
            ->assertOk()
            ->assertJsonPath('data.status', 'published')
            ->assertJsonPath('data.name', 'Published Product Edited');

        $this->actingAs($admin)
            ->postJson('/admin/products/'.$product->id.'/unpublish')
            ->assertOk()
            ->assertJsonPath('data.status', 'unpublished');
    }

    public function test_admin_api_product_endpoints_support_variants_images_and_publish_rules(): void
    {
        $admin = $this->adminWithRole('admin');
        $token = $admin->createToken('admin-api')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/products', $this->payload([
                'name' => 'Publish Blocked',
                'slug' => 'publish-blocked',
                'images' => [],
            ]))
            ->assertCreated();

        $product = Product::query()->where('slug', 'publish-blocked')->firstOrFail();

        $this->withToken($token)
            ->postJson('/api/v1/products/'.$product->id.'/publish')
            ->assertUnprocessable()
            ->assertJsonPath('code', 'validation_failed');

        $this->withToken($token)
            ->putJson('/api/v1/products/'.$product->id, $this->payload([
                'name' => 'Publish Allowed',
                'slug' => 'publish-allowed',
                'images' => [[
                    'path' => '/storage/products/publish-allowed.jpg',
                    'is_primary' => true,
                ]],
            ]))
            ->assertOk();

        $this->withToken($token)
            ->postJson('/api/v1/products/'.$product->id.'/publish')
            ->assertOk()
            ->assertJsonPath('data.status', 'published');
    }

    public function test_admin_can_upload_primary_product_image(): void
    {
        Storage::fake('public');

        $admin = $this->adminWithRole('admin');

        $this->actingAs($admin)
            ->post('/admin/products', $this->payload([
                'name' => 'Image Upload Product',
                'slug' => 'image-upload-product',
                'variants' => [[
                    'name' => 'Default',
                    'sku' => 'IMG-UPLOAD-1',
                    'price' => 500,
                ]],
                'images' => [],
                'primary_image' => UploadedFile::fake()->image('product.jpg'),
                'image_alt_text' => 'Uploaded product image',
            ]), ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.images.0.alt_text', 'Uploaded product image');

        $image = Product::query()
            ->where('slug', 'image-upload-product')
            ->firstOrFail()
            ->images()
            ->firstOrFail();

        $this->assertStringStartsWith('/storage/products/', $image->path);
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $image->path));
    }

    public function test_admin_product_list_can_filter_by_search_status_and_category(): void
    {
        $admin = $this->adminWithRole('admin');
        $hairCare = Category::factory()->create(['name' => 'Hair Care']);
        $skinCare = Category::factory()->create(['name' => 'Skin Care']);

        $matching = Product::factory()->create([
            'name' => 'Herbal Shampoo',
            'slug' => 'herbal-shampoo',
            'status' => 'published',
        ]);
        $matching->categories()->attach($hairCare);

        $wrongStatus = Product::factory()->create([
            'name' => 'Herbal Conditioner',
            'slug' => 'herbal-conditioner',
            'status' => 'draft',
        ]);
        $wrongStatus->categories()->attach($hairCare);

        $wrongCategory = Product::factory()->create([
            'name' => 'Herbal Face Wash',
            'slug' => 'herbal-face-wash',
            'status' => 'published',
        ]);
        $wrongCategory->categories()->attach($skinCare);

        $this->actingAs($admin)
            ->getJson('/admin/products/data?search=herbal&status=published&category_id='.$hairCare->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matching->id);
    }

    public function test_admin_product_page_shows_filter_controls(): void
    {
        $admin = $this->adminWithRole('admin');
        Category::factory()->create(['name' => 'Hair Care']);

        $this->actingAs($admin)
            ->get('/admin/products')
            ->assertOk()
            ->assertSee('product-filter-form')
            ->assertSee('Name or slug')
            ->assertSee('All statuses')
            ->assertSee('Hair Care');
    }

    public function test_variant_sku_must_be_unique(): void
    {
        $admin = $this->adminWithRole('admin');

        $this->actingAs($admin)
            ->postJson('/admin/products', $this->payload([
                'name' => 'First Product',
                'slug' => 'first-product',
                'variants' => [[
                    'name' => 'Default',
                    'sku' => ' dup-sku ',
                    'price' => 100,
                ]],
            ]))
            ->assertCreated();

        $this->assertDatabaseHas('product_variants', [
            'sku' => 'DUP-SKU',
        ]);

        $this->actingAs($admin)
            ->postJson('/admin/products', $this->payload([
                'name' => 'Second Product',
                'slug' => 'second-product',
                'variants' => [[
                    'name' => 'Default',
                    'sku' => 'dup-sku',
                    'price' => 100,
                ]],
            ]))
            ->assertUnprocessable()
            ->assertJsonPath('code', 'validation_failed');
    }

    public function test_product_slugs_are_generated_and_made_unique(): void
    {
        $admin = $this->adminWithRole('admin');

        $this->actingAs($admin)
            ->postJson('/admin/products', $this->payload([
                'name' => 'Herbal Face Wash',
                'slug' => '',
                'variants' => [[
                    'name' => 'Default',
                    'sku' => 'HFW-1',
                    'price' => 100,
                ]],
            ]))
            ->assertCreated()
            ->assertJsonPath('data.slug', 'herbal-face-wash');

        $this->actingAs($admin)
            ->postJson('/admin/products', $this->payload([
                'name' => 'Herbal Face Wash',
                'slug' => '',
                'variants' => [[
                    'name' => 'Default',
                    'sku' => 'HFW-2',
                    'price' => 100,
                ]],
            ]))
            ->assertCreated()
            ->assertJsonPath('data.slug', 'herbal-face-wash-2');
    }

    public function test_only_one_variant_can_be_default_after_normalization(): void
    {
        $admin = $this->adminWithRole('admin');

        $this->actingAs($admin)
            ->postJson('/admin/products', $this->payload([
                'name' => 'Default Variant Product',
                'slug' => 'default-variant-product',
                'variants' => [[
                    'name' => 'Small',
                    'sku' => 'DVP-S',
                    'price' => 100,
                    'is_default' => true,
                ], [
                    'name' => 'Large',
                    'sku' => 'DVP-L',
                    'price' => 150,
                    'is_default' => true,
                ]],
            ]))
            ->assertCreated()
            ->assertJsonPath('data.variants.0.is_default', true)
            ->assertJsonPath('data.variants.1.is_default', false);
    }

    public function test_public_catalog_only_returns_visible_products(): void
    {
        $activeCategory = Category::factory()->create(['status' => 'active']);
        $inactiveCategory = Category::factory()->create(['status' => 'inactive']);
        $visible = $this->catalogProduct('Visible Product', 'visible-product', $activeCategory);

        $this->catalogProduct('Draft Product', 'draft-product', $activeCategory, ['status' => 'draft']);
        $this->catalogProduct('No Image Product', 'no-image-product', $activeCategory, [], false);
        $this->catalogProduct('Inactive Variant Product', 'inactive-variant-product', $activeCategory, [], true, 'inactive');
        $this->catalogProduct('Inactive Category Product', 'inactive-category-product', $inactiveCategory);

        $this->getJson('/api/v1/catalog/products')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $visible->id)
            ->assertJsonPath('data.0.variants.0.status', 'active')
            ->assertJsonPath('data.0.categories.0.status', 'active');

        $this->getJson('/api/v1/catalog/products/visible-product')
            ->assertOk()
            ->assertJsonPath('data.id', $visible->id);

        $this->getJson('/api/v1/catalog/products/draft-product')
            ->assertNotFound();
    }

    public function test_public_catalog_respects_variant_inventory_visibility(): void
    {
        $category = Category::factory()->create(['status' => 'active']);
        $inStock = $this->catalogProduct('In Stock Product', 'in-stock-product', $category);
        $outOfStock = $this->catalogProduct('Out Of Stock Product', 'out-of-stock-product', $category);
        $backorder = $this->catalogProduct('Backorder Product', 'backorder-product', $category);
        $untracked = $this->catalogProduct('Untracked Product', 'untracked-product', $category);

        $this->stockFor($inStock, onHand: 5, reserved: 2);
        $this->stockFor($outOfStock, onHand: 3, reserved: 3);
        $this->stockFor($backorder, onHand: 0, reserved: 0, allowBackorder: true);
        $this->stockFor($untracked, onHand: 0, reserved: 0, trackInventory: false);

        $this->getJson('/api/v1/catalog/products')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonMissingPath('data.0.variants.1')
            ->assertJsonFragment(['slug' => 'in-stock-product'])
            ->assertJsonFragment(['slug' => 'backorder-product'])
            ->assertJsonFragment(['slug' => 'untracked-product'])
            ->assertJsonMissing(['slug' => 'out-of-stock-product']);

        $this->getJson('/api/v1/catalog/products/in-stock-product')
            ->assertOk()
            ->assertJsonPath('data.variants.0.stock.available_quantity', 3)
            ->assertJsonPath('data.variants.0.stock.track_inventory', true);

        $this->getJson('/api/v1/catalog/products/out-of-stock-product')
            ->assertNotFound();
    }

    public function test_manager_can_view_but_cannot_mutate_products(): void
    {
        $manager = $this->adminWithRole('manager');
        $token = $manager->createToken('admin-api')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/products')
            ->assertOk();

        $this->actingAs($manager)
            ->postJson('/admin/products', $this->payload())
            ->assertForbidden();

        $this->withToken($token)
            ->postJson('/api/v1/products', $this->payload())
            ->assertForbidden();
    }

    public function test_product_routes_have_expected_boundaries(): void
    {
        foreach ([
            'admin.products.index' => 'products.view',
            'admin.products.data' => 'products.view',
            'admin.products.show' => 'products.view',
            'admin.products.store' => 'products.create',
            'admin.products.update' => 'products.update',
            'admin.products.publish' => 'products.publish',
            'admin.products.unpublish' => 'products.publish',
            'admin.products.destroy' => 'products.delete',
        ] as $routeName => $permission) {
            $middleware = $this->middlewareFor($routeName);

            $this->assertRouteHasMiddleware($middleware, 'auth');
            $this->assertRouteHasMiddleware($middleware, 'admin');
            $this->assertRouteHasMiddleware($middleware, 'can:'.$permission);
        }

        foreach ([
            'api.v1.products.index' => 'products.view',
            'api.v1.products.show' => 'products.view',
            'api.v1.products.store' => 'products.create',
            'api.v1.products.update' => 'products.update',
            'api.v1.products.publish' => 'products.publish',
            'api.v1.products.unpublish' => 'products.publish',
            'api.v1.products.destroy' => 'products.delete',
        ] as $routeName => $permission) {
            $middleware = $this->middlewareFor($routeName);

            $this->assertRouteHasMiddleware($middleware, 'auth:sanctum');
            $this->assertRouteHasMiddleware($middleware, 'admin');
            $this->assertRouteHasMiddleware($middleware, 'can:'.$permission);
        }
    }

    private function payload(array $overrides = []): array
    {
        $payload = array_replace_recursive([
            'name' => 'Default Product',
            'slug' => 'default-product',
            'description' => 'Default product description.',
            'status' => 'draft',
            'category_ids' => [],
            'variants' => [[
                'name' => 'Default',
                'sku' => 'DEFAULT-SKU',
                'price' => 100,
                'compare_at_price' => null,
                'is_default' => true,
                'status' => 'active',
            ]],
            'images' => [[
                'path' => '/storage/products/default.jpg',
                'alt_text' => 'Default Product',
                'is_primary' => true,
            ]],
        ], $overrides);

        foreach (['category_ids', 'variants', 'images'] as $arrayKey) {
            if (array_key_exists($arrayKey, $overrides)) {
                $payload[$arrayKey] = $overrides[$arrayKey];
            }
        }

        return $payload;
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

    private function catalogProduct(
        string $name,
        string $slug,
        Category $category,
        array $overrides = [],
        bool $withImage = true,
        string $variantStatus = 'active'
    ): Product {
        $product = Product::factory()->create(array_replace([
            'name' => $name,
            'slug' => $slug,
            'status' => 'published',
            'published_at' => now(),
        ], $overrides));

        $product->categories()->attach($category);
        ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => 'Default',
            'sku' => str($slug)->upper()->replace('-', '-')->toString(),
            'price' => 100,
            'is_default' => true,
            'status' => $variantStatus,
        ]);

        if ($withImage) {
            ProductImage::query()->create([
                'product_id' => $product->id,
                'path' => '/storage/products/'.$slug.'.jpg',
                'is_primary' => true,
            ]);
        }

        return $product;
    }

    private function stockFor(
        Product $product,
        int $onHand,
        int $reserved,
        bool $trackInventory = true,
        bool $allowBackorder = false
    ): InventoryStock {
        /** @var ProductVariant $variant */
        $variant = $product->variants()->firstOrFail();

        return InventoryStock::query()->create([
            'product_variant_id' => $variant->id,
            'quantity_on_hand' => $onHand,
            'quantity_reserved' => $reserved,
            'track_inventory' => $trackInventory,
            'allow_backorder' => $allowBackorder,
        ]);
    }

    /**
     * @return list<string>
     */
    private function middlewareFor(string $routeName): array
    {
        $route = Route::getRoutes()->getByName($routeName);

        $this->assertNotNull($route, "Route [{$routeName}] is not registered.");

        return $route->gatherMiddleware();
    }

    /**
     * @param  list<string>  $middleware
     */
    private function assertRouteHasMiddleware(array $middleware, string $expected): void
    {
        $this->assertTrue(
            collect($middleware)->contains(fn (string $entry): bool => $entry === $expected || str_starts_with($entry, $expected)),
            'Expected middleware ['.$expected.'] not found in ['.implode(', ', $middleware).'].'
        );
    }
}
