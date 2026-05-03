<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        Gate::policy(User::class, UserPolicy::class);

        Gate::before(function (User $user, string $ability): ?bool {
            return $user->hasRole('super_admin') ? true : null;
        });

        RateLimiter::for('auth.login', function (Request $request): Limit {
            return Limit::perMinutes(
                (int) config('auth_rate_limits.login.decay_minutes', 1),
                (int) config('auth_rate_limits.login.max_attempts', 5)
            )->by($this->rateLimitKey($request, 'login'));
        });

        RateLimiter::for('auth.register', function (Request $request): Limit {
            return Limit::perMinutes(
                (int) config('auth_rate_limits.register.decay_minutes', 1),
                (int) config('auth_rate_limits.register.max_attempts', 3)
            )->by($this->rateLimitKey($request, 'register', includeEmail: false));
        });

        RateLimiter::for('auth.password-reset', function (Request $request): Limit {
            return Limit::perMinutes(
                (int) config('auth_rate_limits.password_reset.decay_minutes', 1),
                (int) config('auth_rate_limits.password_reset.max_attempts', 3)
            )->by($this->rateLimitKey($request, 'password-reset'));
        });

        RateLimiter::for('auth.session', function (Request $request): Limit {
            return Limit::perMinutes(
                (int) config('auth_rate_limits.session.decay_minutes', 1),
                (int) config('auth_rate_limits.session.max_attempts', 30)
            )->by($this->rateLimitKey($request, 'session', includeEmail: false));
        });
    }

    private function rateLimitKey(Request $request, string $bucket, bool $includeEmail = true): string
    {
        $identity = $request->user()?->getAuthIdentifier()
            ?: ($includeEmail ? Str::lower((string) $request->input('email')) : null)
            ?: 'guest';

        return implode('|', [
            $bucket,
            $identity,
            $request->ip(),
        ]);
    }
}
