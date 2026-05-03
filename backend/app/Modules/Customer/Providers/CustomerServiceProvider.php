<?php

namespace App\Modules\Customer\Providers;

use Illuminate\Support\ServiceProvider;

class CustomerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(app_path('Modules/Customer/views'), 'customer');
    }
}
