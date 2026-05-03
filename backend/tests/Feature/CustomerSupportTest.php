<?php

namespace Tests\Feature;

use App\Models\CustomerNote;
use App\Models\CustomerTag;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CustomerSupportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RbacSeeder::class);
    }

    public function test_customer_support_tables_and_relationships_exist(): void
    {
        $this->assertTrue(Schema::hasTable('customer_notes'));
        $this->assertTrue(Schema::hasTable('customer_tags'));

        $customer = User::factory()->create(['is_admin' => false]);
        $author = $this->adminWithPermission('customers.notes.create');

        $note = CustomerNote::query()->create([
            'user_id' => $customer->id,
            'author_id' => $author->id,
            'note' => 'Prefers WhatsApp follow up.',
        ]);
        $tag = CustomerTag::query()->create([
            'user_id' => $customer->id,
            'tag' => 'vip',
        ]);

        $this->assertTrue($customer->customerNotes->first()->is($note));
        $this->assertTrue($customer->customerTags->first()->is($tag));
        $this->assertTrue($note->author->is($author));
    }

    public function test_customer_can_view_own_order_history(): void
    {
        $customer = User::factory()->create(['is_admin' => false]);
        $otherCustomer = User::factory()->create(['is_admin' => false]);
        $order = $this->orderFor($customer);
        $otherOrder = $this->orderFor($otherCustomer);

        $token = $customer->createToken('storefront', ['customer'], now()->addHour())->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/customer/orders')
            ->assertOk()
            ->assertJsonPath('data.0.id', $order->id)
            ->assertJsonMissing(['id' => $otherOrder->id]);

        $this->withToken($token)
            ->getJson("/api/v1/customer/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $order->id);

        $this->withToken($token)
            ->getJson("/api/v1/customer/orders/{$otherOrder->id}")
            ->assertNotFound();
    }

    public function test_admin_can_view_customer_support_profile_with_order_linkage(): void
    {
        $admin = $this->adminWithPermission('customers.view');
        $customer = User::factory()->create(['is_admin' => false]);
        $order = $this->orderFor($customer);

        $this->withToken($admin->createToken('admin-api')->plainTextToken)
            ->getJson("/api/v1/customers/{$customer->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $customer->id)
            ->assertJsonPath('data.orders_count', 1)
            ->assertJsonPath('data.orders.0.id', $order->id);
    }

    public function test_admin_can_view_customer_list_screen(): void
    {
        $admin = $this->adminWithPermission('customers.view');

        $this->actingAs($admin)
            ->get('/admin/customers')
            ->assertOk()
            ->assertSee('Customer List')
            ->assertSee('customer-table-body')
            ->assertSee('customer-filter-form');
    }

    public function test_admin_can_view_customer_detail_screen(): void
    {
        $admin = $this->adminWithPermissions([
            'customers.view',
            'customers.update',
            'customers.notes.create',
            'customers.tags.update',
        ]);
        $customer = User::factory()->create(['is_admin' => false]);

        $this->actingAs($admin)
            ->get("/admin/customers/{$customer->id}")
            ->assertOk()
            ->assertSee('Customer Detail')
            ->assertSee('customer-profile-panel')
            ->assertSee('customer-address-table-body')
            ->assertSee('customer-order-history-panel')
            ->assertSee('customer-order-table-body')
            ->assertSee('customer-notes-panel')
            ->assertSee('customer-address-form')
            ->assertSee('customer-note-form')
            ->assertSee('customer-tags-form');
    }

    public function test_admin_can_fetch_customer_detail_data(): void
    {
        $admin = $this->adminWithPermission('customers.view');
        $customer = User::factory()->create([
            'is_admin' => false,
            'name' => 'Amina Rahman',
            'email' => 'amina@example.com',
        ]);
        $order = $this->orderFor($customer);

        CustomerTag::query()->create([
            'user_id' => $customer->id,
            'tag' => 'vip',
        ]);
        CustomerNote::query()->create([
            'user_id' => $customer->id,
            'author_id' => $admin->id,
            'note' => 'Prefers WhatsApp follow up.',
        ]);

        $this->actingAs($admin)
            ->getJson("/admin/customers/{$customer->id}/data")
            ->assertOk()
            ->assertJsonPath('message', 'Customer fetched successfully.')
            ->assertJsonPath('data.id', $customer->id)
            ->assertJsonPath('data.name', 'Amina Rahman')
            ->assertJsonPath('data.orders_count', 1)
            ->assertJsonPath('data.orders.0.id', $order->id)
            ->assertJsonPath('data.orders.0.fulfillment_status', 'unfulfilled')
            ->assertJsonPath('data.orders.0.payment_method.name', 'Cash on Delivery')
            ->assertJsonPath('data.tags.0.tag', 'vip')
            ->assertJsonPath('data.notes.0.note', 'Prefers WhatsApp follow up.');
    }

    public function test_admin_can_manage_customer_addresses_from_web_routes(): void
    {
        $admin = $this->adminWithPermissions(['customers.view', 'customers.update']);
        $customer = User::factory()->create(['is_admin' => false]);

        $createResponse = $this->actingAs($admin)
            ->postJson("/admin/customers/{$customer->id}/addresses", [
                'label' => 'Home',
                'recipient_name' => 'Amina Rahman',
                'phone' => '01710000001',
                'address_line_1' => 'Road 1, House 2',
                'city' => 'Dhaka',
                'country' => 'BD',
                'is_default_shipping' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.label', 'Home')
            ->assertJsonPath('data.is_default_shipping', true);

        $addressId = $createResponse->json('data.id');

        $this->actingAs($admin)
            ->putJson("/admin/customers/{$customer->id}/addresses/{$addressId}", [
                'label' => 'Office',
                'recipient_name' => 'Amina Rahman',
                'phone' => '01710000001',
                'address_line_1' => 'Road 3, House 4',
                'city' => 'Dhaka',
                'country' => 'BD',
                'is_default_billing' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.label', 'Office')
            ->assertJsonPath('data.is_default_billing', true);

        $this->actingAs($admin)
            ->deleteJson("/admin/customers/{$customer->id}/addresses/{$addressId}")
            ->assertOk();

        $this->assertDatabaseMissing('customer_addresses', [
            'id' => $addressId,
        ]);
    }

    public function test_admin_can_manage_customer_tags_and_notes_from_web_routes(): void
    {
        $admin = $this->adminWithPermissions([
            'customers.view',
            'customers.notes.create',
            'customers.tags.update',
        ]);
        $customer = User::factory()->create(['is_admin' => false]);

        $this->actingAs($admin)
            ->postJson("/admin/customers/{$customer->id}/notes", [
                'note' => 'Prefers phone support.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.note', 'Prefers phone support.')
            ->assertJsonPath('data.author.id', $admin->id);

        $this->actingAs($admin)
            ->putJson("/admin/customers/{$customer->id}/tags", [
                'tags' => ['vip', 'follow-up'],
            ])
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->assertDatabaseHas('customer_notes', [
            'user_id' => $customer->id,
            'author_id' => $admin->id,
            'note' => 'Prefers phone support.',
        ]);
        $this->assertDatabaseHas('customer_tags', [
            'user_id' => $customer->id,
            'tag' => 'vip',
        ]);
    }

    public function test_admin_customer_list_can_filter_by_search_and_status(): void
    {
        $admin = $this->adminWithPermission('customers.view');
        $matching = User::factory()->create([
            'is_admin' => false,
            'name' => 'Amina Rahman',
            'email' => 'amina@example.com',
            'phone' => '01710000001',
            'status' => 'active',
        ]);
        User::factory()->create([
            'is_admin' => false,
            'name' => 'Amina Suspended',
            'email' => 'amina-suspended@example.com',
            'status' => 'suspended',
        ]);
        User::factory()->create([
            'is_admin' => false,
            'name' => 'Farhan Ahmed',
            'email' => 'farhan@example.com',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->getJson('/admin/customers/data?search=amina&status=active')
            ->assertOk()
            ->assertJsonPath('message', 'Customers fetched successfully.')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matching->id)
            ->assertJsonPath('data.0.email', 'amina@example.com');
    }

    public function test_admin_customer_web_routes_require_permissions(): void
    {
        foreach ([
            'admin.customers.index',
            'admin.customers.data',
            'admin.customers.show',
            'admin.customers.show.data',
        ] as $routeName) {
            $middleware = $this->middlewareFor($routeName);

            $this->assertRouteHasMiddleware($middleware, 'auth');
            $this->assertRouteHasMiddleware($middleware, 'admin');
            $this->assertRouteHasMiddleware($middleware, 'can:customers.view');
        }

        foreach ([
            'admin.customers.addresses.store',
            'admin.customers.addresses.update',
            'admin.customers.addresses.destroy',
        ] as $routeName) {
            $middleware = $this->middlewareFor($routeName);

            $this->assertRouteHasMiddleware($middleware, 'auth');
            $this->assertRouteHasMiddleware($middleware, 'admin');
            $this->assertRouteHasMiddleware($middleware, 'can:customers.update');
        }

        $this->assertRouteHasMiddleware($this->middlewareFor('admin.customers.notes.store'), 'can:customers.notes.create');
        $this->assertRouteHasMiddleware($this->middlewareFor('admin.customers.tags.sync'), 'can:customers.tags.update');
    }

    public function test_admin_can_add_customer_note(): void
    {
        $admin = $this->adminWithPermission('customers.notes.create');
        $customer = User::factory()->create(['is_admin' => false]);

        $this->withToken($admin->createToken('admin-api')->plainTextToken)
            ->postJson("/api/v1/customers/{$customer->id}/notes", [
                'note' => 'Customer requested phone support.',
                'metadata' => ['source' => 'call'],
            ])
            ->assertCreated()
            ->assertJsonPath('data.note', 'Customer requested phone support.')
            ->assertJsonPath('data.author.id', $admin->id)
            ->assertJsonPath('data.metadata.source', 'call');

        $this->assertDatabaseHas('customer_notes', [
            'user_id' => $customer->id,
            'author_id' => $admin->id,
            'note' => 'Customer requested phone support.',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'event' => 'customer.note.created',
            'actor_id' => $admin->id,
            'auditable_id' => $customer->id,
        ]);
    }

    public function test_admin_can_sync_customer_tags(): void
    {
        $admin = $this->adminWithPermission('customers.tags.update');
        $customer = User::factory()->create(['is_admin' => false]);

        CustomerTag::query()->create([
            'user_id' => $customer->id,
            'tag' => 'old',
        ]);

        $this->withToken($admin->createToken('admin-api')->plainTextToken)
            ->putJson("/api/v1/customers/{$customer->id}/tags", [
                'tags' => ['vip', 'fragile-delivery'],
            ])
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.tag', 'fragile-delivery')
            ->assertJsonPath('data.1.tag', 'vip');

        $this->assertDatabaseMissing('customer_tags', [
            'user_id' => $customer->id,
            'tag' => 'old',
        ]);
        $this->assertDatabaseHas('customer_tags', [
            'user_id' => $customer->id,
            'tag' => 'vip',
        ]);
    }

    public function test_admin_can_update_customer_status(): void
    {
        $admin = $this->adminWithPermission('customers.update');
        $customer = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
        ]);

        $this->withToken($admin->createToken('admin-api')->plainTextToken)
            ->patchJson("/api/v1/customers/{$customer->id}/status", [
                'status' => 'suspended',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'suspended');

        $this->assertSame('suspended', $customer->refresh()->status);
        $this->assertDatabaseHas('audit_events', [
            'event' => 'customer.status.updated',
            'actor_id' => $admin->id,
            'auditable_id' => $customer->id,
        ]);
    }

    public function test_admin_customer_routes_require_permissions(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);
        $customer = User::factory()->create(['is_admin' => false]);

        $this->withToken($admin->createToken('admin-api')->plainTextToken)
            ->getJson("/api/v1/customers/{$customer->id}")
            ->assertForbidden();
    }

    private function adminWithPermission(string $permission): User
    {
        return $this->adminWithPermissions([$permission]);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function adminWithPermissions(array $permissions): User
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);

        $admin->givePermissionTo($permissions);

        return $admin;
    }

    private function orderFor(User $customer): Order
    {
        return Order::query()->create([
            'order_number' => 'ORD-CUSTOMER-'.strtoupper(uniqid()),
            'user_id' => $customer->id,
            'idempotency_key' => 'customer-order-'.uniqid(),
            'status' => 'pending',
            'payment_status' => 'pending',
            'fulfillment_status' => 'unfulfilled',
            'shipping_method_code' => 'standard',
            'shipping_method_name' => 'Standard',
            'payment_method_code' => 'cod',
            'payment_method_name' => 'Cash on Delivery',
            'subtotal' => '100.00',
            'shipping_total' => '60.00',
            'grand_total' => '160.00',
            'currency' => 'BDT',
            'shipping_address' => ['city' => 'Dhaka', 'country' => 'BD'],
            'billing_address' => ['city' => 'Dhaka', 'country' => 'BD'],
            'placed_at' => now(),
        ]);
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
}
