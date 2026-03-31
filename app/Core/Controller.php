<?php

declare(strict_types=1);

// Base controller utilities for validation and JSON responses.

namespace App\Core;

abstract class Controller
{
    protected Request $request;

    public function __construct(?Request $request = null)
    {
        $this->request = $request ?? Request::capture();
    }

    protected function allowMethod(string $expectedMethod): void
    {
        if ($this->request->method() !== strtoupper($expectedMethod)) {
            $this->error(405, 'Method not allowed.', [
                'allowed_method' => strtoupper($expectedMethod),
            ]);
        }
    }

    // Unified payload reader with automatic invalid JSON handling.
    protected function input(): array
    {
        if ($this->request->hasInvalidJson()) {
            $this->error(400, 'Invalid JSON payload.');
        }

        return $this->request->body();
    }

    protected function requireFields(array $data, array $fields): void
    {
        foreach ($fields as $field) {
            if (!isset($data[$field]) || trim((string) $data[$field]) === '') {
                $this->error(422, "Field '{$field}' is required.");
            }
        }
    }

    protected function json(int $statusCode, array $payload): never
    {
        Response::json($statusCode, $payload, $this->request->path());
    }

    protected function error(int $statusCode, string $message, array $extra = []): never
    {
        Response::error($this->request->path(), $statusCode, $message, $extra);
    }
}


