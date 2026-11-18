<?php

declare(strict_types=1);

namespace PosMyAdmin\Auth;

use PosMyAdmin\Database\Connection;
use PosMyAdmin\Http\Request;
use Exception;

class SessionManager
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Start secure session with hardened parameters
     */
    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $sessionCfg = $this->config['session'] ?? [];
        $lifetime = $sessionCfg['lifetime'] ?? 3600;

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.gc_maxlifetime', (string)$lifetime);

        session_name($sessionCfg['name'] ?? 'POSMYADMIN_SESSID');

        session_set_cookie_params([
            'lifetime' => 0, // Session cookie (expires when browser closes or idle)
            'path' => '/',
            'domain' => '',
            'secure' => !empty($sessionCfg['cookie_secure']),
            'httponly' => true,
            'samesite' => $sessionCfg['cookie_samesite'] ?? 'Lax',
        ]);

        session_start();

        // Check for session hijacking via fingerprint
        if ($this->isAuthenticated()) {
            if (!$this->verifyFingerprint()) {
                $this->logout();
                return;
            }

            // Check idle timeout
            $lastActivity = $_SESSION['last_activity'] ?? 0;
            if (time() - $lastActivity > $lifetime) {
                $this->logout();
                $this->flash('warning', 'Your session has expired due to inactivity. Please log in again.');
                return;
            }

            $_SESSION['last_activity'] = time();
        }
    }

    /**
     * Verify client fingerprint against session
     */
    private function verifyFingerprint(): bool
    {
        if (!isset($_SESSION['fingerprint'])) {
            return false;
        }

        return hash_equals($_SESSION['fingerprint'], $this->generateFingerprint());
    }

    /**
     * Generate deterministic client fingerprint
     */
    private function generateFingerprint(): string
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'UnknownAgent';
        $ip = Request::getClientIp();
        // Use /24 subnet for IPv4 or first 48 bits of IPv6 to be resilient against minor dynamic IP hops
        $ipSegment = (str_contains($ip, '.')) ? implode('.', array_slice(explode('.', $ip), 0, 3)) : substr($ip, 0, 16);

        return hash('sha256', $userAgent . '|' . $ipSegment . '|' . ($this->config['session']['secret_key'] ?? ''));
    }

    /**
     * Authenticate and start session
     */
    public function login(array $credentials): bool
    {
        // Test connection to PostgreSQL with provided credentials
        Connection::testConnection($credentials);

        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);

        $_SESSION['authenticated'] = true;
        $_SESSION['fingerprint'] = $this->generateFingerprint();
        $_SESSION['last_activity'] = time();
        $_SESSION['login_time'] = time();
        $_SESSION['username'] = $credentials['username'];
        $_SESSION['host'] = $credentials['host'];
        $_SESSION['port'] = (int)$credentials['port'];
        $_SESSION['database'] = $credentials['database'];

        // Encrypt credentials before storing in session
        $_SESSION['encrypted_creds'] = $this->encryptCredentials($credentials);

        return true;
    }

    /**
     * Logout and destroy session
     */
    public function logout(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];

            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }

            session_destroy();
        }
    }

    /**
     * Check if user is authenticated
     */
    public function isAuthenticated(): bool
    {
        return !empty($_SESSION['authenticated']) && !empty($_SESSION['encrypted_creds']);
    }

    /**
     * Get decrypted credentials for active connection
     */
    public function getCredentials(): ?array
    {
        if (!$this->isAuthenticated()) {
            return null;
        }

        return $this->decryptCredentials($_SESSION['encrypted_creds']);
    }

    /**
     * Switch current active database in session
     */
    public function switchDatabase(string $newDatabase): bool
    {
        $creds = $this->getCredentials();
        if (!$creds) {
            return false;
        }

        $creds['database'] = $newDatabase;

        // Test connection to the new database
        try {
            Connection::testConnection($creds);
            $_SESSION['database'] = $newDatabase;
            $_SESSION['encrypted_creds'] = $this->encryptCredentials($creds);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Encrypt sensitive credentials using AES-256-GCM
     */
    private function encryptCredentials(array $credentials): string
    {
        $plaintext = json_encode($credentials, JSON_THROW_ON_ERROR);
        $key = hash('sha256', $this->config['session']['secret_key'] ?? 'default-secret-key-salt', true);
        $iv = random_bytes(12); // GCM standard IV size

        $ciphertext = openssl_encrypt(
            $plaintext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        return base64_encode($iv . $tag . $ciphertext);
    }

    /**
     * Decrypt credentials
     */
    private function decryptCredentials(string $encrypted): ?array
    {
        try {
            $data = base64_decode($encrypted, true);
            if ($data === false || strlen($data) < 28) {
                return null;
            }

            $iv = substr($data, 0, 12);
            $tag = substr($data, 12, 16);
            $ciphertext = substr($data, 28);
            $key = hash('sha256', $this->config['session']['secret_key'] ?? 'default-secret-key-salt', true);

            $decrypted = openssl_decrypt(
                $ciphertext,
                'aes-256-gcm',
                $key,
                OPENSSL_RAW_DATA,
                $iv,
                $tag
            );

            if ($decrypted === false) {
                return null;
            }

            return json_decode($decrypted, true, 512, JSON_THROW_ON_ERROR);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Set a flash message
     */
    public function flash(string $type, string $message): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['_flashes'][$type][] = $message;
        }
    }

    /**
     * Get and clear all flash messages
     */
    public function getFlashes(): array
    {
        if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['_flashes'])) {
            $flashes = $_SESSION['_flashes'];
            unset($_SESSION['_flashes']);
            return $flashes;
        }

        return [];
    }

    /**
     * Get active session user info
     */
    public function getUserInfo(): array
    {
        return [
            'username' => $_SESSION['username'] ?? 'Anonymous',
            'host' => $_SESSION['host'] ?? 'localhost',
            'port' => $_SESSION['port'] ?? 5432,
            'database' => $_SESSION['database'] ?? '',
            'login_time' => $_SESSION['login_time'] ?? time(),
        ];
    }
}
