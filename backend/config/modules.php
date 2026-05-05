<?php

use App\Modules\Auth\Providers\AuthServiceProvider;
use App\Modules\Catalog\Providers\CatalogServiceProvider;
use App\Modules\Customer\Providers\CustomerServiceProvider;
use App\Modules\Inventory\Providers\InventoryServiceProvider;
use App\Modules\Order\Providers\OrderServiceProvider;
use App\Modules\Promotion\Providers\PromotionServiceProvider;
use App\Modules\Reporting\Providers\ReportingServiceProvider;
use App\Modules\Setting\Providers\SettingServiceProvider;
use App\Modules\Shipping\Providers\ShippingServiceProvider;
use App\Modules\User\Providers\UserServiceProvider;

return [
    'auth' => [
        'name' => 'Auth',
        'provider' => AuthServiceProvider::class,
        'routes' => [
            'web' => 'Modules/Auth/routes/web.php',
            'api' => 'Modules/Auth/routes/api.php',
        ],
    ],
    'user' => [
        'name' => 'User',
        'provider' => UserServiceProvider::class,
        'routes' => [
            'web' => 'Modules/User/routes/web.php',
            'api' => 'Modules/User/routes/api.php',
        ],
    ],
    'setting' => [
        'name' => 'Setting',
        'provider' => SettingServiceProvider::class,
        'routes' => [
            'web' => 'Modules/Setting/routes/web.php',
            'api' => 'Modules/Setting/routes/api.php',
        ],
    ],
    'catalog' => [
        'name' => 'Catalog',
        'provider' => CatalogServiceProvider::class,
        'routes' => [
            'web' => 'Modules/Catalog/routes/web.php',
            'api' => 'Modules/Catalog/routes/api.php',
        ],
    ],
    'inventory' => [
        'name' => 'Inventory',
        'provider' => InventoryServiceProvider::class,
        'routes' => [
            'web' => 'Modules/Inventory/routes/web.php',
            'api' => 'Modules/Inventory/routes/api.php',
        ],
    ],
    'customer' => [
        'name' => 'Customer',
        'provider' => CustomerServiceProvider::class,
        'routes' => [
            'web' => 'Modules/Customer/routes/web.php',
            'api' => 'Modules/Customer/routes/api.php',
        ],
    ],
    'cart' => [
        'name' => 'Cart',
        'provider' => null,
        'routes' => [
            'web' => 'Modules/Cart/routes/web.php',
            'api' => 'Modules/Cart/routes/api.php',
        ],
    ],
    'checkout' => [
        'name' => 'Checkout',
        'provider' => null,
        'routes' => [
            'web' => 'Modules/Checkout/routes/web.php',
            'api' => 'Modules/Checkout/routes/api.php',
        ],
    ],
    'shipping' => [
        'name' => 'Shipping',
        'provider' => ShippingServiceProvider::class,
        'routes' => [
            'web' => 'Modules/Shipping/routes/web.php',
            'api' => 'Modules/Shipping/routes/api.php',
        ],
    ],
    'promotion' => [
        'name' => 'Promotion',
        'provider' => PromotionServiceProvider::class,
        'routes' => [
            'web' => 'Modules/Promotion/routes/web.php',
            'api' => 'Modules/Promotion/routes/api.php',
        ],
    ],
    'order' => [
        'name' => 'Order',
        'provider' => OrderServiceProvider::class,
        'routes' => [
            'web' => 'Modules/Order/routes/web.php',
            'api' => 'Modules/Order/routes/api.php',
        ],
    ],
    'payment' => [
        'name' => 'Payment',
        'provider' => null,
        'routes' => [
            'web' => 'Modules/Payment/routes/web.php',
            'api' => 'Modules/Payment/routes/api.php',
        ],
    ],
    'reporting' => [
        'name' => 'Reporting',
        'provider' => ReportingServiceProvider::class,
        'routes' => [
            'web' => 'Modules/Reporting/routes/web.php',
            'api' => 'Modules/Reporting/routes/api.php',
        ],
    ],
];
