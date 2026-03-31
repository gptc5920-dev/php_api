<?php

declare(strict_types=1);

// Handles authentication and account registration flows.

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use RuntimeException;

final class AuthController extends Controller
{
    private User $users;

    public function __construct(?\App\Core\Request $request = null, ?User $users = null)
    {
        parent::__construct($request);
        $this->users = $users ?? new User();
    }

    // POST /login.php
    public function login(): never //the 'never' return type indicates that a function or method will not return a value to the caller, and it will instead terminate the script execution. 
    {
        // 1) Guard HTTP method first.
        $this->allowMethod('POST');

        // 2) Read and validate required credentials.
        $data = $this->input();
        $this->requireFields($data, ['email', 'password']);

        $email = strtolower(trim((string) $data['email']));
        $password = (string) $data['password'];

        // 3) Load account by email, then verify password hash.
        try {
            $user = $this->users->findByEmail($email);
        } catch (RuntimeException $exception) {
            $this->error(500, 'Database connection failed.', [
                'error' => $exception->getMessage(),
            ]);
        }

        if (!$user || !password_verify($password, (string) $user['password'])) {
            $this->error(401, 'Invalid email or password.');
        }

        // 4) Return authenticated user profile (without password).
        $this->json(200, [
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'id' => (int) $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
            ],
        ]);
    }

    // GET /register.php (metadata) and POST /register.php (create account)
    public function register(): never
    {
        // 1) For quick API docs / form hints, GET returns metadata only.
        if ($this->request->method() === 'GET') {
            $this->registrationMetadata();
        }

        // 2) Registration itself is POST-only.
        $this->allowMethod('POST');

        // 3) Read, normalize, and validate incoming user fields.
        $data = $this->input();
        $this->requireFields($data, ['name', 'email', 'password']);

        $name = trim((string) $data['name']);
        $email = strtolower(trim((string) $data['email']));
        $password = (string) $data['password'];
        $confirmPassword = isset($data['confirm_password']) ? (string) $data['confirm_password'] : '';
        $role = $this->normalizeRole($data['role'] ?? null);

        $this->validateName($name);
        $this->validateEmail($email);
        $this->validatePassword($password);

        if ($confirmPassword !== '' && $password !== $confirmPassword) {
            $this->error(422, 'Password confirmation does not match.');
        }

        // 4) Ensure unique email, then persist new user.
        try {
            if ($this->users->emailExists($email)) {
                $this->error(409, 'Email already exists.');
            }

            $userId = $this->users->create(
                $name,
                $email,
                $role,
                password_hash($password, PASSWORD_DEFAULT)
            );
        } catch (RuntimeException $exception) {
            $this->error(500, 'Database connection failed.', [
                'error' => $exception->getMessage(),
            ]);
        }

        // 5) Return resource location for clients that follow Location headers.
        header('Location: /api/users.php?id=' . $userId);

        $this->json(201, [
            'success' => true,
            'message' => 'User registered successfully.',
            'data' => [
                'id' => $userId,
                'name' => $name,
                'email' => $email,
                'role' => $role,
            ],
        ]);
    }

    private function registrationMetadata(): never
    {
        $this->json(200, [
            'success' => true,
            'message' => 'Registration endpoint is ready.',
            'data' => [
                'method' => 'POST',
                'fields' => [
                    'name',
                    'email',
                    'role',
                    'password',
                    'confirm_password',
                ],
                'example' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                    'role' => 'staff',
                    'password' => 'secret123',
                    'confirm_password' => 'secret123',
                ],
            ],
        ]);
    }

    private function validateName(string $name): void
    {
        $minLength = (int) appConfig()['auth']['min_name_length'];

        if (strlen($name) < $minLength) {
            $this->error(422, "Name must be at least {$minLength} characters.");
        }
    }

    private function validateEmail(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error(422, 'Invalid email address.');
        }
    }

    private function validatePassword(string $password): void
    {
        $minLength = (int) appConfig()['auth']['min_password_length'];

        if (strlen($password) < $minLength) {
            $this->error(422, "Password must be at least {$minLength} characters.");
        }
    }

    private function normalizeRole(?string $role): string
    {
        $defaultRole = (string) appConfig()['auth']['default_role'];
        $allowedRoles = (array) appConfig()['auth']['allowed_roles'];
        $normalizedRole = strtolower(trim((string) $role));

        if ($normalizedRole === '') {
            return $defaultRole;
        }

        if (!in_array($normalizedRole, $allowedRoles, true)) {
            $this->error(422, 'Role must be either admin or staff.');
        }

        return $normalizedRole;
    }
}

