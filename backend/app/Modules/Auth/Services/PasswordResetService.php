<?php

namespace App\Modules\Auth\Services;

use App\Core\Services\AuditLogger;
use App\Models\User;
use App\Modules\Auth\Repositories\PasswordResetRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordResetService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly PasswordResetRepository $passwordResets
    ) {}

    public function sendCustomerResetLink(string $email): string
    {
        return $this->sendResetLink($email, false);
    }

    public function sendAdminResetLink(string $email): string
    {
        return $this->sendResetLink($email, true);
    }

    /**
     * @param  array<string, string>  $data
     */
    public function resetCustomerPassword(array $data): string
    {
        return $this->resetPassword($data, false);
    }

    /**
     * @param  array<string, string>  $data
     */
    public function resetAdminPassword(array $data): string
    {
        return $this->resetPassword($data, true);
    }

    private function sendResetLink(string $email, bool $isAdmin): string
    {
        $target = $this->findResettableUser($email, $isAdmin);
        $status = Password::sendResetLink($this->brokerCredentials($email, $isAdmin));
        $domain = $isAdmin ? 'admin' : 'customer';

        if ($status === Password::RESET_THROTTLED) {
            $this->auditLogger->record(
                "{$domain}.password_reset.requested",
                auditable: $target,
                metadata: ['email' => $email],
                outcome: 'throttled',
                guard: $isAdmin ? 'web' : 'sanctum'
            );

            throw ValidationException::withMessages([
                'email' => __($status),
            ]);
        }

        if ($status !== Password::RESET_LINK_SENT && $status !== Password::INVALID_USER) {
            $this->auditLogger->record(
                "{$domain}.password_reset.requested",
                auditable: $target,
                metadata: ['email' => $email, 'status' => $status],
                outcome: 'failure',
                guard: $isAdmin ? 'web' : 'sanctum'
            );

            throw ValidationException::withMessages([
                'email' => __($status),
            ]);
        }

        $this->auditLogger->record(
            "{$domain}.password_reset.requested",
            auditable: $target,
            metadata: ['email' => $email],
            outcome: $status === Password::RESET_LINK_SENT ? 'sent' : 'accepted',
            guard: $isAdmin ? 'web' : 'sanctum'
        );

        return Password::RESET_LINK_SENT;
    }

    /**
     * @param  array<string, string>  $data
     */
    private function resetPassword(array $data, bool $isAdmin): string
    {
        $domain = $isAdmin ? 'admin' : 'customer';

        $status = Password::reset(
            [
                ...$this->brokerCredentials($data['email'], $isAdmin),
                'token' => $data['token'],
                'password' => $data['password'],
                'password_confirmation' => $data['password_confirmation'] ?? '',
            ],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                $this->passwordResets->deleteTokens($user);

                $this->auditLogger->record(
                    ($user->is_admin ? 'admin' : 'customer').'.password_reset.completed',
                    actor: $user,
                    auditable: $user,
                    metadata: ['email' => $user->email],
                    guard: $user->is_admin ? 'web' : 'sanctum'
                );
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            $this->auditLogger->record(
                "{$domain}.password_reset.failed",
                auditable: $this->findResettableUser($data['email'], $isAdmin),
                metadata: ['email' => $data['email'], 'status' => $status],
                outcome: 'failure',
                guard: $isAdmin ? 'web' : 'sanctum'
            );

            throw ValidationException::withMessages([
                'email' => __($status),
            ]);
        }

        return $status;
    }

    /**
     * @return array<string, mixed>
     */
    private function brokerCredentials(string $email, bool $isAdmin): array
    {
        return [
            'email' => $email,
            'is_admin' => $isAdmin,
            'status' => 'active',
        ];
    }

    private function findResettableUser(string $email, bool $isAdmin): ?User
    {
        return $this->passwordResets->findActiveUser($email, $isAdmin);
    }
}
