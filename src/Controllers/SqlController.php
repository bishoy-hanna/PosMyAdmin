<?php

declare(strict_types=1);

namespace PosMyAdmin\Controllers;

use PosMyAdmin\Auth\CSRF;
use PosMyAdmin\Database\QueryExecutor;
use PosMyAdmin\Http\Request;
use PosMyAdmin\Http\Response;
use Exception;

class SqlController extends BaseController
{
    private QueryExecutor $queryExecutor;

    protected function initExecutor(): void
    {
        $this->requireAuth();
        $this->queryExecutor = new QueryExecutor($this->pdo);
    }

    public function index(Request $request): void
    {
        $this->initExecutor();

        $initialQuery = (string)$request->get('query', '');
        $tableName = (string)$request->get('table', '');
        $schemaName = (string)$request->get('schema', 'public');

        if (empty($initialQuery) && !empty($tableName)) {
            $initialQuery = sprintf('SELECT * FROM "%s"."%s" LIMIT 50;', $schemaName, $tableName);
        }

        $this->render('sql.query', [
            'initialQuery' => $initialQuery,
            'result' => null,
            'pageTitle' => 'SQL Console',
        ]);
    }

    public function execute(Request $request): void
    {
        $this->initExecutor();

        if (!CSRF::validate($request->csrfToken())) {
            if ($request->isAjax()) {
                Response::json(['success' => false, 'error' => 'Invalid CSRF token.'], 403);
            }
            $this->session->flash('error', 'Invalid CSRF token.');
            Response::redirect('/sql');
        }

        $query = (string)$request->post('query', '');
        $action = (string)$request->post('action', 'execute');

        if ($action === 'explain') {
            $result = $this->queryExecutor->explain($query);
        } else {
            $result = $this->queryExecutor->execute($query);
        }

        if ($request->isAjax()) {
            Response::json($result);
        }

        $this->render('sql.query', [
            'initialQuery' => $query,
            'result' => $result,
            'pageTitle' => 'SQL Console',
        ]);
    }

    public function export(Request $request): void
    {
        $this->initExecutor();

        if (!CSRF::validate($request->csrfToken())) {
            $this->session->flash('error', 'Invalid CSRF token.');
            Response::redirect('/sql');
        }

        $query = (string)$request->post('query', '');
        $format = strtolower((string)$request->post('format', 'csv'));

        $result = $this->queryExecutor->execute($query);

        if (!$result['success']) {
            $this->session->flash('error', 'Query error: ' . ($result['error'] ?? 'Execution failed'));
            Response::redirect('/sql');
        }

        $rows = $result['rows'] ?? [];
        $columns = $result['columns'] ?? [];
        $timestamp = date('Ymd_His');

        if ($format === 'json') {
            $jsonContent = json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            Response::download("query_export_{$timestamp}.json", $jsonContent, 'application/json');
        } else {
            // CSV
            $output = fopen('php://memory', 'r+');
            fputcsv($output, $columns);
            foreach ($rows as $row) {
                fputcsv($output, $row);
            }
            rewind($output);
            $csvContent = stream_get_contents($output);
            fclose($output);

            Response::download("query_export_{$timestamp}.csv", (string)$csvContent, 'text/csv');
        }
    }
}
