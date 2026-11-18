<?php

declare(strict_types=1);

namespace PosMyAdmin\Controllers;

use PosMyAdmin\Auth\CSRF;
use PosMyAdmin\Auth\RateLimiter;
use PosMyAdmin\Http\Request;
use PosMyAdmin\Http\Response;
use PosMyAdmin\Utils\Logger;
use Exception;

class AuthController extends BaseController
{
    private RateLimiter $rateLimiter;

    public function __construct()
    {
        parent::__construct();
        $this->rateLimiter = new RateLimiter(
            $this->config['rate_limit']['max_attempts'] ?? 5,
            $this->config['rate_limit']['lockout_time'] ?? 900
        );
    }

    /**
     * Show Login form
     */
    public function showLogin(Request $request): void
    {
        if ($this->session->isAuthenticated()) {
            Response::redirect('/');
        }

        $ip = $request->ip();
        $isLocked = $this->rateLimiter->isLocked($ip);
        $lockoutRemaining = $this->rateLimiter->getLockoutRemaining($ip);

        $defaults = $this->config['defaults'] ?? [
            'host' => '127.0.0.1',
            'port' => 5432,
            'database' => 'postgres',
            'username' => 'postgres',
        ];

        $this->renderPartial('auth.login', [
            'defaults' => $defaults,
            'isLocked' => $isLocked,
            'lockoutRemaining' => $lockoutRemaining,
        ]);
    }

    /**
     * Handle Login submission
     */
    public function submitLogin(Request $request): void
    {
        $ip = $request->ip();

        // 1. Check Rate Limiting
        if ($this->rateLimiter->isLocked($ip)) {
            $remSeconds = $this->rateLimiter->getLockoutRemaining($ip);
            $mins = ceil($remSeconds / 60);

            Logger::auth('RATE_LIMIT_BLOCKED', 'Connection rejected: Client IP is locked due to excessive failed attempts.', [
                'ip' => $ip,
                'username' => trim((string)$request->post('username', 'unknown')),
                'host' => trim((string)$request->post('host', 'unknown')),
                'lockout_remaining' => $remSeconds,
            ]);

            $this->session->flash('error', "Too many failed login attempts. Client IP is locked for {$mins} more minutes.");
            Response::redirect('/login');
        }

        // 2. Check CSRF Token
        if (!CSRF::validate($request->csrfToken())) {
            $this->session->flash('error', 'Invalid security token (CSRF). Please submit the form again.');
            Response::redirect('/login');
        }

        $host = trim((string)$request->post('host', '127.0.0.1'));
        $port = (int)$request->post('port', 5432);
        $database = trim((string)$request->post('database', 'postgres'));
        $username = trim((string)$request->post('username', 'postgres'));
        $password = (string)$request->post('password', '');
        $sslmode = (string)$request->post('sslmode', 'prefer');

        if (empty($host) || empty($database) || empty($username)) {
            $this->session->flash('error', 'Host, Database, and Username are required.');
            Response::redirect('/login');
        }

        $credentials = [
            'host' => $host,
            'port' => $port,
            'database' => $database,
            'username' => $username,
            'password' => $password,
            'sslmode' => $sslmode,
        ];

        try {
            $this->session->login($credentials);
            $this->rateLimiter->clear($ip);

            Logger::auth('LOGIN_SUCCESS', "Authentication succeeded for user '{$username}'.", [
                'ip' => $ip,
                'username' => $username,
                'host' => $host,
                'port' => $port,
                'database' => $database,
            ]);

            $this->session->flash('success', "Connected successfully to {$host}:{$port} as {$username}.");
            Response::redirect('/');
        } catch (Exception $e) {
            $this->rateLimiter->hit($ip);
            $attempts = $this->rateLimiter->getAttempts($ip);
            $maxAttempts = $this->rateLimiter->getMaxAttempts();
            $isNowLocked = $this->rateLimiter->isLocked($ip);

            Logger::auth('LOGIN_FAILED', 'Failed login attempt: ' . $e->getMessage(), [
                'ip' => $ip,
                'username' => $username,
                'host' => $host,
                'port' => $port,
                'database' => $database,
                'attempts' => $attempts,
                'max_attempts' => $maxAttempts,
            ]);

            if ($isNowLocked) {
                Logger::auth('RATE_LIMIT_LOCKOUT', "Client IP exceeded max allowed attempts ({$maxAttempts}). Lockout activated.", [
                    'ip' => $ip,
                    'username' => $username,
                    'lockout_remaining' => $this->rateLimiter->getLockoutRemaining($ip),
                ]);
            }

            $this->session->flash('error', $e->getMessage());
            Response::redirect('/login');
        }
    }

    /**
     * Switch current connected database
     */
    public function switchDatabase(Request $request): void
    {
        $this->requireAuth();

        if (!CSRF::validate($request->csrfToken())) {
            $this->session->flash('error', 'Invalid CSRF token.');
            Response::redirect('/');
        }

        $newDb = trim((string)$request->post('database', ''));
        if (empty($newDb)) {
            $this->session->flash('error', 'Database name cannot be empty.');
            Response::redirect('/');
        }

        if ($this->session->switchDatabase($newDb)) {
            $this->session->flash('success', "Switched database to '{$newDb}'.");
        } else {
            $this->session->flash('error', "Could not connect to database '{$newDb}'.");
        }

        Response::redirect('/');
    }

    /**
     * Logout
     */
    public function logout(Request $request): void
    {
        $this->session->logout();
        Response::redirect('/login');
    }
}
