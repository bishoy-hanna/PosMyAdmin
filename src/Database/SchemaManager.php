<?php

declare(strict_types=1);

namespace PosMyAdmin\Database;

use PDO;
use Exception;

class SchemaManager
{
    private PDO $pdo;
    private array $hiddenSchemas;

    public function __construct(PDO $pdo, array $hiddenSchemas = [])
    {
        $this->pdo = $pdo;
        $this->hiddenSchemas = $hiddenSchemas ?: ['pg_toast', 'pg_temp_1', 'pg_toast_temp_1', 'information_schema'];
    }

    /**
     * Get all accessible databases on the server
     */
    public function getDatabases(): array
    {
        $query = "
            SELECT 
                datname,
                datname AS name,
                pg_catalog.pg_get_userbyid(datdba) AS owner,
                pg_catalog.pg_encoding_to_char(encoding) AS encoding,
                datcollate,
                pg_catalog.pg_size_pretty(pg_catalog.pg_database_size(datname)) AS size,
                pg_catalog.pg_size_pretty(pg_catalog.pg_database_size(datname)) AS size_pretty
            FROM pg_catalog.pg_database
            WHERE datistemplate = false
            ORDER BY datname ASC
        ";

        try {
            return $this->pdo->query($query)->fetchAll() ?: [];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get schemas in current database
     */
    public function getSchemas(): array
    {
        $placeholders = implode(',', array_fill(0, count($this->hiddenSchemas), '?'));
        $query = "
            SELECT schema_name 
            FROM information_schema.schemata 
            WHERE schema_name NOT IN ($placeholders)
            ORDER BY (schema_name = 'public') DESC, schema_name ASC
        ";

        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($this->hiddenSchemas);
            return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        } catch (Exception $e) {
            return ['public'];
        }
    }

    /**
     * Get tables and views in a specific schema
     */
    public function getTablesAndViews(string $schema = 'public'): array
    {
        $query = "
            SELECT 
                t.table_name,
                t.table_type,
                COALESCE(c.reltuples::bigint, 0) AS estimated_rows,
                pg_size_pretty(pg_total_relation_size(quote_ident(t.table_schema) || '.' || quote_ident(t.table_name))) AS total_size
            FROM information_schema.tables t
            LEFT JOIN pg_class c ON c.relname = t.table_name
            LEFT JOIN pg_namespace n ON n.oid = c.relnamespace AND n.nspname = t.table_schema
            WHERE t.table_schema = :schema
            ORDER BY t.table_name ASC
        ";

        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute(['schema' => $schema]);
            return $stmt->fetchAll() ?: [];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Build full navigation tree (Schemas -> Tables & Views)
     */
    public function getNavigationTree(): array
    {
        $schemas = $this->getSchemas();
        $tree = [];

        foreach ($schemas as $schema) {
            $tree[$schema] = $this->getTablesAndViews($schema);
        }

        return $tree;
    }

    /**
     * Create a new database
     */
    public function createDatabase(string $name, string $encoding = 'UTF8', ?string $owner = null): bool
    {
        // Sanitize database name identifier
        $cleanName = preg_replace('/[^a-zA-Z0-9_]/', '', $name);
        if (empty($cleanName)) {
            throw new Exception('Invalid database name.');
        }

        $sql = sprintf('CREATE DATABASE "%s" WITH ENCODING %s', $cleanName, $this->pdo->quote($encoding));
        if ($owner) {
            $cleanOwner = preg_replace('/[^a-zA-Z0-9_]/', '', $owner);
            $sql .= sprintf(' OWNER "%s"', $cleanOwner);
        }

        $this->pdo->exec($sql);
        return true;
    }

    /**
     * Drop a database
     */
    public function dropDatabase(string $name): bool
    {
        $cleanName = preg_replace('/[^a-zA-Z0-9_]/', '', $name);
        if (empty($cleanName)) {
            throw new Exception('Invalid database name.');
        }

        // Drop with force to terminate other connections if supported
        try {
            $this->pdo->exec(sprintf('DROP DATABASE "%s" WITH (FORCE)', $cleanName));
        } catch (Exception $e) {
            $this->pdo->exec(sprintf('DROP DATABASE "%s"', $cleanName));
        }

        return true;
    }

    /**
     * Create a new schema
     */
    public function createSchema(string $name): bool
    {
        $cleanName = preg_replace('/[^a-zA-Z0-9_]/', '', $name);
        if (empty($cleanName)) {
            throw new Exception('Invalid schema name.');
        }

        $this->pdo->exec(sprintf('CREATE SCHEMA "%s"', $cleanName));
        return true;
    }

    /**
     * Drop a schema
     */
    public function dropSchema(string $name, bool $cascade = false): bool
    {
        $cleanName = preg_replace('/[^a-zA-Z0-9_]/', '', $name);
        if (empty($cleanName)) {
            throw new Exception('Invalid schema name.');
        }

        $sql = sprintf('DROP SCHEMA "%s"', $cleanName);
        if ($cascade) {
            $sql .= ' CASCADE';
        }

        $this->pdo->exec($sql);
        return true;
    }
}
