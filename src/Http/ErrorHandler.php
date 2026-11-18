<?php

declare(strict_types=1);

namespace PosMyAdmin\Http;

use PosMyAdmin\Utils\Logger;
use Throwable;
use ErrorException;

class ErrorHandler
{
    private static bool $debug = true;

    /**
     * Register global error and exception handlers
     */
    public static function register(bool $debug = true): void
    {
        self::$debug = $debug;

        // Ensure error reporting is set
        error_reporting(E_ALL);

        // Convert standard PHP errors to ErrorException or log them
        set_error_handler([self::class, 'handleError']);

        // Handle uncaught exceptions
        set_exception_handler([self::class, 'handleException']);

        // Handle fatal shutdown errors
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    /**
     * Handle standard PHP errors (Warnings, Notices, etc.)
     */
    public static function handleError(int $severity, string $message, string $file, int $line): bool
    {
        // Don't log if error was suppressed with @
        if (!(error_reporting() & $severity)) {
            return false;
        }

        $level = match ($severity) {
            E_USER_ERROR, E_RECOVERABLE_ERROR => 'ERROR',
            E_WARNING, E_USER_WARNING => 'WARNING',
            E_NOTICE, E_USER_NOTICE, E_DEPRECATED, E_USER_DEPRECATED => 'NOTICE',
            default => 'WARNING',
        };

        Logger::log($level, $message, [
            'file' => $file,
            'line' => $line,
        ]);

        // If error is recoverable or user error, throw exception
        if ($severity === E_USER_ERROR || $severity === E_RECOVERABLE_ERROR) {
            throw new ErrorException($message, 0, $severity, $file, $line);
        }

        // Return false to allow standard PHP handler if debug is enabled
        return false;
    }

    /**
     * Handle uncaught exceptions
     */
    public static function handleException(Throwable $e): void
    {
        Logger::exception($e, 'Uncaught Exception');

        if (php_sapi_name() === 'cli') {
            echo "\n[ERROR] " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
            return;
        }

        Response::applySecurityHeaders();

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

        if ($isAjax) {
            Response::json([
                'success' => false,
                'error' => self::$debug ? $e->getMessage() : 'An unexpected server error occurred.',
                'file' => self::$debug ? $e->getFile() . ':' . $e->getLine() : null,
            ], 500);
            return;
        }

        http_response_code(500);
        $message = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        $file = htmlspecialchars($e->getFile(), ENT_QUOTES, 'UTF-8');
        $line = $e->getLine();
        $trace = htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8');
        $debug = self::$debug;

        echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Error - PosMyAdmin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-3xl bg-slate-800 border border-red-500/30 rounded-2xl p-6 sm:p-8 shadow-2xl space-y-4">
        <div class="flex items-center gap-3 text-red-400">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div>
                <h1 class="text-xl font-bold text-white">Application Exception</h1>
                <p class="text-xs text-slate-400">This event has been recorded in <code class="text-sky-400">storage/logs/error.log</code></p>
            </div>
        </div>

        <div class="p-4 bg-slate-900 border border-slate-700 rounded-xl text-xs font-mono text-red-300 break-words">
            {$message}
        </div>

HTML;

        if ($debug) {
            echo <<<HTML
        <div class="space-y-1 text-xs">
            <div class="text-slate-400 font-mono">Location: <span class="text-white">{$file}:{$line}</span></div>
            <details class="mt-3">
                <summary class="cursor-pointer text-sky-400 font-medium hover:underline">View Stack Trace</summary>
                <pre class="mt-2 p-3 bg-slate-950 rounded-lg text-[11px] font-mono text-slate-400 overflow-x-auto whitespace-pre-wrap leading-relaxed max-h-64">{$trace}</pre>
            </details>
        </div>
HTML;
        }

        echo <<<HTML
        <div class="pt-4 border-t border-slate-700/60 flex items-center justify-between">
            <a href="/" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 rounded-lg text-xs font-semibold text-white">Back to Dashboard</a>
            <a href="/logs" class="px-4 py-2 bg-red-600/80 hover:bg-red-600 rounded-lg text-xs font-semibold text-white">View Error Log</a>
        </div>
    </div>
</body>
</html>
HTML;
        exit;
    }

    /**
     * Handle fatal PHP shutdown errors
     */
    public static function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            Logger::log('FATAL', $error['message'], [
                'file' => $error['file'],
                'line' => $error['line'],
            ]);
        }
    }
}
