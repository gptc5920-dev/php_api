<?php

declare(strict_types=1);

// User data access layer for authentication and CRUD queries.

namespace App\Models;

use App\Core\Database;
use PDO;

final class User
{
    private ?PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo;
    }

    public function findAll(): array
    {
        $query = $this->connection()->query(
            'SELECT id, name, email, role, created_at FROM users ORDER BY id DESC'
        );

        return $query->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $query = $this->connection()->prepare(
            'SELECT id, name, email, role, created_at FROM users WHERE id = :id LIMIT 1'
        );
        $query->execute(['id' => $id]);

        $user = $query->fetch();

        return is_array($user) ? $user : null;
    }

    public function findByEmail(string $email): ?array
    {
        $query = $this->connection()->prepare(
            'SELECT id, name, email, role, password FROM users WHERE email = :email LIMIT 1'
        );
        $query->execute(['email' => $email]);

        $user = $query->fetch();

        return is_array($user) ? $user : null;
    }

    public function emailExists(string $email): bool
    {
        $query = $this->connection()->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $query->execute(['email' => $email]);

        return (bool) $query->fetch();
    }

    public function emailExistsExcept(string $email, int $excludedId): bool
    {
        $query = $this->connection()->prepare(
            'SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1'
        );
        $query->execute([
            'email' => $email,
            'id' => $excludedId,
        ]);

        return (bool) $query->fetch();
    }

    public function create(string $name, string $email, string $role, string $passwordHash): int
    {
        $query = $this->connection()->prepare(
            'INSERT INTO users (name, email, role, password, created_at) VALUES (:name, :email, :role, :password, NOW())'
        );

        $query->execute([
            'name' => $name,
            'email' => $email,
            'role' => $role,
            'password' => $passwordHash,
            
        ]);

        return (int) $this->connection()->lastInsertId();
    }

    public function updateById(int $id, array $fields): bool
    {
        if ($fields === []) {
            return false;
        }

        $allowed = ['name', 'email', 'role', 'password'];
        $setParts = [];
        $params = ['id' => $id];

        foreach ($fields as $column => $value) {
            if (!in_array($column, $allowed, true)) {
                continue;
            }

            $setParts[] = "{$column} = :{$column}";
            $params[$column] = $value;
        }

        if ($setParts === []) {
            return false;
        }

        $sql = 'UPDATE users SET ' . implode(', ', $setParts) . ' WHERE id = :id';
        $query = $this->connection()->prepare($sql);
        $query->execute($params);

        return $query->rowCount() > 0;
    }

    // public function deleteById(int $id): bool
    // {
    //     $query = $this->connection()->prepare('DELETE FROM users WHERE id = :id');
    //     $query->execute(['id' => $id]);

    //     return $query->rowCount() > 0;
    // }

    private function connection(): PDO
    {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }

        $this->pdo = Database::connection();

        return $this->pdo;
    }
}


