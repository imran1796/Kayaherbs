<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RbacModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_spatie_role_and_permission_tables_exist(): void
    {
        $this->assertTrue(Schema::hasTable('roles'));
        $this->assertTrue(Schema::hasTable('permissions'));
        $this->assertTrue(Schema::hasTable('model_has_roles'));
        $this->assertTrue(Schema::hasTable('model_has_permissions'));
        $this->assertTrue(Schema::hasTable('role_has_permissions'));
    }

    public function test_rbac_seeder_creates_baseline_roles_and_permissions(): void
    {
        $this->seed(RbacSeeder::class);

        $this->assertDatabaseHas('roles', [
            'name' => 'super_admin',
            'guard_name' => 'web',
        ]);
        $this->assertDatabaseHas('roles', [
            'name' => 'customer',
            'guard_name' => 'web',
        ]);
        $this->assertDatabaseHas('permissions', [
            'name' => 'roles.view',
            'guard_name' => 'web',
        ]);
        $this->assertDatabaseHas('permissions', [
            'name' => 'customer.account.view',
            'guard_name' => 'web',
        ]);
        $this->assertDatabaseHas('permissions', [
            'name' => 'inventory.adjust',
            'guard_name' => 'web',
        ]);

        $superAdmin = Role::findByName('super_admin');
        $admin = Role::findByName('admin');
        $manager = Role::findByName('manager');
        $support = Role::findByName('support');
        $customer = Role::findByName('customer');

        $this->assertTrue($superAdmin->hasPermissionTo('permissions.delete'));
        $this->assertTrue($admin->hasPermissionTo('inventory.adjust'));
        $this->assertTrue($admin->hasPermissionTo('inventory.reserve'));
        $this->assertTrue($manager->hasPermissionTo('inventory.view'));
        $this->assertFalse($manager->hasPermissionTo('inventory.adjust'));
        $this->assertTrue($support->hasPermissionTo('inventory.view'));
        $this->assertFalse($support->hasPermissionTo('inventory.release'));
        $this->assertTrue($customer->hasPermissionTo('customer.account.view'));
        $this->assertFalse($customer->hasPermissionTo('inventory.view'));
        $this->assertFalse($customer->hasPermissionTo('admin.dashboard.view'));
    }

    public function test_user_can_receive_roles_and_permissions_through_spatie(): void
    {
        $this->seed(RbacSeeder::class);

        $user = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);

        $user->assignRole('admin');

        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->can('users.view'));
        $this->assertFalse($user->can('permissions.delete'));
    }

    public function test_direct_permission_assignment_uses_project_permission_model(): void
    {
        Permission::findOrCreate('reports.view');

        $user = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);

        $user->givePermissionTo('reports.view');

        $this->assertTrue($user->can('reports.view'));
        $this->assertInstanceOf(Permission::class, Permission::findByName('reports.view'));
    }

    public function test_database_seeder_assigns_super_admin_role_to_seed_admin(): void
    {
        $this->seed();

        $admin = User::query()
            ->where('email', config('admin.seed.email'))
            ->firstOrFail();

        $this->assertTrue($admin->hasRole('super_admin'));
        $this->assertTrue($admin->can('roles.delete'));
        $this->assertTrue($admin->is_admin);
    }
}
