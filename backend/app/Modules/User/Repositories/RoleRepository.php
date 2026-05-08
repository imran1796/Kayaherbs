<?php

namespace App\Modules\User\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleRepository
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Role::query()
            ->withCount(['users', 'permissions'])
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function findOrFail(int $id): Role
    {
        /** @var Role $role */
        $role = Role::query()
            ->with('permissions')
            ->findOrFail($id);

        return $role;
    }

    public function findByName(string $name): ?Role
    {
        /** @var Role|null $role */
        $role = Role::query()
            ->where('name', $name)
            ->where('guard_name', (string) config('rbac.guard', 'web'))
            ->first();

        return $role;
    }

    /**
     * @return Collection<int, Permission>
     */
    public function permissionsForGuard(): Collection
    {
        return Permission::query()
            ->where('guard_name', (string) config('rbac.guard', 'web'))
            ->orderBy('name')
            ->get();
    }

    public function create(string $name): Role
    {
        return Role::query()->create([
            'name' => $name,
            'guard_name' => (string) config('rbac.guard', 'web'),
        ]);
    }

    public function update(Role $role, string $name): Role
    {
        $role->update(['name' => $name]);

        return $this->findOrFail((int) $role->id);
    }

    /**
     * @param  list<string>  $permissions
     */
    public function syncPermissions(Role $role, array $permissions): Role
    {
        $role->syncPermissions($permissions);

        return $this->findOrFail((int) $role->id);
    }

    public function refreshWithPermissions(Role $role): Role
    {
        return $this->findOrFail((int) $role->id);
    }
}
