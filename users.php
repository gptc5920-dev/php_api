<?php

declare(strict_types=1);

// Users CRUD endpoint entrypoint.

require_once __DIR__ . '/bootstrap.php';

use App\Controllers\UserController;

(new UserController())->handle();

