<?php

declare(strict_types=1);

namespace PosMyAdmin\Controllers;

use PosMyAdmin\Auth\CSRF;
use PosMyAdmin\Database\BackupManager;
use PosMyAdmin\Http\Request;
use PosMyAdmin\Http\Response;
use PDO;
use Exception;

class ExportController extends BaseController
{
    private BackupManager $backupManager;

    protected function initBackupManager(): void
    {
        $this->requireAuth();
        $this->backupManager = new BackupManager($this->pdo);
    }

    /**
     * Show Export & Import Hub page
     */
    public function backupView(Request $request): void
    {
        $this->initBackupManager();

        $activeTab = (string)$request->get('tab', 'export');
        $currentDb = $this->session->getUserInfo()['database'] ?? 'postgres';

        $this->render('database.backup', [
            'activeTab' => $activeTab,
            'currentDb' => $currentDb,
            'pageTitle' => "Backup & Import ({$currentDb})",
        ]);
    }

    /**
     * Handle Database SQL Export Download
     */
    public function exportDatabase(Request $request): void
    {
        $this->initBackupManager();

        $includeStructure = (bool)$request->post('include_structure', true);
        $includeData = (bool)$request->post('include_data', true);
        $dropTables = (bool)$request->post('drop_tables', true);
        $useTransaction = (bool)$request->post('use_transaction', true);

        $currentDb = $this->session->getUserInfo()['database'] ?? 'postgres';
        $timestamp = date('Ymd_His');
        $filename = "{$currentDb}_dump_{$timestamp}.sql";

        try {
            $sql = $this->backupManager->exportDatabase(
                $includeStructure,
                $includeData,
                $dropTables,
                $useTransaction
            );

            Response::download($filename, $sql, 'application/sql');
        } catch (Exception $e) {
            $this->session->flash('error', 'Export failed: ' . $e->getMessage());
            Response::redirect('/database/backup?tab=export');
        }
    }

    /**
     * Handle SQL Import (File upload or pasted SQL)
     */
    public function importDatabase(Request $request): void
    {
        $this->initBackupManager();

        if (!CSRF::validate($request->csrfToken())) {
            $this->session->flash('error', 'Invalid CSRF token.');
            Response::redirect('/database/backup?tab=import');
        }

        $sqlContent = '';
        $useTransaction = (bool)$request->post('use_transaction', true);

        // Check file upload first
        if (!empty($_FILES['sql_file']['tmp_name']) && is_uploaded_file($_FILES['sql_file']['tmp_name'])) {
            $fileError = $_FILES['sql_file']['error'];
            if ($fileError !== UPLOAD_ERR_OK) {
                $this->session->flash('error', 'File upload error code: ' . $fileError);
                Response::redirect('/database/backup?tab=import');
            }
            $sqlContent = (string)file_get_contents($_FILES['sql_file']['tmp_name']);
        } else {
            // Check pasted raw SQL
            $sqlContent = (string)$request->post('raw_sql', '');
        }

        if (empty(trim($sqlContent))) {
            $this->session->flash('error', 'Please upload a .sql file or enter SQL script statements.');
            Response::redirect('/database/backup?tab=import');
        }

        $result = $this->backupManager->importSql($sqlContent, $useTransaction);

        if ($result['success']) {
            $this->session->flash('success', $result['message']);
        } else {
            $this->session->flash('error', 'Import failed: ' . $result['error']);
        }

        Response::redirect('/database/backup?tab=import');
    }

    /**
     * Export a single table
     */
    public function exportTable(Request $request): void
    {
        $this->requireAuth();

        $table = (string)$request->get('table', '');
        $schema = (string)$request->get('schema', 'public');
        $format = strtolower((string)$request->get('format', 'sql'));

        if (empty($table)) {
            $this->session->flash('error', 'Table name is required.');
            Response::redirect('/');
        }

        $safeSchema = preg_replace('/[^a-zA-Z0-9_]/', '', $schema);
        $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $fullTable = sprintf('"%s"."%s"', $safeSchema, $safeTable);

        $stmt = $this->pdo->query("SELECT * FROM {$fullTable}");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $timestamp = date('Ymd_His');

        if ($format === 'json') {
            $content = json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            Response::download("{$schema}_{$table}_{$timestamp}.json", $content, 'application/json');
        } elseif ($format === 'csv') {
            $output = fopen('php://memory', 'r+');
            if (!empty($rows)) {
                fputcsv($output, array_keys($rows[0]));
                foreach ($rows as $row) {
                    fputcsv($output, $row);
                }
            }
            rewind($output);
            $content = stream_get_contents($output);
            fclose($output);
            Response::download("{$schema}_{$table}_{$timestamp}.csv", (string)$content, 'text/csv');
        } else {
            // SQL dump
            $sql = "-- PosMyAdmin PostgreSQL Table Dump\n";
            $sql .= "-- Table: {$schema}.{$table}\n";
            $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";

            if (!empty($rows)) {
                $columns = array_keys($rows[0]);
                $quotedCols = array_map(fn($c) => '"' . preg_replace('/[^a-zA-Z0-9_]/', '', $c) . '"', $columns);
                $colsList = implode(', ', $quotedCols);

                foreach ($rows as $row) {
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
                    $sql .= sprintf("INSERT INTO %s (%s) VALUES (%s);\n", $fullTable, $colsList, implode(', ', $vals));
                }
            }

            Response::download("{$schema}_{$table}_{$timestamp}.sql", $sql, 'application/sql');
        }
    }
}
