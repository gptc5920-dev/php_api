<?php

declare(strict_types=1);

// Normalizes request method/path and parses payload input.

namespace App\Core;

final class Request
{
    private string $method;
    private string $path;
    private array $body;
    private bool $invalidJson;

    private function __construct(string $method, string $path, array $body, bool $invalidJson)
    {
        $this->method = strtoupper($method);
        $this->path = self::normalizePath($path);
        $this->body = $body;
        $this->invalidJson = $invalidJson;
    }

    public static function capture(?string $forcedPath = null): self
    {
        $method = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path = $forcedPath ?? (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
        $parsedBody = self::parseBody();

        return new self($method, $path, $parsedBody['body'], $parsedBody['invalid_json']);
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function body(): array
    {
        return $this->body;
    }

    public function hasInvalidJson(): bool
    {
        return $this->invalidJson;
    }

    private static function parseBody(): array
    {
        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
        $rawBody = file_get_contents('php://input') ?: '';

        if (str_contains($contentType, 'application/json')) {
            if ($rawBody === '') {
                return [
                    'body' => [],
                    'invalid_json' => false,
                ];
            }

            $decoded = json_decode($rawBody, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'body' => [],
                    'invalid_json' => true,
                ];
            }

            return [
                'body' => is_array($decoded) ? $decoded : [],
                'invalid_json' => false,
            ];
        }

        return [
            'body' => $_POST,
            'invalid_json' => false,
        ];
    }

    private static function normalizePath(string $path): string
    {
        $normalized = '/' . ltrim($path, '/');

        if ($normalized !== '/') {
            $normalized = rtrim($normalized, '/');
        }

        return $normalized === '' ? '/' : $normalized;
    }
}


