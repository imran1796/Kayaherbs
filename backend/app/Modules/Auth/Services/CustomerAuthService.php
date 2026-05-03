<?php

namespace App\Modules\Auth\Services;

use App\Core\Services\AuditLogger;
use App\Models\User;
use App\Modules\Auth\Repositories\CustomerAuthRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\NewAccessToken;

class CustomerAuthService
{
    public function __construct(
        private readonly CustomerAuthRepository $customerAuthRepository,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function register(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $user = $this->customerAuthRepository->createCustomer($data);
            $this->auditLogger->record(
                'customer.registered',
                actor: $user,
                auditable: $user,
                metadata: ['email' => $user->email],
                guard: 'sanctum'
            );

            return $this->tokenResponse($user);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function login(string $email, string $password): array
    {
        $user = $this->customerAuthRepository->findCustomerByEmail($email);

        if (! $this->canAuthenticateCustomer($user, $password)) {
            $this->auditLogger->record(
                'customer.login.failed',
                auditable: $user,
                metadata: ['email' => $email],
                outcome: 'failure',
                guard: 'sanctum'
            );

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $this->customerAuthRepository->recordSuccessfulLogin($user);
        $this->auditLogger->record(
            'customer.login.succeeded',
            actor: $user,
            auditable: $user,
            guard: 'sanctum'
        );

        return $this->tokenResponse($user);
    }

    /**
     * @return array<string, mixed>
     */
    public function profile(User $user): array
    {
        return $this->userPayload($user);
    }

    public function logout(User $user): void
    {
        $this->customerAuthRepository->deleteCurrentToken($user);
        $this->auditLogger->record(
            'customer.logout',
            actor: $user,
            auditable: $user,
            metadata: ['scope' => 'current_token'],
            guard: 'sanctum'
        );
    }

    public function logoutAll(User $user): void
    {
        $this->customerAuthRepository->deleteAllTokens($user);
        $this->auditLogger->record(
            'customer.logout_all',
            actor: $user,
            auditable: $user,
            metadata: ['scope' => 'all_tokens'],
            guard: 'sanctum'
        );
    }

    private function canAuthenticateCustomer(?User $user, string $password): bool
    {
        return $user !== null
            && Hash::check($password, $user->password)
            && ! $user->is_admin
            && $user->status === 'active';
    }

    /**
     * @return array<string, mixed>
     */
    private function tokenResponse(User $user): array
    {
        $this->revokeExpiredTokens($user);

        $token = $this->issueCustomerToken($user);

        $this->enforceActiveTokenLimit($user);

        return [
            'token_type' => 'Bearer',
            'access_token' => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at?->toJSON(),
            'user' => $this->userPayload($user),
        ];
    }

    private function issueCustomerToken(User $user): NewAccessToken
    {
        return $user->createToken(
            (string) config('auth_lifecycle.customer_tokens.name', 'storefront'),
            config('auth_lifecycle.customer_tokens.abilities', ['customer']),
            $this->customerTokenExpiresAt()
        );
    }

    private function customerTokenExpiresAt(): Carbon
    {
        return now()->addMinutes((int) config('auth_lifecycle.customer_tokens.expire_minutes', 43200));
    }

    private function revokeExpiredTokens(User $user): void
    {
        $this->customerAuthRepository->deleteExpiredTokens($user);
    }

    private function enforceActiveTokenLimit(User $user): void
    {
        $maxActiveTokens = (int) config('auth_lifecycle.customer_tokens.max_active_tokens', 5);

        if ($maxActiveTokens < 1) {
            return;
        }

        $oldTokenIds = $this->customerAuthRepository
            ->activeTokenIds($user)
            ->slice($maxActiveTokens);

        $this->customerAuthRepository->deleteTokensByIds($user, $oldTokenIds);
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'status' => $user->status,
        ];
    }
}
