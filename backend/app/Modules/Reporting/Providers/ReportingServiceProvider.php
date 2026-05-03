<?php

namespace App\Modules\Reporting\Providers;

use Illuminate\Support\ServiceProvider;

class ReportingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(app_path('Modules/Reporting/views'), 'reporting');
    }
}
