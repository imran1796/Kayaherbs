<?php

namespace App\Modules\Auth\Repositories;

use App\Models\User;

class AdminAuthRepository
{
    public function findUserByEmail(string $email): ?User
    {
        return User::query()
            ->where('email', $email)
            ->first();
    }

    public function recordSuccessfulLogin(User $user): void
    {
        $user->forceFill([
            'last_login_at' => now(),
        ])->save();
    }
}
