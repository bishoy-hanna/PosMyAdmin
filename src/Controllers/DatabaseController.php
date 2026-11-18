<?php

declare(strict_types=1);

namespace PosMyAdmin\Controllers;

use PosMyAdmin\Auth\CSRF;
use PosMyAdmin\Http\Request;
use PosMyAdmin\Http\Response;
use Exception;

class DatabaseController extends BaseController
{
    public function index(Request $request): void
    {
        $this->requireAuth();

        $databases = $this->schemaManager->getDatabases();
        $schemas = $this->schemaManager->getSchemas();

        $this->render('database.index', [
            'databases' => $databases,
            'schemas' => $schemas,
            'pageTitle' => 'Databases & Schemas',
        ]);
    }

    public function create(Request $request): void
    {
        $this->requireAuth();

        if (!CSRF::validate($request->csrfToken())) {
            $this->session->flash('error', 'Invalid CSRF token.');
            Response::redirect('/databases');
        }

        $name = trim((string)$request->post('name', ''));
        $encoding = trim((string)$request->post('encoding', 'UTF8'));

        try {
            $this->schemaManager->createDatabase($name, $encoding);
            $this->session->flash('success', "Database '{$name}' created successfully.");
        } catch (Exception $e) {
            $this->session->flash('error', 'Error creating database: ' . $e->getMessage());
        }

        Response::redirect('/databases');
    }

    public function drop(Request $request): void
    {
        $this->requireAuth();

        if (!CSRF::validate($request->csrfToken())) {
            $this->session->flash('error', 'Invalid CSRF token.');
            Response::redirect('/databases');
        }

        $name = trim((string)$request->post('name', ''));

        try {
            $this->schemaManager->dropDatabase($name);
            $this->session->flash('success', "Database '{$name}' dropped successfully.");
        } catch (Exception $e) {
            $this->session->flash('error', 'Error dropping database: ' . $e->getMessage());
        }

        Response::redirect('/databases');
    }

    public function createSchema(Request $request): void
    {
        $this->requireAuth();

        if (!CSRF::validate($request->csrfToken())) {
            $this->session->flash('error', 'Invalid CSRF token.');
            Response::redirect('/databases');
        }

        $name = trim((string)$request->post('name', ''));

        try {
            $this->schemaManager->createSchema($name);
            $this->session->flash('success', "Schema '{$name}' created successfully.");
        } catch (Exception $e) {
            $this->session->flash('error', 'Error creating schema: ' . $e->getMessage());
        }

        Response::redirect('/databases');
    }

    public function dropSchema(Request $request): void
    {
        $this->requireAuth();

        if (!CSRF::validate($request->csrfToken())) {
            $this->session->flash('error', 'Invalid CSRF token.');
            Response::redirect('/databases');
        }

        $name = trim((string)$request->post('name', ''));
        $cascade = (bool)$request->post('cascade', false);

        try {
            $this->schemaManager->dropSchema($name, $cascade);
            $this->session->flash('success', "Schema '{$name}' dropped successfully.");
        } catch (Exception $e) {
            $this->session->flash('error', 'Error dropping schema: ' . $e->getMessage());
        }

        Response::redirect('/databases');
    }
}
