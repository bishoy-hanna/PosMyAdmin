<?php

declare(strict_types=1);

namespace PosMyAdmin\Controllers;

use PosMyAdmin\Auth\SessionManager;
use PosMyAdmin\Auth\CSRF;
use PosMyAdmin\Database\Connection;
use PosMyAdmin\Database\SchemaManager;
use PosMyAdmin\Http\Response;
use PDO;
use Exception;

abstract class BaseController
{
    protected array $config;
    protected SessionManager $session;
    protected ?PDO $pdo = null;
    protected ?SchemaManager $schemaManager = null;

    public function __construct()
    {
        $this->config = require dirname(__DIR__, 2) . '/config/app.php';
        $this->session = new SessionManager($this->config);
        $this->session->start();
    }

    /**
     * Require active authenticated session
     */
    protected function requireAuth(): void
    {
        if (!$this->session->isAuthenticated()) {
            Response::redirect('/login');
        }

        try {
            $creds = $this->session->getCredentials();
            $this->pdo = Connection::get($creds);
            $this->schemaManager = new SchemaManager($this->pdo, $this->config['hidden_schemas'] ?? []);
        } catch (Exception $e) {
            $this->session->logout();
            $this->session->flash('error', 'Database connection lost: ' . $e->getMessage());
            Response::redirect('/login');
        }
    }

    /**
     * Render a view wrapped in the main layout
     */
    protected function render(string $viewPath, array $data = [], string $layout = 'main'): void
    {
        Response::applySecurityHeaders();

        // Extract passed data variables
        extract($data);

        // Common layout data
        $config = $this->config;
        $user = $this->session->getUserInfo();
        $flashes = $this->session->getFlashes();
        $csrfToken = CSRF::current();
        
        // Navigation tree
        $navTree = [];
        $databases = [];
        $currentDb = $user['database'] ?? '';
        $currentSchema = $_GET['schema'] ?? 'public';
        $currentTable = $_GET['table'] ?? '';
        $pageTitle = $data['pageTitle'] ?? 'PosMyAdmin';

        if ($this->schemaManager) {
            try {
                $navTree = $this->schemaManager->getNavigationTree();
                $databases = $this->schemaManager->getDatabases();
            } catch (Exception $e) {
                // If tree fails, keep empty
            }
        }

        // Render view in isolated scope to prevent variable collision with layout
        $renderIsolated = static function (string $file, array $vars): string {
            extract($vars);
            ob_start();
            if (file_exists($file)) {
                require $file;
            } else {
                echo "View not found: " . htmlspecialchars($file);
            }
            return ob_get_clean();
        };

        // Pass common view context variables to all views
        $viewData = array_merge([
            'config' => $config,
            'user' => $user,
            'flashes' => $flashes,
            'csrfToken' => $csrfToken,
            'currentDb' => $currentDb,
            'currentSchema' => $currentSchema,
            'currentTable' => $currentTable,
            'pageTitle' => $pageTitle,
        ], $data);

        $viewFile = dirname(__DIR__) . '/Views/' . str_replace('.', '/', $viewPath) . '.php';
        $content = $renderIsolated($viewFile, $viewData);

        // Render layout
        $layoutFile = dirname(__DIR__) . '/Views/layouts/' . $layout . '.php';
        if (file_exists($layoutFile)) {
            require $layoutFile;
        } else {
            echo $content;
        }
        exit;
    }

    /**
     * Render standalone view without layout (e.g. for login or modals)
     */
    protected function renderPartial(string $viewPath, array $data = []): void
    {
        Response::applySecurityHeaders();
        extract($data);
        $config = $this->config;
        $csrfToken = CSRF::current();
        $flashes = $this->session->getFlashes();

        $viewFile = dirname(__DIR__) . '/Views/' . str_replace('.', '/', $viewPath) . '.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        }
        exit;
    }
}
