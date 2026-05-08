<?php

namespace App\Modules\User\Services;

use App\Core\Services\AuditLogger;
use App\Models\User;
use App\Modules\User\Repositories\RoleRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class RoleService
{
    public function __construct(
        private readonly RoleRepository $roles,
        private readonly AuditLogger $auditLogger
    ) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->roles->paginate($perPage);
    }

    public function findOrFail(int $id): Role
    {
        return $this->roles->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $actor = null): Role
    {
        $name = $this->roleName($data['name'] ?? '');
        $this->ensureRoleIsUnique($name);

        $role = $this->roles->create($name);
        $role = $this->syncPermissions($role, $data);

        $this->auditLogger->record('role.created', $actor, $role, [
            'name' => $role->name,
            'permissions' => $this->permissionNames($role),
        ], guard: 'web');

        return $role;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data, ?User $actor = null): Role
    {
        $role = $this->findOrFail($id);
        $name = $this->roleName($data['name'] ?? '');
        $before = [
            'name' => $role->name,
            'permissions' => $this->permissionNames($role),
        ];

        if ($this->isProtectedRole($role) && $name !== $role->name) {
            throw ValidationException::withMessages([
                'name' => ['Protected baseline roles cannot be renamed.'],
            ]);
        }

        if ($this->hasProtectedPermissionSet($role) && $this->permissionSetChanged($role, $data)) {
            throw ValidationException::withMessages([
                'permissions' => ['The super_admin and default admin permission sets are protected.'],
            ]);
        }

        $this->ensureRoleIsUnique($name, $role->id);

        $updatedRole = $this->roles->update($role, $name);
        $updatedRole = $this->syncPermissions($updatedRole, $data);
        $after = [
            'name' => $updatedRole->name,
            'permissions' => $this->permissionNames($updatedRole),
        ];

        $this->auditLogger->record('role.updated', $actor, $updatedRole, [
            'name' => $updatedRole->name,
            'changed' => array_keys(array_filter([
                'name' => $before['name'] !== $after['name'],
                'permissions' => $before['permissions'] !== $after['permissions'],
            ])),
            'permissions' => $after['permissions'],
        ], guard: 'web');

        return $updatedRole;
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function permissionMatrix(): array
    {
        $labels = config('rbac.permissions', []);

        return $this->roles->permissionsForGuard()
            ->groupBy(fn ($permission): string => Str::before($permission->name, '.'))
            ->map(fn ($permissions): array => $permissions
                ->mapWithKeys(fn ($permission): array => [
                    $permission->name => $labels[$permission->name] ?? $permission->name,
                ])
                ->all())
            ->all();
    }

    private function roleName(mixed $name): string
    {
        $normalized = Str::of((string) $name)
            ->trim()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->toString();

        if ($normalized === '') {
            throw ValidationException::withMessages([
                'name' => ['Role name is required.'],
            ]);
        }

        return $normalized;
    }

    private function ensureRoleIsUnique(string $name, ?int $ignoreId = null): void
    {
        $existing = $this->roles->findByName($name);

        if ($existing !== null && $existing->id !== $ignoreId) {
            throw ValidationException::withMessages([
                'name' => ['A role already exists with this name.'],
            ]);
        }
    }

    private function isProtectedRole(Role $role): bool
    {
        return in_array($role->name, ['super_admin', 'admin', 'manager', 'support', 'customer'], true);
    }

    private function hasProtectedPermissionSet(Role $role): bool
    {
        return in_array($role->name, ['super_admin', 'admin'], true);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function permissionSetChanged(Role $role, array $data): bool
    {
        if (! array_key_exists('permissions', $data)) {
            return false;
        }

        $current = $this->permissionNames($role);
        $incoming = collect((array) $data['permissions'])
            ->filter()
            ->sort()
            ->values()
            ->all();

        return $current !== $incoming;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncPermissions(Role $role, array $data): Role
    {
        if (! array_key_exists('permissions', $data)) {
            return $this->roles->refreshWithPermissions($role);
        }

        $permissions = array_values(array_filter((array) $data['permissions']));

        return $this->roles->syncPermissions($role, $permissions);
    }

    /**
     * @return list<string>
     */
    private function permissionNames(Role $role): array
    {
        return $role->permissions->pluck('name')->sort()->values()->all();
    }
}
