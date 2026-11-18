<?php

declare(strict_types=1);

namespace PosMyAdmin\Utils;

use PosMyAdmin\Http\Request;
use Throwable;

class Logger
{
    private static ?string $logFile = null;
    private static int $maxFileSize = 10485760; // 10 MB

    public static function init(?string $filePath = null, int $maxFileSize = 10485760): void
    {
        if ($filePath === null) {
            $filePath = dirname(__DIR__, 2) . '/storage/logs/error.log';
        }

        self::$logFile = $filePath;
        self::$maxFileSize = $maxFileSize;

        $dir = dirname(self::$logFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }

    public static function getLogPath(): string
    {
        if (self::$logFile === null) {
            self::init();
        }
        return self::$logFile;
    }

    public static function log(string $level, string $message, array $context = []): void
    {
        $logFile = self::getLogPath();

        // Auto-rotation if file exceeds max size
        if (file_exists($logFile) && filesize($logFile) > self::$maxFileSize) {
            $backup = dirname($logFile) . '/error-' . date('Y-m-d_His') . '.log';
            @rename($logFile, $backup);
        }

        $timestamp = date('Y-m-d H:i:s');
        $ip = Request::getClientIp();
        $cfCountry = !empty($_SERVER['HTTP_CF_IPCOUNTRY']) ? ' (' . $_SERVER['HTTP_CF_IPCOUNTRY'] . ')' : '';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $user = !empty($_SESSION['username']) ? $_SESSION['username'] : 'guest';

        $header = sprintf("[%s] [%s] [%s %s] [IP: %s%s] [User: %s]", $timestamp, strtoupper($level), $method, $uri, $ip, $cfCountry, $user);
        
        $entry = $header . "\n" . "Message: " . $message . "\n";

        if (!empty($context['file']) && !empty($context['line'])) {
            $entry .= "Location: " . $context['file'] . ":" . $context['line'] . "\n";
        }

        if (!empty($context['trace'])) {
            $entry .= "Stack Trace:\n" . $context['trace'] . "\n";
        }

        if (!empty($context['extra']) && is_array($context['extra'])) {
            $entry .= "Extra: " . json_encode($context['extra'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
        }

        $entry .= str_repeat('-', 80) . "\n";

        @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('INFO', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log('WARNING', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('ERROR', $message, $context);
    }

    public static function exception(Throwable $e, string $customMessage = ''): void
    {
        $message = ($customMessage !== '' ? $customMessage . ': ' : '') . $e->getMessage();
        self::log('EXCEPTION', $message, [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'extra' => [
                'type' => get_class($e),
                'code' => $e->getCode(),
            ],
        ]);
    }

    /**
     * Read recent entries from log file
     */
    public static function readLines(int $limit = 200): array
    {
        $logFile = self::getLogPath();
        if (!file_exists($logFile)) {
            return [];
        }

        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return [];
        }

        if (count($lines) > $limit) {
            $lines = array_slice($lines, -$limit);
        }

        return $lines;
    }

    /**
     * Get full log file contents
     */
    public static function getContent(): string
    {
        $logFile = self::getLogPath();
        if (!file_exists($logFile)) {
            return '';
        }
        return (string)file_get_contents($logFile);
    }

    /**
     * Clear the log file
     */
    public static function clear(): bool
    {
        $logFile = self::getLogPath();
        if (file_exists($logFile)) {
            return @file_put_contents($logFile, '') !== false;
        }
        return true;
    }

    /**
     * Get path for authentication and rate-limit log file
     */
    public static function getAuthLogPath(): string
    {
        return dirname(self::getLogPath()) . '/auth.log';
    }

    /**
     * Log authentication events (failed logins, rate-limit lockouts, successes)
     */
    public static function auth(string $event, string $message, array $context = []): void
    {
        $logFile = self::getAuthLogPath();

        // Auto-rotation if auth log exceeds 10MB
        if (file_exists($logFile) && filesize($logFile) > self::$maxFileSize) {
            $backup = dirname($logFile) . '/auth-' . date('Y-m-d_His') . '.log';
            @rename($logFile, $backup);
        }

        $timestamp = date('Y-m-d H:i:s');
        $ip = $context['ip'] ?? Request::getClientIp();
        $cfCountry = !empty($_SERVER['HTTP_CF_IPCOUNTRY']) ? ' (' . $_SERVER['HTTP_CF_IPCOUNTRY'] . ')' : '';
        $user = $context['username'] ?? 'unknown';

        $header = sprintf("[%s] [%s] [IP: %s%s] [User: %s]", $timestamp, strtoupper($event), $ip, $cfCountry, $user);
        $entry = $header . "\n" . "Event: " . $message . "\n";

        if (!empty($_SERVER['HTTP_CF_RAY'])) {
            $entry .= "Cloudflare Ray ID: {$_SERVER['HTTP_CF_RAY']}\n";
        }

        if (!empty($context['host'])) {
            $port = $context['port'] ?? 5432;
            $entry .= "Target: {$context['host']}:{$port}\n";
        }

        if (!empty($context['database'])) {
            $entry .= "Database: {$context['database']}\n";
        }

        if (isset($context['attempts'])) {
            $max = $context['max_attempts'] ?? 5;
            $entry .= "Failed Attempts Count: {$context['attempts']}/{$max}\n";
        }

        if (!empty($context['lockout_remaining'])) {
            $entry .= "Lockout Remaining: {$context['lockout_remaining']} seconds\n";
        }

        $entry .= str_repeat('-', 80) . "\n";

        @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Get auth log contents
     */
    public static function getAuthContent(): string
    {
        $file = self::getAuthLogPath();
        return file_exists($file) ? (string)file_get_contents($file) : '';
    }

    /**
     * Clear auth log
     */
    public static function clearAuthLog(): bool
    {
        $file = self::getAuthLogPath();
        return file_exists($file) ? @file_put_contents($file, '') !== false : true;
    }

    /**
     * Get auth log file size
     */
    public static function getAuthSize(): int
    {
        $file = self::getAuthLogPath();
        return file_exists($file) ? (int)filesize($file) : 0;
    }

    /**
     * Get log file size in bytes
     */
    public static function getSize(): int
    {
        $logFile = self::getLogPath();
        return file_exists($logFile) ? (int)filesize($logFile) : 0;
    }
}
