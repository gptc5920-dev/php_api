<?php

declare(strict_types=1);

// Exposes API metadata and available endpoint list.

namespace App\Controllers;

use App\Core\Controller;

final class ApiController extends Controller
{
    public function index(): never
    {
        $this->allowMethod('GET');

        $this->json(200, [
            'success' => true,
            'message' => 'Clean REST API is running.',
            'data' => [
                'name' => 'Clean API',
                'version' => 'v1',
                'endpoints' => [
                    [
                        'method' => 'POST',
                        'uri' => '/api/register.php',
                        'description' => 'Register a new user.',
                    ],
                    [
                        'method' => 'POST',
                        'uri' => '/api/login.php',
                        'description' => 'Authenticate a user.',
                    ],
                    [
                        'method' => 'GET',
                        'uri' => '/api/users.php',
                        'description' => 'List users or fetch one user by id query.',
                    ],
                    [
                        'method' => 'POST',
                        'uri' => '/api/users.php',
                        'description' => 'Create a new user.',
                    ],
                    [
                        'method' => 'PUT/PATCH',
                        'uri' => '/api/users.php?id={id}',
                        'description' => 'Update an existing user.',
                    ],
                    [
                        'method' => 'DELETE',
                        'uri' => '/api/users.php?id={id}',
                        'description' => 'Delete a user.',
                    ],
                ],
            ],
        ]);
    }
}

