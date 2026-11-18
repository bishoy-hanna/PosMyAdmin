<?php

declare(strict_types=1);

namespace PosMyAdmin\Utils;

class RequirementsChecker
{
    /**
     * Checks all mandatory system extensions and dependencies.
     * If any required extension is missing or damaged, it halts execution
     * and renders a detailed diagnostics page with step-by-step resolution instructions.
     */
    public static function checkOrHalt(): void
    {
        $failures = [];
        $passed = [];

        // 1. PHP Version Check (>= 8.1.0)
        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            $failures[] = [
                'name' => 'PHP Version',
                'extension' => 'PHP Core',
                'status' => 'FAILED',
                'current' => 'PHP ' . PHP_VERSION,
                'required' => 'PHP >= 8.1.0',
                'description' => 'PosMyAdmin leverages modern PHP 8.1+ features (typed properties, readonly classes, match expressions, and fiber-safe PDO).',
                'fix' => 'Please upgrade your PHP runtime environment to PHP 8.1, 8.2, 8.3 or newer.'
            ];
        } else {
            $passed[] = [
                'name' => 'PHP Version',
                'extension' => 'PHP Core',
                'version' => PHP_VERSION,
            ];
        }

        // 2. PostgreSQL PDO Driver (pdo_pgsql)
        $pdoLoaded = extension_loaded('pdo');
        $pdoPgsqlLoaded = extension_loaded('pdo_pgsql');
        $drivers = class_exists(\PDO::class) ? \PDO::getAvailableDrivers() : [];
        $hasPgsqlDriver = in_array('pgsql', $drivers, true);

        if (!$pdoLoaded || !$pdoPgsqlLoaded || !$hasPgsqlDriver) {
            $reason = 'Extension not loaded in PHP';
            if ($pdoPgsqlLoaded && !$hasPgsqlDriver) {
                $reason = 'pdo_pgsql is loaded, but libpq client driver is missing or corrupted';
            }

            $failures[] = [
                'name' => 'PostgreSQL PDO Driver',
                'extension' => 'pdo_pgsql',
                'status' => 'FAILED',
                'current' => $reason,
                'required' => 'extension=pdo_pgsql',
                'description' => 'Essential driver required to establish database connections, execute queries, and manage transactions on PostgreSQL servers.',
                'fix' => "1. Open your active php.ini configuration file:\n   " . (php_ini_loaded_file() ?: 'php.ini') . "\n2. Search for the lines:\n   ;extension=pdo_pgsql\n   ;extension=pgsql\n3. Remove the semicolon (;) from the beginning of both lines to enable them:\n   extension=pdo_pgsql\n   extension=pgsql\n4. Restart your web server (e.g. Apache, Nginx, or PHP CLI)."
            ];
        } else {
            $passed[] = [
                'name' => 'PostgreSQL PDO Driver',
                'extension' => 'pdo_pgsql',
                'version' => phpversion('pdo_pgsql') ?: 'Active',
            ];
        }

        // 3. OpenSSL Cryptography (openssl)
        $opensslLoaded = extension_loaded('openssl');
        $hasEncrypt = function_exists('openssl_encrypt');

        if (!$opensslLoaded || !$hasEncrypt) {
            $failures[] = [
                'name' => 'OpenSSL Cryptography Extension',
                'extension' => 'openssl',
                'status' => 'FAILED',
                'current' => 'Not Loaded / Missing',
                'required' => 'extension=openssl',
                'description' => 'Mandatory for AES-256-GCM symmetric encryption of PostgreSQL credentials in sessions, secure random token generation, and CSRF protection.',
                'fix' => "1. Open your active php.ini configuration file:\n   " . (php_ini_loaded_file() ?: 'php.ini') . "\n2. Locate the line:\n   ;extension=openssl\n3. Remove the leading semicolon (;) to enable it:\n   extension=openssl\n4. Ensure openssl DLL/shared library exists in your PHP ext/ folder.\n5. Restart your web server."
            ];
        } else {
            $passed[] = [
                'name' => 'OpenSSL Cryptography Extension',
                'extension' => 'openssl',
                'version' => OPENSSL_VERSION_TEXT ?? 'Active',
            ];
        }

