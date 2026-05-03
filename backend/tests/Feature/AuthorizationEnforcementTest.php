<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AuthorizationEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RbacSeeder::class);
    }

    public function test_admin_dashboard_requires_dashboard_permission(): void
    {
        $admin = $this->adminWithRole('dashboard_only');

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk();
    }

    public function test_admin_user_management_requires_users_view_permission(): void
    {
        $admin = $this->adminWithRole('dashboard_only');

        $this->actingAs($admin)
            ->getJson('/admin/users')
            ->assertForbidden();

        $admin->assignRole('manager');

        $this->actingAs($admin)
            ->getJson('/admin/users')
            ->assertOk();
    }

    public function test_admin_web_forbidden_response_shows_unauthorized_screen(): void
    {
        $admin = $this->adminWithRole('dashboard_only');

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertForbidden()
            ->assertSee('Access denied')
            ->assertSee('Your admin account does not have permission to open this page.');
    }

    public function test_super_admin_bypasses_permission_checks(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);

        $admin->assignRole('super_admin');

        $this->assertTrue(Gate::forUser($admin)->allows('permissions.delete'));
        $this->assertTrue(Gate::forUser($admin)->allows('unknown.future.permission'));
    }

    public function test_user_policy_maps_actions_to_permissions(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);
        $target = User::factory()->create();

        $admin->assignRole('admin');

        $this->assertTrue($admin->can('viewAny', User::class));
        $this->assertTrue($admin->can('view', $target));
        $this->assertTrue($admin->can('create', User::class));
        $this->assertTrue($admin->can('update', $target));
        $this->assertTrue($admin->can('delete', $target));
    }

    public function test_user_management_api_requires_authentication(): void
    {
        $this->getJson('/api/v1/users')
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'unauthenticated');
    }

    public function test_user_management_api_rejects_non_admin_token(): void
    {
        $customer = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
        ]);
        $customer->givePermissionTo('users.view');

        $this->withToken($customer->createToken('customer')->plainTextToken)
            ->getJson('/api/v1/users')
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_user_management_api_rejects_admin_without_permission(): void
    {
        $admin = $this->adminWithRole('dashboard_only');

        $this->withToken($admin->createToken('admin-api')->plainTextToken)
            ->getJson('/api/v1/users')
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_user_management_api_allows_admin_with_permission(): void
    {
        $admin = $this->adminWithRole('dashboard_only');
        $admin->assignRole('manager');
        $admin->refresh();

        $this->assertTrue($admin->can('users.view'));

        $this->withToken($admin->createToken('admin-api')->plainTextToken)
            ->getJson('/api/v1/users')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    private function adminWithRole(string $roleName): User
    {
        $role = Role::findOrCreate($roleName, 'web');
        $role->syncPermissions(['admin.dashboard.view']);

        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);

        $admin->assignRole($role);

        return $admin;
    }
}
