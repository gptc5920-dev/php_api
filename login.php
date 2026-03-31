<?php

declare(strict_types=1);

// Login endpoint entrypoint.

require_once __DIR__ . '/bootstrap.php';

use App\Controllers\AuthController;

(new AuthController())->login();

