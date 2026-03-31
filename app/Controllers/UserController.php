<?php

declare(strict_types=1);

// Handles users CRUD flow and method-based dispatch.

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use RuntimeException;

final class UserController extends Controller
{
    private User $users;

    public function __construct(?\App\Core\Request $request = null, ?User $users = null)
    {
        parent::__construct($request);
        $this->users = $users ?? new User();
    }

    // Single entrypoint that dispatches CRUD behavior by HTTP method.
    public function handle(): never
    {
        $method = $this->request->method();

        if ($method === 'GET') {
            $id = $this->queryUserId();

            if ($id === null) {
                $this->index();
            }

            $this->show($id);
        }

        if ($method === 'POST') {
            $this->store();
        }

        if ($method === 'PUT' || $method === 'PATCH') {
            $id = $this->queryUserId();

            if ($id === null) {
                $this->error(422, 'Query parameter "id" is required.');
            }

            $this->update($id);
        }

        if ($method === 'DELETE') {
            $id = $this->queryUserId();

            if ($id === null) {
                $this->error(422, 'Query parameter "id" is required.');
            }

            $this->destroy($id);
        }

        $this->error(405, 'Method not allowed.', [
            'allowed_method' => 'GET, POST, PUT, PATCH, DELETE',
        ]);
    }

    private function index(): never
    {
        try {
            $items = $this->users->findAll();
        } catch (RuntimeException $exception) {
            $this->error(500, 'Database connection failed.', [
                'error' => $exception->getMessage(),
            ]);
        }

        $this->json(200, [
            'success' => true,
            'message' => 'Users fetched successfully.',
            'data' => $items,
        ]);
    }

    private function show(int $id): never
    {
        try {
            $user = $this->users->findById($id);
        } catch (RuntimeException $exception) {
            $this->error(500, 'Database connection failed.', [
                'error' => $exception->getMessage(),
            ]);
        }

        if (!$user) {
            $this->error(404, 'User not found.');
        }

        $this->json(200, [
            'success' => true,
            'message' => 'User fetched successfully.',
            'data' => $user,
        ]);
    }

    private function store(): never
    {
        $data = $this->input();
        $this->requireFields($data, ['name', 'email', 'password']);

        $name = trim((string) $data['name']);
        $email = strtolower(trim((string) $data['email']));
        $password = (string) $data['password'];
        $role = $this->normalizeRole($data['role'] ?? null);

        $this->validateName($name);
        $this->validateEmail($email);
        $this->validatePassword($password);

        try {
            if ($this->users->emailExists($email)) {
                $this->error(409, 'Email already exists.');
            }

            $id = $this->users->create($name, $email, $role, password_hash($password, PASSWORD_DEFAULT));
            $user = $this->users->findById($id);
        } catch (RuntimeException $exception) {
            $this->error(500, 'Database connection failed.', [
                'error' => $exception->getMessage(),
            ]);
        }

        header('Location: /api/users.php?id=' . $id);

        $this->json(201, [
            'success' => true,
            'message' => 'User created successfully.',
            'data' => $user,
        ]);
    }

    private function update(int $id): never
    {
        $data = $this->input();

        try {
            $existing = $this->users->findById($id);
        } catch (RuntimeException $exception) {
            $this->error(500, 'Database connection failed.', [
                'error' => $exception->getMessage(),
            ]);
        }

        if (!$existing) {
            $this->error(404, 'User not found.');
        }

        $fields = [];

        if (array_key_exists('name', $data)) {
            $name = trim((string) $data['name']);
            $this->validateName($name);
            $fields['name'] = $name;
        }

        if (array_key_exists('email', $data)) {
            $email = strtolower(trim((string) $data['email']));
            $this->validateEmail($email);

            try {
                if ($this->users->emailExistsExcept($email, $id)) {
                    $this->error(409, 'Email already exists.');
                }
            } catch (RuntimeException $exception) {
                $this->error(500, 'Database connection failed.', [
                    'error' => $exception->getMessage(),
                ]);
            }

            $fields['email'] = $email;
        }

        if (array_key_exists('role', $data)) {
            $fields['role'] = $this->normalizeRole((string) $data['role']);
        }

        if (array_key_exists('password', $data)) {
            $password = (string) $data['password'];
            $this->validatePassword($password);
            $fields['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        if ($fields === []) {
            $this->error(422, 'At least one field is required: name, email, role, or password.');
        }

        try {
            $this->users->updateById($id, $fields);
            $updated = $this->users->findById($id);
        } catch (RuntimeException $exception) {
            $this->error(500, 'Database connection failed.', [
                'error' => $exception->getMessage(),
            ]);
        }

        $this->json(200, [
            'success' => true,
            'message' => 'User updated successfully.',
            'data' => $updated,
        ]);
    }

    private function destroy(int $id): never
    {
        try {
            $existing = $this->users->findById($id);
        } catch (RuntimeException $exception) {
            $this->error(500, 'Database connection failed.', [
                'error' => $exception->getMessage(),
            ]);
        }

        if (!$existing) {
            $this->error(404, 'User not found.');
        }

        try {
            $this->users->deleteById($id);
        } catch (RuntimeException $exception) {
            $this->error(500, 'Database connection failed.', [
                'error' => $exception->getMessage(),
            ]);
        }

        $this->json(200, [
            'success' => true,
            'message' => 'User deleted successfully.',
        ]);
    }

    // Reads user id from query string (?id=123) for show/update/delete calls.
    private function queryUserId(): ?int
    {
        $raw = $_GET['id'] ?? null;

        if ($raw === null || $raw === '') {
            return null;
        }

        if (!is_numeric($raw) || (int) $raw <= 0) {
            $this->error(422, 'Query parameter "id" must be a positive integer.');
        }

        return (int) $raw;
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