        // 4. JSON Serialization Extension (json)
        $jsonLoaded = extension_loaded('json') && function_exists('json_encode') && function_exists('json_decode');

        if (!$jsonLoaded) {
            $failures[] = [
                'name' => 'JSON Parser & Encoder',
                'extension' => 'json',
                'status' => 'FAILED',
                'current' => 'Not Loaded / Missing',
                'required' => 'extension=json',
                'description' => 'Required for JSON query exports, AJAX API responses, rate-limiter state serialization, and configuration handling.',
                'fix' => "1. Open your active php.ini configuration file:\n   " . (php_ini_loaded_file() ?: 'php.ini') . "\n2. Search for: ;extension=json\n3. Remove the semicolon (;) to enable it:\n   extension=json\n4. Restart your web server."
            ];
        } else {
            $passed[] = [
                'name' => 'JSON Parser & Encoder',
                'extension' => 'json',
                'version' => phpversion('json') ?: 'Built-in',
            ];
        }

        // If any requirement failed, render the diagnostics page and halt execution
        if (!empty($failures)) {
            try {
                Logger::log('CRITICAL', 'System requirements pre-flight check failed: ' . count($failures) . ' missing extension(s).', [
                    'failures' => array_column($failures, 'name'),
                ]);
            } catch (\Throwable) {
                // If logger cannot write, proceed to render
            }

            self::renderDiagnosticsPage($failures, $passed);
            exit(1);
        }
    }

    /**
     * Render modern, high-contrast system diagnostics error page
     */
    private static function renderDiagnosticsPage(array $failures, array $passed): void
    {
        if (!headers_sent()) {
            http_response_code(503);
            header('Content-Type: text/html; charset=UTF-8');
            header('Retry-After: 60');
        }

        $iniPath = php_ini_loaded_file() ?: 'Not detected / None loaded';
        $sapi = php_sapi_name();
        $os = PHP_OS . ' (' . php_uname('m') . ')';
        ?>
        <!DOCTYPE html>
        <html lang="en" class="dark">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>System Requirements Error - PosMyAdmin</title>
            <script src="https://cdn.tailwindcss.com"></script>
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
            <style>
                body { font-family: 'Plus Jakarta Sans', sans-serif; }
                code, pre { font-family: 'JetBrains Mono', monospace; }
            </style>
        </head>
        <body class="bg-slate-950 text-slate-100 min-h-screen flex items-center justify-center p-4 md:p-8">
            <div class="max-w-4xl w-full space-y-6">
                <!-- Header Card -->
                <div class="bg-slate-900 border border-red-500/30 rounded-2xl p-6 md:p-8 shadow-2xl relative overflow-hidden">
                    <div class="absolute -right-12 -top-12 w-48 h-48 bg-red-500/10 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="flex items-start gap-5">
                        <div class="w-14 h-14 rounded-2xl bg-red-500/20 border border-red-500/40 flex items-center justify-center text-red-400 shrink-0 shadow-lg">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div class="space-y-2">
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-500/20 text-red-400 border border-red-500/30">Halted Pre-Flight Check</span>
                                <span class="text-xs text-slate-400 font-mono">HTTP 503 Service Unavailable</span>
                            </div>
                            <h1 class="text-2xl md:text-3xl font-extrabold text-white tracking-tight">System Requirements Error</h1>
                            <p class="text-sm text-slate-400 leading-relaxed">
                                PosMyAdmin cannot start or display the login page because one or more mandatory PHP extensions are missing or disabled. To protect database security and prevent runtime crashes, execution has been paused until the requirements below are satisfied.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Environment Details Strip -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-slate-900/60 border border-slate-800 rounded-xl p-4 text-xs font-mono">
                    <div>
                        <span class="text-slate-500 block text-[10px] uppercase tracking-wider">PHP Version</span>
                        <span class="text-slate-200 font-semibold"><?= htmlspecialchars(PHP_VERSION) ?></span>
                    </div>
                    <div>
                        <span class="text-slate-500 block text-[10px] uppercase tracking-wider">Server SAPI</span>
                        <span class="text-slate-200 font-semibold"><?= htmlspecialchars($sapi) ?></span>
                    </div>
                    <div class="sm:col-span-2">
                        <span class="text-slate-500 block text-[10px] uppercase tracking-wider">Active php.ini</span>
                        <span class="text-amber-400 font-semibold truncate block" title="<?= htmlspecialchars($iniPath) ?>"><?= htmlspecialchars($iniPath) ?></span>
                    </div>
                </div>

                <!-- Failed Requirements List -->
                <div class="space-y-4">
                    <h2 class="text-base font-bold text-white flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-red-500 animate-pulse"></span>
                        Unsatisfied Requirements (<?= count($failures) ?>)
                    </h2>

                    <?php foreach ($failures as $f): ?>
                        <div class="bg-slate-900 border border-red-500/40 rounded-xl p-5 md:p-6 space-y-4 shadow-lg">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-800 pb-3">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-base font-bold text-white"><?= htmlspecialchars($f['name']) ?></h3>
                                        <code class="px-2 py-0.5 rounded text-xs bg-slate-800 text-slate-300 font-mono"><?= htmlspecialchars($f['extension']) ?></code>
                                    </div>
                                    <p class="text-xs text-slate-400 mt-1"><?= htmlspecialchars($f['description']) ?></p>
                                </div>
                                <div class="shrink-0 flex items-center gap-2">
                                    <span class="px-3 py-1 rounded-lg text-xs font-bold bg-red-500/20 text-red-400 border border-red-500/40 uppercase">
                                        <?= htmlspecialchars($f['status']) ?>
                                    </span>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                                <div class="bg-slate-950/70 border border-slate-800/80 rounded-lg p-3">
                                    <span class="text-slate-500 block font-semibold mb-1">Current State:</span>
                                    <span class="text-red-400 font-mono font-medium"><?= htmlspecialchars($f['current']) ?></span>
                                </div>
                                <div class="bg-slate-950/70 border border-slate-800/80 rounded-lg p-3">
                                    <span class="text-slate-500 block font-semibold mb-1">Required Directive:</span>
                                    <span class="text-emerald-400 font-mono font-medium"><?= htmlspecialchars($f['required']) ?></span>
                                </div>
                            </div>

                            <div class="bg-slate-950 border border-slate-800 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-semibold text-slate-300 uppercase tracking-wider">How to Fix in php.ini:</span>
                                    <span class="text-[11px] text-slate-500">Step-by-step resolution</span>
                                </div>
                                <pre class="text-xs text-slate-300 whitespace-pre-wrap leading-relaxed overflow-x-auto"><?= htmlspecialchars($f['fix']) ?></pre>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Passed Extensions List (Collapsed / Summary) -->
                <?php if (!empty($passed)): ?>
                    <div class="bg-slate-900/60 border border-slate-800/80 rounded-xl p-4">
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            Satisfied Requirements (<?= count($passed) ?>)
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                            <?php foreach ($passed as $p): ?>
                                <div class="flex items-center justify-between bg-slate-950/50 border border-slate-800/50 rounded-lg px-3 py-2 text-xs">
                                    <span class="text-slate-300 font-medium"><?= htmlspecialchars($p['name']) ?></span>
                                    <span class="text-emerald-400 font-mono text-[11px]">✓ Passed</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Refresh Button Footer -->
                <div class="flex items-center justify-between pt-2">
                    <p class="text-xs text-slate-500">After modifying <code class="text-slate-400">php.ini</code> and restarting your web server, refresh this page.</p>
                    <button onclick="window.location.reload();" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs shadow-lg transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Re-check System
                    </button>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
}
