<?php

namespace App\Modules\Auth\Repositories;

use App\Core\Services\AuditLogger;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;

class CustomerAuthRepository
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function createCustomer(array $data): User
    {
        $customer = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'status' => 'active',
            'is_admin' => false,
        ]);

        $customerRole = Role::findOrCreate('customer', (string) config('rbac.guard', 'web'));
        $customer->assignRole($customerRole);
        app(AuditLogger::class)->record(
            'rbac.role.assigned',
            actor: $customer,
            auditable: $customer,
            metadata: ['role' => 'customer', 'source' => 'customer_registration'],
            guard: 'web'
        );

        return $customer;
    }

    public function findCustomerByEmail(string $email): ?User
    {
        return User::query()
            ->where('email', $email)
            ->where('is_admin', false)
            ->first();
    }

    public function recordSuccessfulLogin(User $user): void
    {
        $user->forceFill([
            'last_login_at' => now(),
        ])->save();
    }

    public function deleteCurrentToken(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }

    public function deleteAllTokens(User $user): void
    {
        $user->tokens()->delete();
    }

    public function deleteExpiredTokens(User $user): void
    {
        $user->tokens()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->delete();
    }

    public function activeTokenIds(User $user): Collection
    {
        return $user->tokens()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->pluck('id');
    }

    public function deleteTokensByIds(User $user, Collection $tokenIds): void
    {
        if ($tokenIds->isEmpty()) {
            return;
        }

        $user->tokens()
            ->whereIn('id', $tokenIds)
            ->delete();
    }
}
