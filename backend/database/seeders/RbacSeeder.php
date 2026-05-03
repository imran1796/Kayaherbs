<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = (string) config('rbac.guard', 'web');

        foreach (array_keys(config('rbac.permissions', [])) as $permissionName) {
            Permission::findOrCreate($permissionName, $guard);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (config('rbac.roles', []) as $roleName => $roleDefinition) {
            $role = Role::findOrCreate($roleName, $guard);
            $permissions = $roleDefinition['permissions'] ?? [];

            if ($permissions === ['*']) {
                $permissions = array_keys(config('rbac.permissions', []));
            }

            $role->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
