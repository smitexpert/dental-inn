<?php

declare(strict_types=1);

use App\Services\Notifications\HttpDriver;
use App\Services\Notifications\LogDriver;
use App\Services\Notifications\MailDriver;

return [
    /*
    |--------------------------------------------------------------------------
    | Default Driver
    |--------------------------------------------------------------------------
    |
    | The default driver used by NotificationService when no override is
    | provided. In production set this to `http` (or `mail` for
    | email-only deployments) via NOTIFICATIONS_DRIVER in .env.
    |
    */

    'default_driver' => env('NOTIFICATIONS_DRIVER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Supported Channels
    |--------------------------------------------------------------------------
    */

    'channels' => ['sms', 'whatsapp', 'email'],

    /*
    |--------------------------------------------------------------------------
    | Driver Registry
    |--------------------------------------------------------------------------
    |
    | Each driver entry includes the class to instantiate and any
    | driver-specific config. The driver classes MUST implement
    | App\Contracts\NotificationDriver.
    |
    */

    'drivers' => [
        'log' => [
            'class' => LogDriver::class,
            'config' => [],
        ],
        'http' => [
            'class' => HttpDriver::class,
            'config' => [
                'url' => env('NOTIFICATIONS_HTTP_URL'),
                'timeout' => (int) env('NOTIFICATIONS_HTTP_TIMEOUT', 5),
            ],
        ],
        'mail' => [
            'class' => MailDriver::class,
            'config' => [],
        ],
    ],
];
