<?php

namespace App\Modules\Auth\Repositories;

use App\Models\User;

class PasswordResetRepository
{
    public function findActiveUser(string $email, bool $isAdmin): ?User
    {
        return User::query()
            ->where('email', $email)
            ->where('is_admin', $isAdmin)
            ->where('status', 'active')
            ->first();
    }

    public function deleteTokens(User $user): void
    {
        $user->tokens()->delete();
    }
}
