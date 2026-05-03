<?php

namespace Tests\Feature;

use App\Models\InventoryStock;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminDashboardWebTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RbacSeeder::class);
    }

    public function test_admin_dashboard_shows_summary_panels_and_loading_states(): void
    {
        $admin = $this->admin();
        $customer = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
        ]);
        $this->orderFor($customer, 'pending');
        $this->orderFor($customer, 'delivered');
        $this->lowStockVariant();

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Order Summary')
            ->assertSee('Customer Summary')
            ->assertSee('Low-Stock Summary')
            ->assertSee('Low-Stock Items')
            ->assertSee('order-summary-loading')
            ->assertSee('customer-summary-loading')
            ->assertSee('low-stock-summary-loading')
            ->assertSee('Low Stock Product')
            ->assertSee('LOW-1');
    }

    private function admin(): User
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);

        $admin->givePermissionTo('admin.dashboard.view');

        return $admin;
    }

    private function orderFor(User $customer, string $status): Order
    {
        return Order::query()->create([
            'order_number' => 'ORD-DASH-'.strtoupper(uniqid()),
            'user_id' => $customer->id,
            'idempotency_key' => 'dashboard-'.uniqid(),
            'status' => $status,
            'payment_status' => $status === 'delivered' ? 'paid' : 'pending',
            'fulfillment_status' => 'unfulfilled',
            'shipping_method_code' => 'standard',
            'shipping_method_name' => 'Standard',
            'payment_method_code' => 'cod',
            'payment_method_name' => 'Cash on Delivery',
            'subtotal' => '100.00',
            'shipping_total' => '0.00',
            'grand_total' => '100.00',
            'currency' => 'BDT',
            'shipping_address' => ['city' => 'Dhaka', 'country' => 'BD'],
            'billing_address' => ['city' => 'Dhaka', 'country' => 'BD'],
            'placed_at' => now(),
        ]);
    }

    private function lowStockVariant(): void
    {
        $product = Product::factory()->create([
            'name' => 'Low Stock Product',
            'status' => 'published',
        ]);
        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => 'Default',
            'sku' => 'LOW-1',
            'price' => 100,
            'is_default' => true,
            'status' => 'active',
        ]);

        InventoryStock::query()->create([
            'product_variant_id' => $variant->id,
            'quantity_on_hand' => 5,
            'quantity_reserved' => 3,
            'low_stock_threshold' => 2,
            'track_inventory' => true,
        ]);
    }
}
