<?php

declare(strict_types=1);

namespace PosMyAdmin\Database;

use PDO;
use Exception;

class QueryExecutor
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Execute SQL query and return performance metrics and results
     */
    public function execute(string $sql): array
    {
        $trimmed = trim($sql);
        if (empty($trimmed)) {
            throw new Exception('SQL query cannot be empty.');
        }

        $startTime = microtime(true);
        $isSelect = preg_match('/^\s*(SELECT|WITH|SHOW|EXPLAIN)\b/i', $trimmed);

        try {
            $stmt = $this->pdo->prepare($trimmed);
            $stmt->execute();
            $durationMs = round((microtime(true) - $startTime) * 1000, 2);

            if ($isSelect) {
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $columns = !empty($rows) ? array_keys($rows[0]) : [];

                return [
                    'success' => true,
                    'is_select' => true,
                    'rows' => $rows,
                    'columns' => $columns,
                    'count' => count($rows),
                    'affected_rows' => $stmt->rowCount(),
                    'duration_ms' => $durationMs,
                    'query' => $trimmed,
                ];
            }

            return [
                'success' => true,
                'is_select' => false,
                'rows' => [],
                'columns' => [],
                'count' => 0,
                'affected_rows' => $stmt->rowCount(),
                'duration_ms' => $durationMs,
                'query' => $trimmed,
            ];
        } catch (Exception $e) {
            $durationMs = round((microtime(true) - $startTime) * 1000, 2);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'duration_ms' => $durationMs,
                'query' => $trimmed,
            ];
        }
    }

    /**
     * Run EXPLAIN (ANALYZE, BUFFERS) on a query
     */
    public function explain(string $sql): array
    {
        $explainSql = 'EXPLAIN (ANALYZE, BUFFERS, VERBOSE, FORMAT TEXT) ' . $sql;
        return $this->execute($explainSql);
    }
}
