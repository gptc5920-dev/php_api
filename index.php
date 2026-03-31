<?php

declare(strict_types=1);

// API home endpoint entrypoint.

require_once __DIR__ . '/bootstrap.php';

use App\Controllers\ApiController;

(new ApiController())->index();

