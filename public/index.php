<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use PosMyAdmin\Http\Request;
use PosMyAdmin\Http\Router;
use PosMyAdmin\Http\Response;
use PosMyAdmin\Http\ErrorHandler;
use PosMyAdmin\Utils\Logger;
use PosMyAdmin\Utils\RequirementsChecker;
use PosMyAdmin\Controllers\AuthController;
use PosMyAdmin\Controllers\DashboardController;
use PosMyAdmin\Controllers\DatabaseController;
use PosMyAdmin\Controllers\TableController;
use PosMyAdmin\Controllers\SqlController;
use PosMyAdmin\Controllers\ProcessController;
use PosMyAdmin\Controllers\ExportController;
use PosMyAdmin\Controllers\LogController;

// Load App Config
$config = require dirname(__DIR__) . '/config/app.php';

// Initialize Global Error Logger & Exception Handlers
Logger::init(
    $config['logging']['path'] ?? null,
    $config['logging']['max_file_size'] ?? 10485760
);
ErrorHandler::register((bool)($config['debug'] ?? true));

// Check mandatory system requirements (pdo_pgsql, openssl, json, PHP >= 8.1)
// If any extension is missing or damaged, execution is halted immediately with detailed diagnostics.
RequirementsChecker::checkOrHalt();

// Apply baseline security headers
Response::applySecurityHeaders();

$request = Request::createFromGlobals();
$router = new Router();

// Authentication Routes
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'submitLogin']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->post('/database/switch', [AuthController::class, 'switchDatabase']);

// Dashboard Routes
$router->get('/', [DashboardController::class, 'index']);
$router->get('/dashboard', [DashboardController::class, 'index']);

// Database & Schema Routes
$router->get('/databases', [DatabaseController::class, 'index']);
$router->post('/database/create', [DatabaseController::class, 'create']);
$router->post('/database/drop', [DatabaseController::class, 'drop']);
$router->post('/schema/create', [DatabaseController::class, 'createSchema']);
$router->post('/schema/drop', [DatabaseController::class, 'dropSchema']);

// Table Management Routes
$router->get('/table/browse', [TableController::class, 'browse']);
$router->get('/table/structure', [TableController::class, 'structure']);
$router->get('/table/insert', [TableController::class, 'showInsert']);
$router->post('/table/insert', [TableController::class, 'insert']);
$router->post('/table/update', [TableController::class, 'update']);
$router->post('/table/delete', [TableController::class, 'delete']);
$router->post('/table/truncate', [TableController::class, 'truncate']);
$router->post('/table/drop', [TableController::class, 'drop']);
$router->get('/table/create', [TableController::class, 'showCreate']);
$router->post('/table/create', [TableController::class, 'create']);

// SQL Console Routes
$router->get('/sql', [SqlController::class, 'index']);
$router->post('/sql/execute', [SqlController::class, 'execute']);
$router->post('/sql/export', [SqlController::class, 'export']);

// Server Activity & Process Monitor Routes
$router->get('/processes', [ProcessController::class, 'index']);
$router->post('/processes/terminate', [ProcessController::class, 'terminate']);

// Export & Backup Routes
$router->get('/export/table', [ExportController::class, 'exportTable']);
$router->get('/database/backup', [ExportController::class, 'backupView']);
$router->post('/database/export', [ExportController::class, 'exportDatabase']);
$router->post('/database/import', [ExportController::class, 'importDatabase']);

// Error Logs Routes
$router->get('/logs', [LogController::class, 'index']);
$router->get('/logs/download', [LogController::class, 'download']);
$router->post('/logs/clear', [LogController::class, 'clear']);

// Dispatch request
$router->dispatch($request);
