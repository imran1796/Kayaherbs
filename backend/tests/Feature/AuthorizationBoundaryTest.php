<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AuthorizationBoundaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RbacSeeder::class);
    }

    public function test_public_auth_routes_are_public_but_throttled(): void
    {
        foreach ([
            'api.v1.auth.customer.register' => 'auth.register',
            'api.v1.auth.customer.login' => 'auth.login',
            'api.v1.auth.customer.password.forgot' => 'auth.password-reset',
            'api.v1.auth.customer.password.reset' => 'auth.password-reset',
        ] as $routeName => $throttleName) {
            $middleware = $this->middlewareFor($routeName);

            $this->assertRouteHasMiddleware($middleware, 'throttle:'.$throttleName);
            $this->assertRouteMissingMiddleware($middleware, 'auth:sanctum');
            $this->assertRouteMissingMiddleware($middleware, 'admin');
            $this->assertRouteMissingMiddleware($middleware, 'customer.token');
        }
    }

    public function test_customer_auth_routes_require_customer_token_boundary(): void
    {
        foreach ([
            'api.v1.auth.customer.me',
            'api.v1.auth.customer.logout',
            'api.v1.auth.customer.logout.all',
        ] as $routeName) {
            $middleware = $this->middlewareFor($routeName);

            $this->assertRouteHasMiddleware($middleware, 'auth:sanctum');
            $this->assertRouteHasMiddleware($middleware, 'customer.token');
            $this->assertRouteHasMiddleware($middleware, 'throttle:auth.session');
            $this->assertRouteMissingMiddleware($middleware, 'admin');
        }
    }

    public function test_admin_web_routes_require_admin_and_permissions_except_auth_forms(): void
    {
        $this->assertRouteHasMiddleware($this->middlewareFor('admin.dashboard'), 'auth');
        $this->assertRouteHasMiddleware($this->middlewareFor('admin.dashboard'), 'admin');
        $this->assertRouteHasMiddleware($this->middlewareFor('admin.dashboard'), 'can:admin.dashboard.view');

        $this->assertRouteHasMiddleware($this->middlewareFor('admin.users.index'), 'auth');
        $this->assertRouteHasMiddleware($this->middlewareFor('admin.users.index'), 'admin');
        $this->assertRouteHasMiddleware($this->middlewareFor('admin.users.index'), 'can:users.view');

        foreach ([
            'admin.login',
            'admin.login.store',
            'admin.password.request',
            'admin.password.email',
            'admin.password.reset',
            'admin.password.update',
        ] as $routeName) {
            $middleware = $this->middlewareFor($routeName);

            $this->assertRouteHasMiddleware($middleware, 'guest');
            $this->assertRouteMissingMiddleware($middleware, 'admin');
        }
    }

    public function test_user_module_api_routes_require_admin_boundary_and_action_permissions(): void
    {
        foreach ([
            'api.v1.users.index' => 'users.view',
            'api.v1.users.show' => 'users.view',
            'api.v1.users.store' => 'users.create',
            'api.v1.users.update' => 'users.update',
            'api.v1.users.destroy' => 'users.delete',
        ] as $routeName => $permission) {
            $middleware = $this->middlewareFor($routeName);

            $this->assertRouteHasMiddleware($middleware, 'auth:sanctum');
            $this->assertRouteHasMiddleware($middleware, 'admin');
            $this->assertRouteHasMiddleware($middleware, 'can:'.$permission);
            $this->assertRouteMissingMiddleware($middleware, 'customer.token');
        }
    }

    public function test_customer_token_cannot_cross_into_admin_or_user_modules(): void
    {
        $customer = User::factory()->create([
            'email' => 'customer@example.com',
            'password' => Hash::make('password123'),
            'is_admin' => false,
            'status' => 'active',
        ]);
        $customer->assignRole('customer');
        $token = $customer->createToken('storefront', ['customer'], now()->addHour())->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/users')
            ->assertForbidden();

        $this->actingAs($customer)
            ->getJson('/admin/users')
            ->assertForbidden();
    }

    public function test_admin_token_cannot_cross_into_customer_token_routes(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'is_admin' => true,
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        $this->withToken($admin->createToken('admin-api')->plainTextToken)
            ->getJson('/api/v1/auth/customer/me')
            ->assertForbidden();
    }

    public function test_admin_api_permissions_are_action_specific_for_manager(): void
    {
        $manager = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);
        $manager->assignRole('manager');
        $managerToken = $manager->createToken('admin-api')->plainTextToken;

        $this->withToken($managerToken)
            ->getJson('/api/v1/users')
            ->assertOk();

        $this->withToken($managerToken)
            ->postJson('/api/v1/users', [
                'name' => 'New Admin',
                'email' => 'new-admin@example.com',
                'password' => 'password123',
            ])
            ->assertForbidden();
    }

    public function test_super_admin_can_cross_all_user_api_action_boundaries(): void
    {
        $superAdmin = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);
        $superAdmin->assignRole('super_admin');

        $this->withToken($superAdmin->createToken('admin-api')->plainTextToken)
            ->postJson('/api/v1/users', [
                'name' => 'New Admin',
                'email' => 'new-admin@example.com',
                'password' => 'password123',
                'roles' => ['admin'],
            ])
            ->assertCreated()
            ->assertJsonPath('success', true);

        $createdUser = User::query()->where('email', 'new-admin@example.com')->firstOrFail();

        $this->assertTrue($createdUser->is_admin);
        $this->assertTrue($createdUser->hasRole('admin'));
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
            $this->middlewareContains($middleware, $expected),
            'Expected middleware ['.$expected.'] not found in ['.implode(', ', $middleware).'].'
        );
    }

    /**
     * @param  list<string>  $middleware
     */
    private function assertRouteMissingMiddleware(array $middleware, string $unexpected): void
    {
        $this->assertFalse(
            $this->middlewareContains($middleware, $unexpected),
            'Unexpected middleware ['.$unexpected.'] found in ['.implode(', ', $middleware).'].'
        );
    }

    /**
     * @param  list<string>  $middleware
     */
    private function middlewareContains(array $middleware, string $needle): bool
    {
        foreach ($middleware as $entry) {
            if ($entry === $needle || str_starts_with($entry, $needle)) {
                return true;
            }
        }

        return false;
    }
}
