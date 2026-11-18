<?php

declare(strict_types=1);

namespace PosMyAdmin\Database;

use PDO;
use Exception;

class TableManager
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Get table column metadata
     */
    public function getColumns(string $table, string $schema = 'public'): array
    {
        $query = "
            SELECT 
                c.column_name,
                c.data_type,
                c.udt_name,
                c.character_maximum_length,
                c.is_nullable,
                c.column_default,
                pgd.description as column_comment,
                CASE WHEN pk.column_name IS NOT NULL THEN true ELSE false END AS is_primary_key
            FROM information_schema.columns c
            LEFT JOIN (
                SELECT ku.column_name
                FROM information_schema.table_constraints tc
                JOIN information_schema.key_column_usage ku
                    ON tc.constraint_name = ku.constraint_name
                    AND tc.table_schema = ku.table_schema
                WHERE tc.constraint_type = 'PRIMARY KEY'
                    AND tc.table_name = :table
                    AND tc.table_schema = :schema
            ) pk ON pk.column_name = c.column_name
            LEFT JOIN pg_catalog.pg_statio_all_tables as st 
                ON st.schemaname = c.table_schema AND st.relname = c.table_name
            LEFT JOIN pg_catalog.pg_description pgd 
                ON pgd.objoid = st.relid AND pgd.objsubid = c.ordinal_position
            WHERE c.table_name = :table AND c.table_schema = :schema
            ORDER BY c.ordinal_position ASC
        ";

        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute(['table' => $table, 'schema' => $schema]);
            return $stmt->fetchAll() ?: [];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get primary key column names
     */
    public function getPrimaryKeys(string $table, string $schema = 'public'): array
    {
        $query = "
            SELECT ku.column_name
            FROM information_schema.table_constraints tc
            JOIN information_schema.key_column_usage ku
                ON tc.constraint_name = ku.constraint_name
                AND tc.table_schema = ku.table_schema
            WHERE tc.constraint_type = 'PRIMARY KEY'
                AND tc.table_name = :table
                AND tc.table_schema = :schema
            ORDER BY ku.ordinal_position
        ";

        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute(['table' => $table, 'schema' => $schema]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get table indexes
     */
    public function getIndexes(string $table, string $schema = 'public'): array
    {
        $query = "
            SELECT 
                indexname,
                indexdef
            FROM pg_indexes
            WHERE tablename = :table AND schemaname = :schema
            ORDER BY indexname ASC
        ";

        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute(['table' => $table, 'schema' => $schema]);
            return $stmt->fetchAll() ?: [];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get foreign keys
     */
    public function getForeignKeys(string $table, string $schema = 'public'): array
    {
        $query = "
            SELECT
                tc.constraint_name,
                kcu.column_name,
                ccu.table_schema AS foreign_table_schema,
                ccu.table_name AS foreign_table_name,
                ccu.column_name AS foreign_column_name,
                rc.update_rule,
                rc.delete_rule
            FROM information_schema.table_constraints AS tc
            JOIN information_schema.key_column_usage AS kcu
                ON tc.constraint_name = kcu.constraint_name
                AND tc.table_schema = kcu.table_schema
            JOIN information_schema.constraint_column_usage AS ccu
                ON ccu.constraint_name = tc.constraint_name
                AND ccu.table_schema = tc.table_schema
            JOIN information_schema.referential_constraints AS rc
                ON rc.constraint_name = tc.constraint_name
            WHERE tc.constraint_type = 'FOREIGN KEY'
                AND tc.table_name = :table
                AND tc.table_schema = :schema
        ";

        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute(['table' => $table, 'schema' => $schema]);
            return $stmt->fetchAll() ?: [];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Browse table data with pagination, sorting, and filtering
     */
    public function browse(
        string $table,
        string $schema = 'public',
        int $page = 1,
        int $perPage = 25,
        ?string $orderBy = null,
        string $orderDir = 'ASC',
        ?string $filterColumn = null,
        ?string $filterOperator = null,
        ?string $filterValue = null
    ): array {
        $safeSchema = preg_replace('/[^a-zA-Z0-9_]/', '', $schema);
        $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $fullTable = sprintf('"%s"."%s"', $safeSchema, $safeTable);

        $tableColumns = $this->getColumns($table, $schema);
        $validColumns = array_column($tableColumns, 'column_name');
        $columnTypes = [];
        foreach ($tableColumns as $colMeta) {
            $columnTypes[$colMeta['column_name']] = strtolower((string)($colMeta['udt_name'] ?? $colMeta['data_type'] ?? 'text'));
        }

        $params = [];
        $whereSql = '';

        // Whitelist allowed operators
        $allowedOperators = ['=', '!=', '>', '<', '>=', '<=', 'ILIKE', 'LIKE', 'IS NULL', 'IS NOT NULL'];
        if ($filterColumn && $filterOperator && in_array($filterColumn, $validColumns, true) && in_array($filterOperator, $allowedOperators, true)) {
            $cleanCol = preg_replace('/[^a-zA-Z0-9_]/', '', $filterColumn);
            $safeCol = "\"{$cleanCol}\"";
            $colType = $columnTypes[$filterColumn] ?? 'text';

            if ($filterOperator === 'IS NULL') {
                $whereSql = "WHERE {$safeCol} IS NULL";
            } elseif ($filterOperator === 'IS NOT NULL') {
                $whereSql = "WHERE {$safeCol} IS NOT NULL";
            } elseif ($filterOperator === 'ILIKE' || $filterOperator === 'LIKE') {
                $whereSql = "WHERE {$safeCol}::text ILIKE :filter_val";
                $params['filter_val'] = '%' . $filterValue . '%';
            } elseif (in_array($filterOperator, ['=', '!='], true)) {
                // By casting column to text, PostgreSQL safely compares any user input (including single quotes or symbols)
                // without throwing 22P02 invalid input syntax error for numeric/uuid/boolean columns!
                $whereSql = "WHERE {$safeCol}::text {$filterOperator} :filter_val";
                $params['filter_val'] = (string)$filterValue;
            } elseif (in_array($filterOperator, ['>', '<', '>=', '<='], true)) {
                $isNumeric = in_array($colType, ['int2', 'int4', 'int8', 'integer', 'bigint', 'smallint', 'numeric', 'float4', 'float8', 'real', 'double precision'], true);
                if ($isNumeric) {
                    if (is_numeric($filterValue)) {
                        $whereSql = "WHERE {$safeCol} {$filterOperator} :filter_val";
                        $params['filter_val'] = $filterValue;
                    } else {
                        // User typed non-numeric value for numeric comparison (e.g. > 8')
                        // Safely return 0 results without SQL error
                        $whereSql = "WHERE 1 = 0";
                    }
                } else {
                    $whereSql = "WHERE {$safeCol}::text {$filterOperator} :filter_val";
                    $params['filter_val'] = (string)$filterValue;
                }
            }
        }

        // Whitelist and safely quote order by column
        $orderSql = '';
        if ($orderBy && in_array($orderBy, $validColumns, true)) {
            $safeOrderBy = preg_replace('/[^a-zA-Z0-9_]/', '', $orderBy);
            $dir = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';
            $orderSql = "ORDER BY \"{$safeOrderBy}\" {$dir} NULLS LAST";
        } else {
            $pks = $this->getPrimaryKeys($table, $schema);
            if (!empty($pks)) {
                $pkOrders = array_map(fn($k) => '"' . preg_replace('/[^a-zA-Z0-9_]/', '', $k) . '" ASC', $pks);
                $orderSql = 'ORDER BY ' . implode(', ', $pkOrders);
            }
        }

        $offset = max(0, ($page - 1) * $perPage);
        $countSql = "SELECT COUNT(*) FROM {$fullTable} {$whereSql}";
        $dataSql = "SELECT * FROM {$fullTable} {$whereSql} {$orderSql} LIMIT :limit OFFSET :offset";

        try {
            $countStmt = $this->pdo->prepare($countSql);
            $countStmt->execute($params);
            $totalRows = (int)$countStmt->fetchColumn();

            $dataStmt = $this->pdo->prepare($dataSql);
            foreach ($params as $k => $v) {
                $dataStmt->bindValue($k, $v);
            }
            $dataStmt->bindValue('limit', $perPage, PDO::PARAM_INT);
            $dataStmt->bindValue('offset', $offset, PDO::PARAM_INT);
            $dataStmt->execute();

            $rows = $dataStmt->fetchAll();
        } catch (\Exception $e) {
            \PosMyAdmin\Utils\Logger::warning("Database search query warning: " . $e->getMessage(), [
                'table' => $table,
                'filter_col' => $filterColumn,
                'filter_val' => $filterValue,
            ]);
            $totalRows = 0;
            $rows = [];
        }

        return [
            'rows' => $rows,
            'total_rows' => $totalRows,
            'current_page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int)ceil($totalRows / max(1, $perPage)),
            'order_by' => $orderBy,
            'order_dir' => $orderDir,
            'primary_keys' => $this->getPrimaryKeys($table, $schema),
            'foreign_keys' => $this->getForeignKeys($table, $schema),
            'columns' => $tableColumns,
        ];
    }

    /**
     * Insert a new record
     */
    public function insertRow(string $table, string $schema, array $data): bool
    {
        $safeSchema = preg_replace('/[^a-zA-Z0-9_]/', '', $schema);
        $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $tableCols = array_map(fn($c) => $c['column_name'], $this->getColumns($table, $schema));

        $cols = [];
        $placeholders = [];
        $params = [];

        foreach ($data as $col => $val) {
            if (!in_array($col, $tableCols, true)) {
                continue;
            }
            $cleanCol = preg_replace('/[^a-zA-Z0-9_]/', '', $col);
            if ($val === '' || $val === null) {
                // If empty string and column allows default/null, omit or set null
                continue;
            }
            $cols[] = '"' . $cleanCol . '"';
            $placeholders[] = ':' . $cleanCol;
            $params[$cleanCol] = $val;
        }

        if (empty($cols)) {
            $sql = sprintf('INSERT INTO "%s"."%s" DEFAULT VALUES', $safeSchema, $safeTable);
            $this->pdo->exec($sql);
            return true;
        }

        $sql = sprintf(
            'INSERT INTO "%s"."%s" (%s) VALUES (%s)',
            $safeSchema,
            $safeTable,
            implode(', ', $cols),
            implode(', ', $placeholders)
        );

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Update an existing record by primary keys or exact match
     */
    public function updateRow(string $table, string $schema, array $primaryKeys, array $newData): bool
    {
        $safeSchema = preg_replace('/[^a-zA-Z0-9_]/', '', $schema);
        $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $tableCols = array_map(fn($c) => $c['column_name'], $this->getColumns($table, $schema));

        $sets = [];
        $params = [];

        foreach ($newData as $col => $val) {
            if (!in_array($col, $tableCols, true)) {
                continue;
            }
            $cleanCol = preg_replace('/[^a-zA-Z0-9_]/', '', $col);
            $paramName = 'val_' . $cleanCol;
            $sets[] = '"' . $cleanCol . '" = :' . $paramName;
            $params[$paramName] = ($val === '') ? null : $val;
        }

        if (empty($sets)) {
            return false;
        }

        $wheres = [];
        foreach ($primaryKeys as $pkCol => $pkVal) {
            if (!in_array($pkCol, $tableCols, true)) {
                continue;
            }
            $cleanPk = preg_replace('/[^a-zA-Z0-9_]/', '', $pkCol);
            $pkParam = 'pk_' . $cleanPk;
            if ($pkVal === null) {
                $wheres[] = '"' . $cleanPk . '" IS NULL';
            } else {
                $wheres[] = '"' . $cleanPk . '" = :' . $pkParam;
                $params[$pkParam] = $pkVal;
            }
        }

        if (empty($wheres)) {
            throw new Exception('Cannot update without primary keys or row identifier.');
        }

        $sql = sprintf(
            'UPDATE "%s"."%s" SET %s WHERE %s',
            $safeSchema,
            $safeTable,
            implode(', ', $sets),
            implode(' AND ', $wheres)
        );

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Delete a row by primary keys
     */
    public function deleteRow(string $table, string $schema, array $primaryKeys): bool
    {
        $safeSchema = preg_replace('/[^a-zA-Z0-9_]/', '', $schema);
        $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $tableCols = array_map(fn($c) => $c['column_name'], $this->getColumns($table, $schema));

        $wheres = [];
        $params = [];

        foreach ($primaryKeys as $pkCol => $pkVal) {
            if (!in_array($pkCol, $tableCols, true)) {
                continue;
            }
            $cleanPk = preg_replace('/[^a-zA-Z0-9_]/', '', $pkCol);
            $pkParam = 'pk_' . $cleanPk;
            if ($pkVal === null) {
                $wheres[] = '"' . $cleanPk . '" IS NULL';
            } else {
                $wheres[] = '"' . $cleanPk . '" = :' . $pkParam;
                $params[$pkParam] = $pkVal;
            }
        }

        if (empty($wheres)) {
            throw new Exception('Cannot delete without primary key identifier.');
        }

        $sql = sprintf(
            'DELETE FROM "%s"."%s" WHERE %s',
            $safeSchema,
            $safeTable,
            implode(' AND ', $wheres)
        );

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Truncate table
     */
    public function truncateTable(string $table, string $schema = 'public', bool $cascade = false): bool
    {
        $safeSchema = preg_replace('/[^a-zA-Z0-9_]/', '', $schema);
        $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);

        $sql = sprintf('TRUNCATE TABLE "%s"."%s" RESTART IDENTITY', $safeSchema, $safeTable);
        if ($cascade) {
            $sql .= ' CASCADE';
        }

        $this->pdo->exec($sql);
        return true;
    }

    /**
     * Drop table
     */
    public function dropTable(string $table, string $schema = 'public', bool $cascade = false): bool
    {
        $safeSchema = preg_replace('/[^a-zA-Z0-9_]/', '', $schema);
        $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);

        $sql = sprintf('DROP TABLE "%s"."%s"', $safeSchema, $safeTable);
        if ($cascade) {
            $sql .= ' CASCADE';
        }

        $this->pdo->exec($sql);
        return true;
    }

    /**
     * Create a table
     */
    public function createTable(string $table, string $schema, array $columns): bool
    {
        $safeSchema = preg_replace('/[^a-zA-Z0-9_]/', '', $schema);
        $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);

        if (empty($safeTable)) {
            throw new Exception('Invalid table name.');
        }

        $colDefs = [];
        $pks = [];

        foreach ($columns as $col) {
            $name = preg_replace('/[^a-zA-Z0-9_]/', '', $col['name'] ?? '');
            $type = strtoupper(trim($col['type'] ?? 'VARCHAR(255)'));
            $nullable = !empty($col['nullable']) ? '' : 'NOT NULL';
            $default = !empty($col['default']) ? 'DEFAULT ' . $col['default'] : '';
            $isPk = !empty($col['primary_key']);

            if (empty($name)) {
                continue;
            }

            $colDefs[] = trim(sprintf('"%s" %s %s %s', $name, $type, $nullable, $default));

            if ($isPk) {
                $pks[] = '"' . $name . '"';
            }
        }

        if (!empty($pks)) {
            $colDefs[] = 'PRIMARY KEY (' . implode(', ', $pks) . ')';
        }

        if (empty($colDefs)) {
            throw new Exception('At least one column is required.');
        }

        $sql = sprintf('CREATE TABLE "%s"."%s" (%s)', $safeSchema, $safeTable, implode(",\n  ", $colDefs));
        $this->pdo->exec($sql);
        return true;
    }
}
