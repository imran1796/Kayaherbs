<?php

namespace App\Modules\Shipping\Providers;

use Illuminate\Support\ServiceProvider;

class ShippingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(app_path('Modules/Shipping/views'), 'shipping');
    }
}
