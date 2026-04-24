<?php

use App\Providers\AppServiceProvider;

$modules = require __DIR__.'/../config/modules.php';

return [
    AppServiceProvider::class,
    ...array_values(array_filter(array_column($modules, 'provider'))),
];
