<?php

namespace App\Modules\User\Services;

use App\Core\Services\BaseService;
use App\Models\User;
use App\Modules\User\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class UserService extends BaseService
{
    public function __construct(
        protected UserRepositoryInterface $users
    ) {
    }

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
        return $this->transaction('users.create', function () use ($data): User {
            $existingUser = $this->users->findByEmail($data['email']);

            if ($existingUser !== null) {
                throw ValidationException::withMessages([
                    'email' => ['A user already exists with this email address.'],
                ]);
            }

            /** @var User $user */
            $user = $this->users->create($this->sanitizePayload($data));

            return $user;
        });
    }

    public function update(int $id, array $data): User
    {
        return $this->transaction('users.update', function () use ($id, $data): User {
            $user = $this->findOrFail($id);
            $existingUser = $this->users->findByEmail($data['email']);

            if ($existingUser !== null && $existingUser->id !== $user->id) {
                throw ValidationException::withMessages([
                    'email' => ['A user already exists with this email address.'],
                ]);
            }

            /** @var User $updatedUser */
            $updatedUser = $this->users->update($user, $this->sanitizePayload($data, false));

            return $updatedUser;
        });
    }

    public function delete(int $id): bool
    {
        return $this->transaction('users.delete', function () use ($id): bool {
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
}
