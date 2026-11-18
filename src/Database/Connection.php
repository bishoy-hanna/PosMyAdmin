<?php

declare(strict_types=1);

namespace PosMyAdmin\Database;

use PDO;
use PDOException;
use RuntimeException;

class Connection
{
    private static ?PDO $instance = null;
    private static ?array $currentCredentials = null;

    /**
     * Get or create active PDO instance
     */
    public static function get(array $credentials): PDO
    {
        if (self::$instance !== null && self::$currentCredentials === $credentials) {
            return self::$instance;
        }

        self::$instance = self::createPdo($credentials);
        self::$currentCredentials = $credentials;

        return self::$instance;
    }

    /**
     * Test connection credentials
     */
    public static function testConnection(array $credentials): bool
    {
        $pdo = self::createPdo($credentials);
        $pdo->query('SELECT 1');
        return true;
    }

    /**
     * Create raw PDO connection
     */
    private static function createPdo(array $credentials): PDO
    {
        $host = $credentials['host'] ?? '127.0.0.1';
        $port = (int)($credentials['port'] ?? 5432);
        $dbname = $credentials['database'] ?? 'postgres';
        $user = $credentials['username'] ?? 'postgres';
        $password = $credentials['password'] ?? '';
        $sslmode = $credentials['sslmode'] ?? 'prefer';

        $dsn = sprintf('pgsql:host=%s;port=%d;dbname=%s;sslmode=%s', $host, $port, $dbname, $sslmode);

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
            PDO::ATTR_TIMEOUT => 5,
        ];

        try {
            $pdo = new PDO($dsn, $user, $password, $options);
            // Ensure client encoding is UTF8
            $pdo->exec("SET client_encoding TO 'UTF8'");
            return $pdo;
        } catch (PDOException $e) {
            throw new RuntimeException('PostgreSQL Connection Failed: ' . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    public static function close(): void
    {
        self::$instance = null;
        self::$currentCredentials = null;
    }
}
