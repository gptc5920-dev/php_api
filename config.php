<?php

declare(strict_types=1);

// Central configuration shared by controllers, models, and core services.

if (!function_exists('appConfig')) {
    // Central app settings used by core services and controllers.
    function appConfig(): array
    {
        static $config = [
            'db' => [
                'host' => '127.0.0.1',
                'name' => 'clean_api',
                'user' => 'root',
                'pass' => '',
            ],
            'cors' => [
                'allow_origin' => '*',
                'allow_methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
                'allow_headers' => 'Content-Type, Authorization',
            ],
            'auth' => [
                'allowed_roles' => ['admin', 'staff'],
                'default_role' => 'staff',
                'min_name_length' => 2,
                'min_password_length' => 6,
            ],
        ];

        return $config;
    }
}

