<?php

declare(strict_types=1);

// Standard JSON response formatter for success and error payloads.

namespace App\Core;

final class Response
{
    public static function json(int $statusCode, array $payload, string $path): never
    {
        $meta = self::resolveMeta($path);

        http_response_code($statusCode);

        echo json_encode([
            'status' => $statusCode,
            'path' => $path,
            'module' => $payload['module'] ?? $meta['module'],
            'action' => $payload['action'] ?? $meta['action'],
        ] + $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        exit;
    }

    public static function error(string $path, int $statusCode, string $message, array $extra = []): never
    {
        self::json($statusCode, array_merge([
            'success' => false,
            'message' => $message,
        ], $extra), $path);
    }

    private static function resolveMeta(string $path): array
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $cleanPath = '/' . trim($path, '/');
        $resource = strtolower((string) pathinfo($path, PATHINFO_BASENAME));
        $hasId = isset($_GET['id']) && (string) $_GET['id'] !== '';

        if ($cleanPath === '/' || $cleanPath === '/api' || $resource === '' || $resource === 'index.php') {
            return [
                'module' => 'api',
                'action' => 'index',
            ];
        }

        if ($resource === 'login.php') {
            return [
                'module' => 'auth',
                'action' => 'login',
            ];
        }

        if ($resource === 'register.php') {
            return [
                'module' => 'auth',
                'action' => $method === 'GET' ? 'register_metadata' : 'register',
            ];
        }

        if ($resource === 'users.php') {
            $action = 'list';

            if ($method === 'POST') {
                $action = 'create';
            } elseif ($method === 'PUT' || $method === 'PATCH') {
                $action = 'update';
            } elseif ($method === 'DELETE') {
                $action = 'delete';
            } elseif ($method === 'GET' && $hasId) {
                $action = 'show';
            }

            return [
                'module' => 'users',
                'action' => $action,
            ];
        }

        return [
            'module' => 'general',
            'action' => strtolower($method),
        ];
    }
}
