<?php

namespace App\Modules\Auth\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadViewsFrom(app_path('Modules/Auth/views'), 'auth');

        ResetPassword::createUrlUsing(function (mixed $notifiable, string $token): string {
            $email = $notifiable->getEmailForPasswordReset();

            if ($notifiable instanceof User && $notifiable->is_admin) {
                return route('admin.password.reset', [
                    'token' => $token,
                    'email' => $email,
                ]);
            }

            return url('/reset-password?'.http_build_query([
                'token' => $token,
                'email' => $email,
            ]));
        });
    }
}
