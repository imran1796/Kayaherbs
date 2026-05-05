<?php

namespace App\Modules\Promotion\Providers;

use Illuminate\Support\ServiceProvider;

class PromotionServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(app_path('Modules/Promotion/views'), 'promotion');
    }
}
