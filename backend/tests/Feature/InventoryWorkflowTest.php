<?php

namespace Tests\Feature;

use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class InventoryWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RbacSeeder::class);
    }

    public function test_admin_can_adjust_stock_and_log_transaction(): void
    {
        $admin = $this->adminWithRole('admin');
        [$variant, $stock] = $this->stock(onHand: 10, reserved: 2);

        $this->actingAs($admin)
            ->postJson('/admin/inventory/variants/'.$variant->id.'/adjust', [
                'quantity_delta' => 5,
                'note' => 'New purchase received',
                'metadata' => ['source' => 'manual'],
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Stock adjusted successfully.')
            ->assertJsonPath('data.quantity_on_hand', 15)
            ->assertJsonPath('data.quantity_reserved', 2)
            ->assertJsonPath('data.available_quantity', 13);

        $stock->refresh();

        $this->assertSame(15, $stock->quantity_on_hand);
        $this->assertDatabaseHas('inventory_transactions', [
            'inventory_stock_id' => $stock->id,
            'product_variant_id' => $variant->id,
            'actor_id' => $admin->id,
            'type' => 'adjustment',
            'quantity_delta' => 5,
            'quantity_on_hand_after' => 15,
            'quantity_reserved_after' => 2,
            'note' => 'New purchase received',
        ]);
    }

    public function test_admin_can_view_inventory_stock_list_screen(): void
    {
        $admin = $this->adminWithRole('admin');

        $this->actingAs($admin)
            ->get('/admin/inventory')
            ->assertOk()
            ->assertSee('Stock List')
            ->assertSee('stock-table-body')
            ->assertSee('refresh-stocks');
    }

    public function test_admin_can_view_stock_adjustment_screen(): void
    {
        $admin = $this->adminWithRole('admin');

        $this->actingAs($admin)
            ->get('/admin/inventory')
            ->assertOk()
            ->assertSee('Adjust Stock')
            ->assertSee('stock-adjustment-form')
            ->assertSee('quantity_delta')
            ->assertSee('Save adjustment');
    }

    public function test_admin_can_view_low_stock_panel(): void
    {
        $admin = $this->adminWithRole('admin');

        $this->actingAs($admin)
            ->get('/admin/inventory')
            ->assertOk()
            ->assertSee('Low Stock')
            ->assertSee('low-stock-table-body')
            ->assertSee('refresh-low-stock');
    }

    public function test_manager_does_not_see_stock_adjustment_screen(): void
    {
        $manager = $this->adminWithRole('manager');

        $this->actingAs($manager)
            ->get('/admin/inventory')
            ->assertOk()
            ->assertDontSee('stock-adjustment-form')
            ->assertDontSee('Save adjustment');
    }

    public function test_admin_can_fetch_inventory_stock_list_data(): void
    {
        $admin = $this->adminWithRole('admin');
        [$variant] = $this->stock(onHand: 10, reserved: 2);

        $this->actingAs($admin)
            ->getJson('/admin/inventory/data')
            ->assertOk()
            ->assertJsonPath('message', 'Inventory stocks fetched successfully.')
            ->assertJsonPath('data.0.product_variant_id', $variant->id)
            ->assertJsonPath('data.0.product_name', $variant->product->name)
            ->assertJsonPath('data.0.variant_name', $variant->name)
            ->assertJsonPath('data.0.sku', $variant->sku)
            ->assertJsonPath('data.0.quantity_on_hand', 10)
            ->assertJsonPath('data.0.quantity_reserved', 2)
            ->assertJsonPath('data.0.available_quantity', 8);
    }

    public function test_inventory_list_creates_missing_zero_stock_rows_for_variants(): void
    {
        $admin = $this->adminWithRole('admin');
        $variant = $this->variant();

        $this->assertDatabaseMissing('inventory_stocks', [
            'product_variant_id' => $variant->id,
        ]);

        $this->actingAs($admin)
            ->getJson('/admin/inventory/data')
            ->assertOk()
            ->assertJsonPath('data.0.product_variant_id', $variant->id)
            ->assertJsonPath('data.0.product_name', $variant->product->name)
            ->assertJsonPath('data.0.variant_name', $variant->name)
            ->assertJsonPath('data.0.quantity_on_hand', 0)
            ->assertJsonPath('data.0.quantity_reserved', 0)
            ->assertJsonPath('data.0.available_quantity', 0);

        $this->assertDatabaseHas('inventory_stocks', [
            'product_variant_id' => $variant->id,
            'quantity_on_hand' => 0,
            'quantity_reserved' => 0,
        ]);
    }

    public function test_admin_can_fetch_low_stock_panel_data(): void
    {
        $admin = $this->adminWithRole('admin');
        [$lowVariant, $lowStock] = $this->stock(onHand: 5, reserved: 3);
        [$healthyVariant, $healthyStock] = $this->stock(onHand: 20, reserved: 2);

        $lowStock->update(['low_stock_threshold' => 2]);
        $healthyStock->update(['low_stock_threshold' => 5]);

        $this->actingAs($admin)
            ->getJson('/admin/inventory/low-stock/data')
            ->assertOk()
            ->assertJsonPath('message', 'Low-stock inventory fetched successfully.')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.product_variant_id', $lowVariant->id)
            ->assertJsonPath('data.0.product_name', $lowVariant->product->name)
            ->assertJsonPath('data.0.available_quantity', 2)
            ->assertJsonPath('data.0.low_stock_threshold', 2)
            ->assertJsonMissing(['product_variant_id' => $healthyVariant->id]);
    }

    public function test_admin_can_view_inventory_history_screen(): void
    {
        $admin = $this->adminWithRole('admin');

        $this->actingAs($admin)
            ->get('/admin/inventory/history')
            ->assertOk()
            ->assertSee('Stock Movement History')
            ->assertSee('inventory-history-table-body')
            ->assertSee('refresh-history');
    }

    public function test_admin_can_fetch_inventory_history_data(): void
    {
        $admin = $this->adminWithRole('admin');
        [$variant] = $this->stock(onHand: 10, reserved: 2);

        $this->actingAs($admin)
            ->postJson('/admin/inventory/variants/'.$variant->id.'/adjust', [
                'quantity_delta' => 4,
                'note' => 'Cycle count correction',
            ])
            ->assertOk();

        $this->actingAs($admin)
            ->getJson('/admin/inventory/history/data')
            ->assertOk()
            ->assertJsonPath('message', 'Inventory history fetched successfully.')
            ->assertJsonPath('data.0.product_variant_id', $variant->id)
            ->assertJsonPath('data.0.product_name', $variant->product->name)
            ->assertJsonPath('data.0.variant_name', $variant->name)
            ->assertJsonPath('data.0.sku', $variant->sku)
            ->assertJsonPath('data.0.actor_name', $admin->name)
            ->assertJsonPath('data.0.type', 'adjustment')
            ->assertJsonPath('data.0.quantity_delta', 4)
            ->assertJsonPath('data.0.quantity_on_hand_after', 14)
            ->assertJsonPath('data.0.quantity_reserved_after', 2)
            ->assertJsonPath('data.0.note', 'Cycle count correction');
    }

    public function test_admin_can_reserve_stock_and_log_transaction(): void
    {
        $admin = $this->adminWithRole('admin');
        [$variant, $stock] = $this->stock(onHand: 10, reserved: 2);

        $this->actingAs($admin)
            ->postJson('/admin/inventory/variants/'.$variant->id.'/reserve', [
                'quantity' => 3,
                'note' => 'Checkout started',
                'metadata' => ['cart_id' => 123],
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Stock reserved successfully.')
            ->assertJsonPath('data.quantity_on_hand', 10)
            ->assertJsonPath('data.quantity_reserved', 5)
            ->assertJsonPath('data.available_quantity', 5);

        $stock->refresh();

        $this->assertSame(5, $stock->quantity_reserved);
        $this->assertDatabaseHas('inventory_transactions', [
            'inventory_stock_id' => $stock->id,
            'product_variant_id' => $variant->id,
            'actor_id' => $admin->id,
            'type' => 'reservation',
            'quantity_delta' => -3,
            'quantity_on_hand_after' => 10,
            'quantity_reserved_after' => 5,
            'note' => 'Checkout started',
        ]);
    }

    public function test_admin_can_release_reserved_stock_and_log_transaction(): void
    {
        $admin = $this->adminWithRole('admin');
        [$variant, $stock] = $this->stock(onHand: 10, reserved: 5);

        $this->actingAs($admin)
            ->postJson('/admin/inventory/variants/'.$variant->id.'/release', [
                'quantity' => 3,
                'note' => 'Checkout cancelled',
                'metadata' => ['cart_id' => 123],
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Stock released successfully.')
            ->assertJsonPath('data.quantity_on_hand', 10)
            ->assertJsonPath('data.quantity_reserved', 2)
            ->assertJsonPath('data.available_quantity', 8);

        $stock->refresh();

        $this->assertSame(2, $stock->quantity_reserved);
        $this->assertDatabaseHas('inventory_transactions', [
            'inventory_stock_id' => $stock->id,
            'product_variant_id' => $variant->id,
            'actor_id' => $admin->id,
            'type' => 'release',
            'quantity_delta' => 3,
            'quantity_on_hand_after' => 10,
            'quantity_reserved_after' => 2,
            'note' => 'Checkout cancelled',
        ]);
    }

    public function test_reservation_cannot_exceed_available_stock_without_backorder(): void
    {
        $admin = $this->adminWithRole('admin');
        [$variant] = $this->stock(onHand: 5, reserved: 4);

        $this->actingAs($admin)
            ->postJson('/admin/inventory/variants/'.$variant->id.'/reserve', [
                'quantity' => 2,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');
    }

    public function test_adjustment_cannot_reduce_stock_below_reserved_quantity(): void
    {
        $admin = $this->adminWithRole('admin');
        [$variant] = $this->stock(onHand: 10, reserved: 8);

        $this->actingAs($admin)
            ->postJson('/admin/inventory/variants/'.$variant->id.'/adjust', [
                'quantity_delta' => -5,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantity_delta');
    }

    public function test_release_cannot_exceed_reserved_stock(): void
    {
        $admin = $this->adminWithRole('admin');
        [$variant] = $this->stock(onHand: 10, reserved: 2);

        $this->actingAs($admin)
            ->postJson('/admin/inventory/variants/'.$variant->id.'/release', [
                'quantity' => 3,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');
    }

    public function test_admin_api_can_adjust_stock(): void
    {
        $admin = $this->adminWithRole('admin');
        $token = $admin->createToken('admin-api')->plainTextToken;
        [$variant] = $this->stock(onHand: 3, reserved: 0);

        $this->withToken($token)
            ->postJson('/api/v1/inventory/variants/'.$variant->id.'/adjust', [
                'quantity_delta' => 7,
            ])
            ->assertOk()
            ->assertJsonPath('data.quantity_on_hand', 10);
    }

    public function test_stock_mutation_creates_missing_stock_row_inside_lock(): void
    {
        $admin = $this->adminWithRole('admin');
        $variant = $this->variant();

        $this->assertDatabaseMissing('inventory_stocks', [
            'product_variant_id' => $variant->id,
        ]);

        $this->actingAs($admin)
            ->postJson('/admin/inventory/variants/'.$variant->id.'/adjust', [
                'quantity_delta' => 4,
            ])
            ->assertOk()
            ->assertJsonPath('data.quantity_on_hand', 4);

        $this->assertDatabaseHas('inventory_stocks', [
            'product_variant_id' => $variant->id,
            'quantity_on_hand' => 4,
        ]);
        $this->assertSame(1, InventoryStock::query()->where('product_variant_id', $variant->id)->count());
    }

    public function test_admin_api_can_release_stock(): void
    {
        $admin = $this->adminWithRole('admin');
        $token = $admin->createToken('admin-api')->plainTextToken;
        [$variant] = $this->stock(onHand: 10, reserved: 4);

        $this->withToken($token)
            ->postJson('/api/v1/inventory/variants/'.$variant->id.'/release', [
                'quantity' => 2,
            ])
            ->assertOk()
            ->assertJsonPath('data.quantity_reserved', 2)
            ->assertJsonPath('data.available_quantity', 8);
    }

    public function test_manager_can_view_inventory_but_cannot_mutate_stock(): void
    {
        $manager = $this->adminWithRole('manager');
        [$variant] = $this->stock(onHand: 10, reserved: 0);

        $this->actingAs($manager)
            ->postJson('/admin/inventory/variants/'.$variant->id.'/adjust', [
                'quantity_delta' => 1,
            ])
            ->assertForbidden();

        $this->actingAs($manager)
            ->postJson('/admin/inventory/variants/'.$variant->id.'/reserve', [
                'quantity' => 1,
            ])
            ->assertForbidden();

        $this->actingAs($manager)
            ->postJson('/admin/inventory/variants/'.$variant->id.'/release', [
                'quantity' => 1,
            ])
            ->assertForbidden();
    }

    public function test_inventory_routes_have_expected_boundaries(): void
    {
        foreach ([
            'admin.inventory.index' => 'inventory.view',
            'admin.inventory.data' => 'inventory.view',
            'admin.inventory.low-stock.data' => 'inventory.view',
            'admin.inventory.history' => 'inventory.view',
            'admin.inventory.history.data' => 'inventory.view',
            'admin.inventory.variants.adjust' => 'inventory.adjust',
            'admin.inventory.variants.reserve' => 'inventory.reserve',
            'admin.inventory.variants.release' => 'inventory.release',
        ] as $routeName => $permission) {
            $middleware = $this->middlewareFor($routeName);

            $this->assertRouteHasMiddleware($middleware, 'auth');
            $this->assertRouteHasMiddleware($middleware, 'admin');
            $this->assertRouteHasMiddleware($middleware, 'can:'.$permission);
        }

        foreach ([
            'api.v1.inventory.variants.adjust' => 'inventory.adjust',
            'api.v1.inventory.variants.reserve' => 'inventory.reserve',
            'api.v1.inventory.variants.release' => 'inventory.release',
        ] as $routeName => $permission) {
            $middleware = $this->middlewareFor($routeName);

            $this->assertRouteHasMiddleware($middleware, 'auth:sanctum');
            $this->assertRouteHasMiddleware($middleware, 'admin');
            $this->assertRouteHasMiddleware($middleware, 'can:'.$permission);
        }
    }

    /**
     * @return array{0: ProductVariant, 1: InventoryStock}
     */
    private function stock(int $onHand, int $reserved): array
    {
        $variant = $this->variant();
        $stock = InventoryStock::query()->create([
            'product_variant_id' => $variant->id,
            'quantity_on_hand' => $onHand,
            'quantity_reserved' => $reserved,
        ]);

        return [$variant, $stock];
    }

    private function variant(): ProductVariant
    {
        $product = Product::factory()->create();

        return ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => 'Default',
            'sku' => 'SKU-'.fake()->unique()->numberBetween(1000, 9999),
            'price' => 100,
            'is_default' => true,
            'status' => 'active',
        ]);
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
