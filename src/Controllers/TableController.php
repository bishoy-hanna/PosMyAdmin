<?php

declare(strict_types=1);

namespace PosMyAdmin\Controllers;

use PosMyAdmin\Auth\CSRF;
use PosMyAdmin\Database\TableManager;
use PosMyAdmin\Http\Request;
use PosMyAdmin\Http\Response;
use Exception;

class TableController extends BaseController
{
    private TableManager $tableManager;

    protected function initTableManager(): void
    {
        $this->requireAuth();
        $this->tableManager = new TableManager($this->pdo);
    }

    /**
     * Browse Table Data with pagination, sorting, and filters
     */
    public function browse(Request $request): void
    {
        $this->initTableManager();

        $table = (string)$request->get('table', '');
        $schema = (string)$request->get('schema', 'public');

        if (empty($table)) {
            $this->session->flash('error', 'Table name is required.');
            Response::redirect('/');
        }

        $page = max(1, (int)$request->get('page', 1));
        $perPage = max(10, min(200, (int)$request->get('per_page', 25)));
        $orderBy = $request->get('order_by');
        $orderDir = strtoupper((string)$request->get('order_dir', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

        $filterColumn = $request->get('filter_col');
        $filterOperator = $request->get('filter_op');
        $filterValue = $request->get('filter_val');

        $browseData = $this->tableManager->browse(
            $table,
            $schema,
            $page,
            $perPage,
            $orderBy,
            $orderDir,
            $filterColumn,
            $filterOperator,
            $filterValue
        );

        $this->render('table.browse', array_merge($browseData, [
            'table' => $table,
            'schema' => $schema,
            'filter_col' => $filterColumn,
            'filter_op' => $filterOperator,
            'filter_val' => $filterValue,
            'pageTitle' => "Browse: {$schema}.{$table}",
        ]));
    }

    /**
     * View Table Structure (Columns, Types, Constraints, Indexes)
     */
    public function structure(Request $request): void
    {
        $this->initTableManager();

        $table = (string)$request->get('table', '');
        $schema = (string)$request->get('schema', 'public');

        if (empty($table)) {
            $this->session->flash('error', 'Table name is required.');
            Response::redirect('/');
        }

        $columns = $this->tableManager->getColumns($table, $schema);
        $indexes = $this->tableManager->getIndexes($table, $schema);
        $foreignKeys = $this->tableManager->getForeignKeys($table, $schema);
        $primaryKeys = $this->tableManager->getPrimaryKeys($table, $schema);

        $this->render('table.structure', [
            'table' => $table,
            'schema' => $schema,
            'columns' => $columns,
            'indexes' => $indexes,
            'foreignKeys' => $foreignKeys,
            'primaryKeys' => $primaryKeys,
            'pageTitle' => "Structure: {$schema}.{$table}",
        ]);
    }

    /**
     * Show Insert Form
     */
    public function showInsert(Request $request): void
    {
        $this->initTableManager();

        $table = (string)$request->get('table', '');
        $schema = (string)$request->get('schema', 'public');

        $columns = $this->tableManager->getColumns($table, $schema);

        $this->render('table.insert', [
            'table' => $table,
            'schema' => $schema,
            'columns' => $columns,
            'pageTitle' => "Insert Row: {$schema}.{$table}",
        ]);
    }

    /**
     * Handle Row Insert
     */
    public function insert(Request $request): void
    {
        $this->initTableManager();

        if (!CSRF::validate($request->csrfToken())) {
            $this->session->flash('error', 'Invalid CSRF token.');
            Response::redirect('/table/insert?' . http_build_query([
                'table' => $request->post('table'),
                'schema' => $request->post('schema', 'public'),
            ]));
        }

        $table = (string)$request->post('table', '');
        $schema = (string)$request->post('schema', 'public');
        $data = (array)$request->post('data', []);

        try {
            $this->tableManager->insertRow($table, $schema, $data);
            $this->session->flash('success', 'Row inserted successfully.');
            Response::redirect('/table/browse?' . http_build_query(['table' => $table, 'schema' => $schema]));
        } catch (Exception $e) {
            $this->session->flash('error', 'Error inserting row: ' . $e->getMessage());
            Response::redirect('/table/insert?' . http_build_query(['table' => $table, 'schema' => $schema]));
        }
    }

    /**
     * Handle Row Update (AJAX or form)
     */
    public function update(Request $request): void
    {
        $this->initTableManager();

        if (!CSRF::validate($request->csrfToken())) {
            if ($request->isAjax()) {
                Response::json(['success' => false, 'error' => 'Invalid CSRF token.'], 403);
            }
            $this->session->flash('error', 'Invalid CSRF token.');
            Response::redirect('/');
        }

        $payload = $request->json() ?: $request->all();
        $table = (string)($payload['table'] ?? '');
        $schema = (string)($payload['schema'] ?? 'public');
        $pks = (array)($payload['primary_keys'] ?? []);
        $data = (array)($payload['data'] ?? []);

        try {
            $this->tableManager->updateRow($table, $schema, $pks, $data);

            if ($request->isAjax()) {
                Response::json(['success' => true, 'message' => 'Row updated successfully.']);
            }

            $this->session->flash('success', 'Row updated successfully.');
            Response::redirect('/table/browse?' . http_build_query(['table' => $table, 'schema' => $schema]));
        } catch (Exception $e) {
            if ($request->isAjax()) {
                Response::json(['success' => false, 'error' => $e->getMessage()], 400);
            }
            $this->session->flash('error', 'Error updating row: ' . $e->getMessage());
            Response::redirect('/table/browse?' . http_build_query(['table' => $table, 'schema' => $schema]));
        }
    }

    /**
     * Handle Row Delete
     */
    public function delete(Request $request): void
    {
        $this->initTableManager();

        if (!CSRF::validate($request->csrfToken())) {
            if ($request->isAjax()) {
                Response::json(['success' => false, 'error' => 'Invalid CSRF token.'], 403);
            }
            $this->session->flash('error', 'Invalid CSRF token.');
            Response::redirect('/');
        }

        $payload = $request->json() ?: $request->all();
        $table = (string)($payload['table'] ?? '');
        $schema = (string)($payload['schema'] ?? 'public');
        $pks = (array)($payload['primary_keys'] ?? []);

        try {
            $this->tableManager->deleteRow($table, $schema, $pks);

            if ($request->isAjax()) {
                Response::json(['success' => true, 'message' => 'Row deleted successfully.']);
            }

            $this->session->flash('success', 'Row deleted successfully.');
            Response::redirect('/table/browse?' . http_build_query(['table' => $table, 'schema' => $schema]));
        } catch (Exception $e) {
            if ($request->isAjax()) {
                Response::json(['success' => false, 'error' => $e->getMessage()], 400);
            }
            $this->session->flash('error', 'Error deleting row: ' . $e->getMessage());
            Response::redirect('/table/browse?' . http_build_query(['table' => $table, 'schema' => $schema]));
        }
    }

    /**
     * Truncate Table
     */
    public function truncate(Request $request): void
    {
        $this->initTableManager();

        if (!CSRF::validate($request->csrfToken())) {
            $this->session->flash('error', 'Invalid CSRF token.');
            Response::redirect('/');
        }

        $table = (string)$request->post('table', '');
        $schema = (string)$request->post('schema', 'public');
        $cascade = (bool)$request->post('cascade', false);

        try {
            $this->tableManager->truncateTable($table, $schema, $cascade);
            $this->session->flash('success', "Table '{$schema}.{$table}' truncated.");
        } catch (Exception $e) {
            $this->session->flash('error', 'Error truncating table: ' . $e->getMessage());
        }

        Response::redirect('/table/browse?' . http_build_query(['table' => $table, 'schema' => $schema]));
    }

    /**
     * Drop Table
     */
    public function drop(Request $request): void
    {
        $this->initTableManager();

        if (!CSRF::validate($request->csrfToken())) {
            $this->session->flash('error', 'Invalid CSRF token.');
            Response::redirect('/');
        }

        $table = (string)$request->post('table', '');
        $schema = (string)$request->post('schema', 'public');
        $cascade = (bool)$request->post('cascade', false);

        try {
            $this->tableManager->dropTable($table, $schema, $cascade);
            $this->session->flash('success', "Table '{$schema}.{$table}' dropped.");
        } catch (Exception $e) {
            $this->session->flash('error', 'Error dropping table: ' . $e->getMessage());
        }

        Response::redirect('/');
    }

    /**
     * Show Create Table Form
     */
    public function showCreate(Request $request): void
    {
        $this->requireAuth();
        $schema = (string)$request->get('schema', 'public');

        $this->render('table.create', [
            'schema' => $schema,
            'pageTitle' => 'Create New Table',
        ]);
    }

    /**
     * Create Table
     */
    public function create(Request $request): void
    {
        $this->initTableManager();

        if (!CSRF::validate($request->csrfToken())) {
            $this->session->flash('error', 'Invalid CSRF token.');
            Response::redirect('/table/create');
        }

        $table = trim((string)$request->post('table', ''));
        $schema = trim((string)$request->post('schema', 'public'));
        $columns = (array)$request->post('columns', []);

        try {
            $this->tableManager->createTable($table, $schema, $columns);
            $this->session->flash('success', "Table '{$schema}.{$table}' created successfully.");
            Response::redirect('/table/browse?' . http_build_query(['table' => $table, 'schema' => $schema]));
        } catch (Exception $e) {
            $this->session->flash('error', 'Error creating table: ' . $e->getMessage());
            Response::redirect('/table/create?' . http_build_query(['schema' => $schema]));
        }
    }
}
