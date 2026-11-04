<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $host = Env::get('DB_HOST');
        $name = Env::get('DB_NAME');
        $user = Env::get('DB_USER');
        $password = Env::get('DB_PASSWORD');

        if (!$host || !$name || !$user || $password === null || $password === '') {
            throw new RuntimeException('Database configuration is incomplete.');
        }

        $port = Env::get('DB_PORT', '3306');
        $charset = Env::get('DB_CHARSET', 'utf8mb4');
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $host, $port, $name, $charset);

        try {
            self::$connection = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
            ]);
        } catch (PDOException $exception) {
            error_log('Database connection failed: ' . $exception->getMessage());
            throw new RuntimeException('Database connection is currently unavailable.');
        }

        return self::$connection;
    }

    /**\n     * Build a safely quoted table identifier using the mandatory DB_PREFIX.\n     */\n    public static function table(string $name): string\n    {\n        $prefix = Env::get('DB_PREFIX');\n        if ($prefix === null || !preg_match('/^[a-zA-Z][a-zA-Z0-9_]{0,30}_$/', $prefix)) {\n            throw new RuntimeException('Database table prefix is missing or invalid.');\n        }\n        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]{0,40}$/', $name)) {\n            throw new RuntimeException('Invalid database table name.');\n        }\n\n        return '`' . $prefix . $name . '`';\n    }\n\n    private function __construct()
    {
    }
}
