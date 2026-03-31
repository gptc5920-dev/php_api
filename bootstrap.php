<?php

declare(strict_types=1);

// App bootstrap: loads config, autoloader, and common HTTP headers.

require_once __DIR__ . '/config.php';

// Minimal PSR-4 style autoloader for classes under the App\ namespace.
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';

    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = __DIR__ . '/app/' . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});

$cors = appConfig()['cors'];

// Apply common API headers for all endpoints.
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . $cors['allow_origin']);
header('Access-Control-Allow-Methods: ' . $cors['allow_methods']);
header('Access-Control-Allow-Headers: ' . $cors['allow_headers']);

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    // End CORS preflight requests early.
    http_response_code(200);
    exit;
}

