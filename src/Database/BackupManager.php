<?php

declare(strict_types=1);

namespace PosMyAdmin\Database;

use PDO;
use Exception;

class BackupManager
{
    private PDO $pdo;
    private SchemaManager $schemaManager;
    private TableManager $tableManager;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->schemaManager = new SchemaManager($pdo);
        $this->tableManager = new TableManager($pdo);
    }

    /**
     * Generate full SQL dump for the entire connected database
     */
    public function exportDatabase(
        bool $includeStructure = true,
        bool $includeData = true,
        bool $dropTables = true,
        bool $useTransaction = true
    ): string {
        $dbName = (string)$this->pdo->query('SELECT current_database()')->fetchColumn();
        $timestamp = date('Y-m-d H:i:s');
        $serverVer = (string)$this->pdo->query('SELECT version()')->fetchColumn();

        $sql = "-- ========================================================\n";
        $sql .= "-- PosMyAdmin PostgreSQL Database Dump\n";
        $sql .= "-- Database: {$dbName}\n";
        $sql .= "-- Date: {$timestamp}\n";
        $sql .= "-- Server: " . substr($serverVer, 0, strpos($serverVer, 'on') ?: 50) . "\n";
        $sql .= "-- ========================================================\n\n";

        $sql .= "SET statement_timeout = 0;\n";
        $sql .= "SET lock_timeout = 0;\n";
        $sql .= "SET client_encoding = 'UTF8';\n";
        $sql .= "SET standard_conforming_strings = on;\n";
        $sql .= "SET check_function_bodies = false;\n";
        $sql .= "SET client_min_messages = warning;\n\n";

        if ($useTransaction) {
            $sql .= "BEGIN;\n\n";
        }

        $schemas = $this->schemaManager->getSchemas();

        // 1. Schemas DDL
        if ($includeStructure) {
            $sql .= "--\n-- Schemas\n--\n";
            foreach ($schemas as $schema) {
                if ($schema !== 'public') {
                    $sql .= sprintf("CREATE SCHEMA IF NOT EXISTS \"%s\";\n", preg_replace('/[^a-zA-Z0-9_]/', '', $schema));
                }
            }
            $sql .= "\n";
        }

        // Collect all tables and views across all schemas
        $allTables = [];
        foreach ($schemas as $schema) {
            $items = $this->schemaManager->getTablesAndViews($schema);
            foreach ($items as $item) {
                if ($item['table_type'] === 'BASE TABLE') {
                    $allTables[] = [
                        'schema' => $schema,
                        'table' => $item['table_name'],
                    ];
                }
            }
        }

        // 2. Drop Tables if requested
        if ($includeStructure && $dropTables && !empty($allTables)) {
            $sql .= "--\n-- Drop Tables\n--\n";
            foreach ($allTables as $t) {
                $sql .= sprintf("DROP TABLE IF EXISTS \"%s\".\"%s\" CASCADE;\n", $t['schema'], $t['table']);
            }
            $sql .= "\n";
        }

        // 3. Table Structures (DDL)
        if ($includeStructure) {
            $sql .= "--\n-- Table Structures\n--\n";
            foreach ($allTables as $t) {
                $schema = $t['schema'];
                $table = $t['table'];
                $columns = $this->tableManager->getColumns($table, $schema);
                $primaryKeys = $this->tableManager->getPrimaryKeys($table, $schema);

                $colDefs = [];
                foreach ($columns as $c) {
                    $cName = '"' . preg_replace('/[^a-zA-Z0-9_]/', '', $c['column_name']) . '"';
                    $cType = $c['data_type'];
                    if (!empty($c['character_maximum_length'])) {
                        $cType .= '(' . $c['character_maximum_length'] . ')';
                    }
                    $nullable = ($c['is_nullable'] === 'YES') ? '' : 'NOT NULL';
                    $default = !empty($c['column_default']) ? 'DEFAULT ' . $c['column_default'] : '';

                    $colDefs[] = trim(sprintf("    %s %s %s %s", $cName, $cType, $nullable, $default));
                }

                if (!empty($primaryKeys)) {
                    $quotedPks = array_map(fn($k) => '"' . preg_replace('/[^a-zA-Z0-9_]/', '', $k) . '"', $primaryKeys);
                    $colDefs[] = sprintf("    PRIMARY KEY (%s)", implode(', ', $quotedPks));
                }

                $sql .= sprintf("CREATE TABLE \"%s\".\"%s\" (\n%s\n);\n\n", $schema, $table, implode(",\n", $colDefs));

                // Indexes (non-primary)
                $indexes = $this->tableManager->getIndexes($table, $schema);
                foreach ($indexes as $idx) {
                    // Skip automatic PK index definitions
                    if (!str_ends_with($idx['indexname'], '_pkey')) {
                        $sql .= $idx['indexdef'] . ";\n";
                    }
                }
            }
            $sql .= "\n";
        }

        // 4. Table Data Inserts
        if ($includeData) {
            $sql .= "--\n-- Data Inserts\n--\n";
            foreach ($allTables as $t) {
                $schema = $t['schema'];
                $table = $t['table'];
                $fullTable = sprintf('"%s"."%s"', $schema, $table);

                $stmt = $this->pdo->query("SELECT * FROM {$fullTable}");
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($rows)) {
                    $sql .= "-- Data for {$fullTable}\n";
                    $columns = array_keys($rows[0]);
                    $quotedCols = array_map(fn($c) => '"' . preg_replace('/[^a-zA-Z0-9_]/', '', $c) . '"', $columns);
                    $colsHeader = implode(', ', $quotedCols);

                    // Insert in batches of 50 for speed and readability
                    $chunks = array_chunk($rows, 50);
                    foreach ($chunks as $chunk) {
                        $valRows = [];
                        foreach ($chunk as $row) {
                            $vals = [];
                            foreach ($row as $v) {
                                if ($v === null) {
                                    $vals[] = 'NULL';
                                } elseif (is_bool($v)) {
                                    $vals[] = $v ? 'TRUE' : 'FALSE';
                                } elseif (is_numeric($v)) {
                                    $vals[] = $v;
                                } else {
                                    $vals[] = $this->pdo->quote((string)$v);
                                }
                            }
                            $valRows[] = '(' . implode(', ', $vals) . ')';
                        }
                        $sql .= sprintf("INSERT INTO %s (%s) VALUES\n  %s;\n", $fullTable, $colsHeader, implode(",\n  ", $valRows));
                    }
                    $sql .= "\n";
                }
            }
        }

        // 5. Foreign Key Constraints
        if ($includeStructure) {
            $sql .= "--\n-- Foreign Key Constraints\n--\n";
            foreach ($allTables as $t) {
                $schema = $t['schema'];
                $table = $t['table'];
                $fks = $this->tableManager->getForeignKeys($table, $schema);

                foreach ($fks as $fk) {
                    $sql .= sprintf(
                        "ALTER TABLE \"%s\".\"%s\" ADD CONSTRAINT \"%s\" FOREIGN KEY (\"%s\") REFERENCES \"%s\".\"%s\" (\"%s\") ON UPDATE %s ON DELETE %s;\n",
                        $schema,
                        $table,
                        $fk['constraint_name'],
                        $fk['column_name'],
                        $fk['foreign_table_schema'] ?: 'public',
                        $fk['foreign_table_name'],
                        $fk['foreign_column_name'],
                        $fk['update_rule'],
                        $fk['delete_rule']
                    );
                }
            }
            $sql .= "\n";
        }

        if ($useTransaction) {
            $sql .= "COMMIT;\n";
        }

        return $sql;
    }

    /**
     * Import raw SQL statements into current database
     */
    public function importSql(string $sql, bool $useTransaction = true): array
    {
        $trimmed = trim($sql);
        if (empty($trimmed)) {
            throw new Exception('SQL content is empty.');
        }

        $startTime = microtime(true);
        $executedCount = 0;

        // If useTransaction and SQL doesn't already contain BEGIN/COMMIT
        $hasInternalTx = preg_match('/^\s*BEGIN\s*;/im', $trimmed);
        if ($useTransaction && !$hasInternalTx) {
            $this->pdo->beginTransaction();
        }

        try {
            // Execute the SQL script directly
            $this->pdo->exec($trimmed);
            $durationMs = round((microtime(true) - $startTime) * 1000, 2);

            if ($useTransaction && !$hasInternalTx && $this->pdo->inTransaction()) {
                $this->pdo->commit();
            }

            // Count approximate statements
            $count = max(1, substr_count($trimmed, ';'));

            return [
                'success' => true,
                'statements_count' => $count,
                'duration_ms' => $durationMs,
                'message' => "SQL script executed successfully ({$count} statements in {$durationMs} ms).",
            ];
        } catch (Exception $e) {
            if ($useTransaction && !$hasInternalTx && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            $durationMs = round((microtime(true) - $startTime) * 1000, 2);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'duration_ms' => $durationMs,
            ];
        }
    }
}
