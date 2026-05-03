<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserManagementWebTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RbacSeeder::class);
    }

    public function test_admin_can_open_user_create_and_edit_pages(): void
    {
        $admin = $this->adminWithRole('admin');
        $target = User::factory()->create([
            'name' => 'Existing User',
            'email' => 'existing@example.com',
            'status' => 'active',
        ]);
        $target->assignRole('support');

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertOk()
            ->assertSee('Create user')
            ->assertSee('Edit')
            ->assertSee('Support');

        $this->actingAs($admin)
            ->get('/admin/users/create')
            ->assertOk()
            ->assertSee('Create User');

        $this->actingAs($admin)
            ->get('/admin/users/'.$target->id.'/edit')
            ->assertOk()
            ->assertSee('Edit User')
            ->assertSee('existing@example.com');
    }

    public function test_admin_can_create_user_from_admin_panel(): void
    {
        $admin = $this->adminWithRole('admin');

        $this->actingAs($admin)
            ->postJson('/admin/users', [
                'name' => 'Created User',
                'email' => 'created@example.com',
                'phone' => '+8801700000000',
                'password' => 'password123',
                'status' => 'active',
                'roles' => ['support'],
            ])
            ->assertCreated()
            ->assertJsonPath('data.email', 'created@example.com');

        $user = User::query()->where('email', 'created@example.com')->firstOrFail();

        $this->assertSame('Created User', $user->name);
        $this->assertSame('+8801700000000', $user->phone);
        $this->assertSame('active', $user->status);
        $this->assertTrue($user->is_admin);
        $this->assertTrue($user->hasRole('support'));
    }

    public function test_admin_can_update_user_from_admin_panel(): void
    {
        $admin = $this->adminWithRole('admin');
        $target = User::factory()->create([
            'name' => 'Before Update',
            'email' => 'before@example.com',
            'phone' => null,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->putJson('/admin/users/'.$target->id, [
                'name' => 'After Update',
                'email' => 'after@example.com',
                'phone' => '+8801800000000',
                'password' => '',
                'status' => 'inactive',
                'roles' => ['manager'],
            ])
            ->assertOk()
            ->assertJsonPath('data.email', 'after@example.com');

        $target->refresh();

        $this->assertSame('After Update', $target->name);
        $this->assertSame('after@example.com', $target->email);
        $this->assertSame('+8801800000000', $target->phone);
        $this->assertSame('inactive', $target->status);
        $this->assertTrue($target->is_admin);
        $this->assertTrue($target->hasRole('manager'));
    }

    public function test_manager_can_view_users_but_cannot_create_or_edit_users(): void
    {
        $manager = $this->adminWithRole('manager');
        $target = User::factory()->create();

        $this->actingAs($manager)
            ->get('/admin/users')
            ->assertOk();

        $this->actingAs($manager)
            ->get('/admin/users/create')
            ->assertForbidden();

        $this->actingAs($manager)
            ->postJson('/admin/users', [
                'name' => 'Denied User',
                'email' => 'denied@example.com',
                'password' => 'password123',
                'roles' => ['support'],
            ])
            ->assertForbidden();

        $this->actingAs($manager)
            ->get('/admin/users/'.$target->id.'/edit')
            ->assertForbidden();

        $this->actingAs($manager)
            ->putJson('/admin/users/'.$target->id, [
                'name' => 'Denied Update',
                'email' => 'denied-update@example.com',
                'status' => 'active',
                'roles' => ['support'],
            ])
            ->assertForbidden();
    }

    public function test_admin_cannot_assign_super_admin_role(): void
    {
        $admin = $this->adminWithRole('admin');

        $this->actingAs($admin)
            ->postJson('/admin/users', [
                'name' => 'Blocked User',
                'email' => 'blocked@example.com',
                'password' => 'password123',
                'status' => 'active',
                'roles' => ['super_admin'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('roles.0');
    }

    public function test_user_management_web_routes_have_action_permissions(): void
    {
        $this->assertRouteHasMiddleware($this->middlewareFor('admin.users.index'), 'can:users.view');
        $this->assertRouteHasMiddleware($this->middlewareFor('admin.users.create'), 'can:users.create');
        $this->assertRouteHasMiddleware($this->middlewareFor('admin.users.store'), 'can:users.create');
        $this->assertRouteHasMiddleware($this->middlewareFor('admin.users.edit'), 'can:users.update');
        $this->assertRouteHasMiddleware($this->middlewareFor('admin.users.update'), 'can:users.update');
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
