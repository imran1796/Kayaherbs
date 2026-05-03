<?php

namespace App\Modules\Auth\Services;

use App\Core\Services\AuditLogger;
use App\Models\User;
use App\Modules\Auth\Repositories\AdminAuthRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminAuthService
{
    public function __construct(
        private readonly AdminAuthRepository $adminAuthRepository,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function login(Request $request, string $email, string $password, bool $remember = false): User
    {
        $user = $this->adminAuthRepository->findUserByEmail($email);

        if (! $this->canAuthenticateAdmin($user, $password)) {
            $this->auditLogger->record(
                'admin.login.failed',
                auditable: $user,
                metadata: ['email' => $email],
                outcome: 'failure',
                request: $request,
                guard: 'web'
            );

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        Auth::login($user, $remember);

        $request->session()->regenerate();
        $this->adminAuthRepository->recordSuccessfulLogin($user);
        $this->auditLogger->record(
            'admin.login.succeeded',
            actor: $user,
            auditable: $user,
            metadata: ['remember' => $remember],
            request: $request,
            guard: 'web'
        );

        return $user;
    }

    public function logout(Request $request): void
    {
        $user = $request->user();

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $this->auditLogger->record(
            'admin.logout',
            actor: $user,
            auditable: $user,
            request: $request,
            guard: 'web'
        );
    }

    private function canAuthenticateAdmin(?User $user, string $password): bool
    {
        return $user !== null
            && Hash::check($password, $user->password)
            && $user->is_admin
            && $user->status === 'active';
    }
}
