<?php

namespace Tests\Feature;

use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerProfileAddressTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_view_and_update_profile(): void
    {
        [$customer, $token] = $this->customerWithToken();

        $this->withToken($token)
            ->getJson('/api/v1/customer/profile')
            ->assertOk()
            ->assertJsonPath('data.email', 'customer@example.com');

        $this->withToken($token)
            ->putJson('/api/v1/customer/profile', [
                'name' => 'Updated Customer',
                'email' => 'updated@example.com',
                'phone' => '+8801700000000',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Customer profile updated successfully.')
            ->assertJsonPath('data.name', 'Updated Customer')
            ->assertJsonPath('data.email', 'updated@example.com');

        $customer->refresh();

        $this->assertSame('Updated Customer', $customer->name);
        $this->assertSame('updated@example.com', $customer->email);
        $this->assertSame('+8801700000000', $customer->phone);
    }

    public function test_customer_profile_rejects_duplicate_email(): void
    {
        User::factory()->create([
            'email' => 'taken@example.com',
            'is_admin' => false,
            'status' => 'active',
        ]);
        [, $token] = $this->customerWithToken();

        $this->withToken($token)
            ->putJson('/api/v1/customer/profile', [
                'name' => 'Customer',
                'email' => 'taken@example.com',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_customer_address_table_and_relationship_exist(): void
    {
        $this->assertTrue(Schema::hasTable('customer_addresses'));
        $this->assertTrue(Schema::hasColumn('customer_addresses', 'user_id'));
        $this->assertTrue(Schema::hasColumn('customer_addresses', 'recipient_name'));
        $this->assertTrue(Schema::hasColumn('customer_addresses', 'is_default_shipping'));

        [$customer] = $this->customerWithToken();
        $address = CustomerAddress::query()->create($this->addressPayload([
            'user_id' => $customer->id,
        ]));

        $this->assertTrue($address->customer->is($customer));
        $this->assertTrue($customer->customerAddresses->first()->is($address));
    }

    public function test_customer_can_manage_own_addresses(): void
    {
        [, $token] = $this->customerWithToken();

        $this->withToken($token)
            ->postJson('/api/v1/customer/addresses', $this->addressPayload([
                'label' => 'Home',
                'is_default_shipping' => true,
                'is_default_billing' => true,
            ]))
            ->assertCreated()
            ->assertJsonPath('message', 'Customer address created successfully.')
            ->assertJsonPath('data.label', 'Home')
            ->assertJsonPath('data.is_default_shipping', true);

        $addressId = CustomerAddress::query()->firstOrFail()->id;

        $this->withToken($token)
            ->getJson('/api/v1/customer/addresses')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->withToken($token)
            ->getJson('/api/v1/customer/addresses/'.$addressId)
            ->assertOk()
            ->assertJsonPath('data.id', $addressId);

        $this->withToken($token)
            ->putJson('/api/v1/customer/addresses/'.$addressId, $this->addressPayload([
                'label' => 'Office',
                'city' => 'Chattogram',
            ]))
            ->assertOk()
            ->assertJsonPath('data.label', 'Office')
            ->assertJsonPath('data.city', 'Chattogram');

        $this->withToken($token)
            ->deleteJson('/api/v1/customer/addresses/'.$addressId)
            ->assertOk()
            ->assertJsonPath('message', 'Customer address deleted successfully.');

        $this->assertDatabaseMissing('customer_addresses', [
            'id' => $addressId,
        ]);
    }

    public function test_customer_address_defaults_are_unique_per_customer(): void
    {
        [, $token] = $this->customerWithToken();

        $this->withToken($token)
            ->postJson('/api/v1/customer/addresses', $this->addressPayload([
                'label' => 'Home',
                'is_default_shipping' => true,
            ]))
            ->assertCreated();

        $first = CustomerAddress::query()->firstOrFail();

        $this->withToken($token)
            ->postJson('/api/v1/customer/addresses', $this->addressPayload([
                'label' => 'Office',
                'is_default_shipping' => true,
            ]))
            ->assertCreated();

        $first->refresh();
        $second = CustomerAddress::query()->where('label', 'Office')->firstOrFail();

        $this->assertFalse($first->is_default_shipping);
        $this->assertTrue($second->is_default_shipping);
    }

    public function test_customer_cannot_access_another_customers_address(): void
    {
        [$owner] = $this->customerWithToken('owner@example.com');
        [, $otherToken] = $this->customerWithToken('other@example.com');
        $address = CustomerAddress::query()->create($this->addressPayload([
            'user_id' => $owner->id,
        ]));

        $this->withToken($otherToken)
            ->getJson('/api/v1/customer/addresses/'.$address->id)
            ->assertNotFound();

        $this->withToken($otherToken)
            ->putJson('/api/v1/customer/addresses/'.$address->id, $this->addressPayload())
            ->assertNotFound();

        $this->withToken($otherToken)
            ->deleteJson('/api/v1/customer/addresses/'.$address->id)
            ->assertNotFound();
    }

    public function test_customer_profile_and_address_routes_have_expected_boundaries(): void
    {
        foreach ([
            'api.v1.customer.profile.show',
            'api.v1.customer.profile.update',
            'api.v1.customer.addresses.index',
            'api.v1.customer.addresses.store',
            'api.v1.customer.addresses.show',
            'api.v1.customer.addresses.update',
            'api.v1.customer.addresses.destroy',
        ] as $routeName) {
            $middleware = $this->middlewareFor($routeName);

            $this->assertRouteHasMiddleware($middleware, 'auth:sanctum');
            $this->assertRouteHasMiddleware($middleware, 'customer.token');
            $this->assertRouteHasMiddleware($middleware, 'throttle:auth.session');
        }
    }

    /**
     * @return array{0: User, 1: string}
     */
    private function customerWithToken(string $email = 'customer@example.com'): array
    {
        $customer = User::factory()->create([
            'name' => 'Customer',
            'email' => $email,
            'password' => Hash::make('password123'),
            'is_admin' => false,
            'status' => 'active',
        ]);

        return [$customer, $customer->createToken('storefront', ['customer'], now()->addHour())->plainTextToken];
    }

    private function addressPayload(array $overrides = []): array
    {
        return array_replace([
            'label' => 'Home',
            'recipient_name' => 'Customer One',
            'phone' => '01700000000',
            'address_line_1' => 'Road 1, House 2',
            'address_line_2' => null,
            'city' => 'Dhaka',
            'state' => 'Dhaka',
            'postal_code' => '1207',
            'country' => 'BD',
            'is_default_shipping' => false,
            'is_default_billing' => false,
        ], $overrides);
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
