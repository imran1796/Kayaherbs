<?php

use App\Modules\User\Providers\UserServiceProvider;

return [
    'user' => [
        'name' => 'User',
        'provider' => UserServiceProvider::class,
        'routes' => [
            'web' => 'Modules/User/routes/web.php',
            'api' => 'Modules/User/routes/api.php',
        ],
    ],
];
