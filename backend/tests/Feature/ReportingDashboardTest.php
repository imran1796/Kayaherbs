<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ReportingDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RbacSeeder::class);
    }

    public function test_admin_can_fetch_dashboard_kpis(): void
    {
        $admin = $this->adminWithPermission('reports.view');
        $customer = User::factory()->create();
        $this->orderFor($customer, status: 'pending', paymentStatus: 'pending', total: '100.00', placedAt: '2026-04-01 10:00:00');
        $this->orderFor($customer, status: 'delivered', paymentStatus: 'paid', total: '200.00', placedAt: '2026-04-02 10:00:00');
        $this->orderFor($customer, status: 'cancelled', paymentStatus: 'failed', total: '300.00', placedAt: '2026-04-03 10:00:00');

        $this->withToken($admin->createToken('admin-api')->plainTextToken)
            ->getJson('/api/v1/reports/dashboard')
            ->assertOk()
            ->assertJsonPath('data.total_orders', 3)
            ->assertJsonPath('data.pending_orders', 1)
            ->assertJsonPath('data.delivered_orders', 1)
            ->assertJsonPath('data.cancelled_orders', 1)
            ->assertJsonPath('data.gross_sales', '300.00')
            ->assertJsonPath('data.paid_sales', '200.00')
            ->assertJsonPath('data.average_order_value', '100.00');
    }

    public function test_orders_report_returns_status_counts_with_date_filter(): void
    {
        $admin = $this->adminWithPermission('reports.view');
        $customer = User::factory()->create();
        $this->orderFor($customer, status: 'pending', paymentStatus: 'pending', total: '100.00', placedAt: '2026-04-01 10:00:00');
        $this->orderFor($customer, status: 'delivered', paymentStatus: 'paid', total: '200.00', placedAt: '2026-04-02 10:00:00');
        $this->orderFor($customer, status: 'delivered', paymentStatus: 'paid', total: '250.00', placedAt: '2026-04-05 10:00:00');

        $this->withToken($admin->createToken('admin-api')->plainTextToken)
            ->getJson('/api/v1/reports/orders?from=2026-04-02&to=2026-04-05')
            ->assertOk()
            ->assertJsonPath('data.total_orders', 2)
            ->assertJsonPath('data.status_counts.delivered', 2)
            ->assertJsonMissingPath('data.status_counts.pending');
    }

    public function test_sales_report_returns_daily_sales_rows(): void
    {
        $admin = $this->adminWithPermission('reports.view');
        $customer = User::factory()->create();
        $this->orderFor($customer, status: 'delivered', paymentStatus: 'paid', total: '200.00', placedAt: '2026-04-02 10:00:00');
        $this->orderFor($customer, status: 'processing', paymentStatus: 'pending', total: '150.00', placedAt: '2026-04-02 11:00:00');
        $this->orderFor($customer, status: 'cancelled', paymentStatus: 'failed', total: '300.00', placedAt: '2026-04-03 10:00:00');

        $this->withToken($admin->createToken('admin-api')->plainTextToken)
            ->getJson('/api/v1/reports/sales')
            ->assertOk()
            ->assertJsonPath('data.gross_sales', '350.00')
            ->assertJsonPath('data.paid_sales', '200.00')
            ->assertJsonPath('data.daily.0.date', '2026-04-02')
            ->assertJsonPath('data.daily.0.orders_count', 2)
            ->assertJsonPath('data.daily.0.gross_sales', '350.00')
            ->assertJsonPath('data.daily.0.paid_sales', '200.00');
    }

    public function test_reports_require_reports_view_permission(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);

        $this->withToken($admin->createToken('admin-api')->plainTextToken)
            ->getJson('/api/v1/reports/dashboard')
            ->assertForbidden();
    }

    public function test_inventory_report_returns_stock_summary_and_low_stock_rows(): void
    {
        $admin = $this->adminWithPermission('reports.view');
        $lowVariant = $this->variant('Low Stock Product', 'LOW-1');
        $healthyVariant = $this->variant('Healthy Product', 'OK-1');

        InventoryStock::query()->create([
            'product_variant_id' => $lowVariant->id,
            'quantity_on_hand' => 5,
            'quantity_reserved' => 3,
            'low_stock_threshold' => 2,
            'track_inventory' => true,
        ]);
        InventoryStock::query()->create([
            'product_variant_id' => $healthyVariant->id,
            'quantity_on_hand' => 20,
            'quantity_reserved' => 4,
            'low_stock_threshold' => 5,
            'track_inventory' => true,
        ]);

        $this->withToken($admin->createToken('admin-api')->plainTextToken)
            ->getJson('/api/v1/reports/inventory')
            ->assertOk()
            ->assertJsonPath('data.summary.tracked_variants', 2)
            ->assertJsonPath('data.summary.total_on_hand', 25)
            ->assertJsonPath('data.summary.total_reserved', 7)
            ->assertJsonPath('data.summary.total_available', 18)
            ->assertJsonPath('data.summary.low_stock_count', 1)
            ->assertJsonPath('data.low_stock.0.product_name', 'Low Stock Product')
            ->assertJsonPath('data.low_stock.0.available_quantity', 2);
    }

    public function test_customer_report_returns_customer_summary_and_top_customers(): void
    {
        $admin = $this->adminWithPermission('reports.view');
        $firstCustomer = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
            'name' => 'First Customer',
            'email' => 'first@example.com',
        ]);
        $secondCustomer = User::factory()->create([
            'is_admin' => false,
            'status' => 'inactive',
            'name' => 'Second Customer',
            'email' => 'second@example.com',
        ]);
        User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
        ]);
        $this->orderFor($firstCustomer, status: 'delivered', paymentStatus: 'paid', total: '200.00', placedAt: '2026-04-02 10:00:00');
        $this->orderFor($firstCustomer, status: 'processing', paymentStatus: 'pending', total: '150.00', placedAt: '2026-04-04 10:00:00');
        $this->orderFor($secondCustomer, status: 'cancelled', paymentStatus: 'failed', total: '500.00', placedAt: '2026-04-05 10:00:00');

        $this->withToken($admin->createToken('admin-api')->plainTextToken)
            ->getJson('/api/v1/reports/customers?from=2026-04-01&to=2026-04-30')
            ->assertOk()
            ->assertJsonPath('data.summary.total_customers', 3)
            ->assertJsonPath('data.summary.active_customers', 2)
            ->assertJsonPath('data.summary.customers_with_orders', 2)
            ->assertJsonPath('data.summary.repeat_customers', 1)
            ->assertJsonPath('data.top_customers.0.name', 'First Customer')
            ->assertJsonPath('data.top_customers.0.orders_count', 2)
            ->assertJsonPath('data.top_customers.0.total_spent', '350.00');
    }

    public function test_admin_can_view_sales_report_screen(): void
    {
        $admin = $this->adminWithPermission('reports.view');

        $this->actingAs($admin)
            ->get('/admin/reports/sales')
            ->assertOk()
            ->assertSee('Sales Report')
            ->assertSee('sales-report-filter-form')
            ->assertSee('sales-report-table-body')
            ->assertSee('export-sales-report')
            ->assertSee('Inventory')
            ->assertSee('Customers');
    }

    public function test_admin_can_view_orders_report_screen(): void
    {
        $admin = $this->adminWithPermission('reports.view');

        $this->actingAs($admin)
            ->get('/admin/reports/orders')
            ->assertOk()
            ->assertSee('Orders Report')
            ->assertSee('orders-report-filter-form')
            ->assertSee('orders-report-table-body')
            ->assertSee('export-orders-report')
            ->assertSee('Inventory')
            ->assertSee('Customers');
    }

    public function test_admin_can_view_inventory_report_screen(): void
    {
        $admin = $this->adminWithPermission('reports.view');

        $this->actingAs($admin)
            ->get('/admin/reports/inventory')
            ->assertOk()
            ->assertSee('Inventory Report')
            ->assertSee('inventory-report-table-body')
            ->assertSee('tracked-variants-value')
            ->assertSee('export-inventory-report');
    }

    public function test_admin_can_view_customer_report_screen(): void
    {
        $admin = $this->adminWithPermission('reports.view');

        $this->actingAs($admin)
            ->get('/admin/reports/customers')
            ->assertOk()
            ->assertSee('Customer Report')
            ->assertSee('customer-report-filter-form')
            ->assertSee('customer-report-table-body')
            ->assertSee('export-customer-report');
    }

    public function test_admin_report_web_data_routes_return_reports(): void
    {
        $admin = $this->adminWithPermission('reports.view');
        $customer = User::factory()->create();
        $this->orderFor($customer, status: 'delivered', paymentStatus: 'paid', total: '200.00', placedAt: '2026-04-02 10:00:00');

        $this->actingAs($admin)
            ->getJson('/admin/reports/sales/data?from=2026-04-01&to=2026-04-30')
            ->assertOk()
            ->assertJsonPath('data.gross_sales', '200.00')
            ->assertJsonPath('data.daily.0.date', '2026-04-02');

        $this->actingAs($admin)
            ->getJson('/admin/reports/orders/data?from=2026-04-01&to=2026-04-30')
            ->assertOk()
            ->assertJsonPath('data.total_orders', 1)
            ->assertJsonPath('data.status_counts.delivered', 1);
    }

    public function test_admin_report_web_data_routes_return_inventory_and_customer_reports(): void
    {
        $admin = $this->adminWithPermission('reports.view');
        $variant = $this->variant('Low Product', 'LOW-REPORT-1');
        InventoryStock::query()->create([
            'product_variant_id' => $variant->id,
            'quantity_on_hand' => 3,
            'quantity_reserved' => 1,
            'low_stock_threshold' => 5,
            'track_inventory' => true,
        ]);
        $customer = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
            'name' => 'Report Customer',
            'email' => 'report@example.com',
        ]);
        $this->orderFor($customer, status: 'delivered', paymentStatus: 'paid', total: '120.00', placedAt: '2026-04-02 10:00:00');

        $this->actingAs($admin)
            ->getJson('/admin/reports/inventory/data')
            ->assertOk()
            ->assertJsonPath('data.summary.tracked_variants', 1)
            ->assertJsonPath('data.low_stock.0.product_name', 'Low Product');

        $this->actingAs($admin)
            ->getJson('/admin/reports/customers/data?from=2026-04-01&to=2026-04-30')
            ->assertOk()
            ->assertJsonPath('data.summary.total_customers', 1)
            ->assertJsonPath('data.top_customers.0.name', 'Report Customer');
    }

    public function test_admin_report_web_routes_require_reports_permission(): void
    {
        foreach ([
            'admin.reports.sales',
            'admin.reports.sales.data',
            'admin.reports.orders',
            'admin.reports.orders.data',
            'admin.reports.inventory',
            'admin.reports.inventory.data',
            'admin.reports.customers',
            'admin.reports.customers.data',
            'admin.reports.export',
        ] as $routeName) {
            $middleware = $this->middlewareFor($routeName);

            $this->assertRouteHasMiddleware($middleware, 'auth');
            $this->assertRouteHasMiddleware($middleware, 'admin');
            $this->assertRouteHasMiddleware($middleware, 'can:reports.view');
        }
    }

    public function test_admin_can_download_sales_report_csv(): void
    {
        $admin = $this->adminWithPermission('reports.view');
        $customer = User::factory()->create();
        $this->orderFor($customer, status: 'delivered', paymentStatus: 'paid', total: '200.00', placedAt: '2026-04-02 10:00:00');

        $response = $this->actingAs($admin)
            ->get('/admin/reports/sales/export?from=2026-04-01&to=2026-04-30')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('date,orders_count,gross_sales,paid_sales', $content);
        $this->assertStringContainsString('2026-04-02,1,200.00,200.00', $content);
    }

    public function test_admin_can_download_inventory_report_csv(): void
    {
        $admin = $this->adminWithPermission('reports.view');
        $variant = $this->variant('Low Export Product', 'LOW-EXPORT-1');
        InventoryStock::query()->create([
            'product_variant_id' => $variant->id,
            'quantity_on_hand' => 3,
            'quantity_reserved' => 1,
            'low_stock_threshold' => 5,
            'track_inventory' => true,
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/reports/inventory/export')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('product_name,variant_name,sku,quantity_on_hand,quantity_reserved,available_quantity,low_stock_threshold', $content);
        $this->assertStringContainsString('Low Export Product', $content);
    }

    private function adminWithPermission(string $permission): User
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);

        $admin->givePermissionTo($permission);

        return $admin;
    }

    /**
     * @return list<string>
     */
    private function middlewareFor(string $routeName): array
    {
        $route = \Illuminate\Support\Facades\Route::getRoutes()->getByName($routeName);

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

    private function orderFor(
        User $customer,
        string $status,
        string $paymentStatus,
        string $total,
        string $placedAt
    ): Order {
        return Order::query()->create([
            'order_number' => 'ORD-REP-'.strtoupper(uniqid()),
            'user_id' => $customer->id,
            'idempotency_key' => 'report-'.uniqid(),
            'status' => $status,
            'payment_status' => $paymentStatus,
            'fulfillment_status' => 'unfulfilled',
            'shipping_method_code' => 'standard',
            'shipping_method_name' => 'Standard',
            'payment_method_code' => 'cod',
            'payment_method_name' => 'Cash on Delivery',
            'subtotal' => $total,
            'shipping_total' => '0.00',
            'grand_total' => $total,
            'currency' => 'BDT',
            'shipping_address' => ['city' => 'Dhaka', 'country' => 'BD'],
            'billing_address' => ['city' => 'Dhaka', 'country' => 'BD'],
            'placed_at' => $placedAt,
        ]);
    }

    private function variant(string $productName, string $sku): ProductVariant
    {
        $product = Product::factory()->create([
            'name' => $productName,
            'status' => 'published',
        ]);

        return ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => 'Default',
            'sku' => $sku,
            'price' => 100,
            'is_default' => true,
            'status' => 'active',
        ]);
    }
}
