<?php

declare(strict_types=1);

/**
 * Konfiguracja VPS.
 *
 * Credentials są wczytywane z .admin (przez AppServiceProvider)
 * lub z .env jako fallback.
 */
return [
    /*
    |--------------------------------------------------------------------------
    | VPS SSH Configuration
    |--------------------------------------------------------------------------
    |
    | Konfiguracja połączenia SSH z VPS.
    |
    */

    'ssh_host' => env('SSH_VPS', 'debian@51.83.253.165'),
    'ip' => env('VPS_IP', '51.83.253.165'),
    'www_root' => env('VPS_WWW', '/var/www'),

    /*
    |--------------------------------------------------------------------------
    | SSH Options
    |--------------------------------------------------------------------------
    |
    | Opcje połączenia SSH.
    |
    */

    'ssh_options' => [
        'StrictHostKeyChecking' => 'no',
        'ConnectTimeout' => 10,
        'UserKnownHostsFile' => '/dev/null',
    ],
];
