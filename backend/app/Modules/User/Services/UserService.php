<?php

namespace App\Modules\User\Services;

use App\Models\User;
use App\Modules\User\Repositories\UserRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function __construct(
        protected UserRepository $users
    ) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->users->paginate($perPage);
    }

    public function findOrFail(int $id): User
    {
        return $this->users->findOrFail($id);
    }

    public function create(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $existingUser = $this->users->findByEmail($data['email']);

            if ($existingUser !== null) {
                throw ValidationException::withMessages([
                    'email' => ['A user already exists with this email address.'],
                ]);
            }

            /** @var User $user */
            $user = $this->users->create($this->sanitizePayload($data));

            return $this->syncRoles($user, $data);
        });
    }

    public function update(int $id, array $data): User
    {
        return DB::transaction(function () use ($id, $data): User {
            $user = $this->findOrFail($id);
            $existingUser = $this->users->findByEmail($data['email']);

            if ($existingUser !== null && $existingUser->id !== $user->id) {
                throw ValidationException::withMessages([
                    'email' => ['A user already exists with this email address.'],
                ]);
            }

            $this->ensureSuperAdminRemainsReachable($user, $data);

            /** @var User $updatedUser */
            $updatedUser = $this->users->update($user, $this->sanitizePayload($data, false));

            return $this->syncRoles($updatedUser, $data);
        });
    }

    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            $user = $this->findOrFail($id);

            return $this->users->delete($user);
        });
    }

    protected function sanitizePayload(array $data, bool $creating = true): array
    {
        $payload = Arr::only($data, [
            'name',
            'email',
            'phone',
            'password',
            'status',
            'created_by',
            'updated_by',
        ]);

        if (! $creating && empty($payload['password'])) {
            unset($payload['password']);
        }

        return $payload;
    }

    protected function syncRoles(User $user, array $data): User
    {
        if (! array_key_exists('roles', $data)) {
            return $user->refresh();
        }

        $roles = array_values(array_filter((array) $data['roles']));

        $user->syncRoles($roles);

        $user->forceFill([
            'is_admin' => $this->rolesMakeAdmin($roles),
        ])->save();

        return $user->refresh();
    }

    /**
     * @param  list<string>  $roles
     */
    private function rolesMakeAdmin(array $roles): bool
    {
        return count(array_diff($roles, ['customer'])) > 0;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function ensureSuperAdminRemainsReachable(User $user, array $data): void
    {
        if (! $user->hasRole('super_admin')) {
            return;
        }

        $roles = array_key_exists('roles', $data)
            ? array_values(array_filter((array) $data['roles']))
            : $user->getRoleNames()->all();

        $willLoseSuperAdmin = ! in_array('super_admin', $roles, true);
        $willBeInactive = ($data['status'] ?? $user->status) !== 'active';

        if (! $willLoseSuperAdmin && ! $willBeInactive) {
            return;
        }

        if (! $this->users->otherActiveSuperAdminExists((int) $user->id)) {
            throw ValidationException::withMessages([
                'roles' => ['At least one active super admin must remain.'],
            ]);
        }
    }
}
