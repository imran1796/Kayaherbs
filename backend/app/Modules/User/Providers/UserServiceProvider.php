<?php

namespace App\Modules\User\Providers;

use App\Modules\User\Repositories\Contracts\UserRepositoryInterface;
use App\Modules\User\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

class UserServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(app_path('Modules/User/views'), 'user');
    }
}
