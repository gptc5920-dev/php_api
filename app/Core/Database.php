<?php

declare(strict_types=1);

// PDO connection manager and schema bootstrapper.

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?PDO $connection = null;

    // Reuse a single PDO instance for the lifecycle of the PHP request.
    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $config = \appConfig()['db'];
        $dsn = 'mysql:host=' . $config['host'] . ';charset=utf8mb4';

        try {
            $pdo = new PDO($dsn, $config['user'], $config['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            self::initializeDatabase($pdo, (string) $config['name']);
            self::$connection = $pdo;

            return self::$connection;
        } catch (PDOException $exception) {
            throw new RuntimeException('Database connection failed.', 0, $exception);
        }
    }

    private static function initializeDatabase(PDO $pdo, string $databaseName): void
    {
        $sanitizedName = preg_replace('/[^a-zA-Z0-9_]/', '', $databaseName);

        if ($sanitizedName === null || $sanitizedName === '') {
            throw new RuntimeException('Invalid database configuration.');
        }

        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$sanitizedName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$sanitizedName}`");
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS users (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(150) NOT NULL UNIQUE,
                role ENUM("admin", "staff") NOT NULL DEFAULT "staff",
                password VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        $pdo->exec(
            'ALTER TABLE users
                ADD COLUMN IF NOT EXISTS role ENUM("admin", "staff") NOT NULL DEFAULT "staff" AFTER email'
        );
    }
}


