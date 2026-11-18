<?php

declare(strict_types=1);

return [
    'app_name' => 'PosMyAdmin',
    'app_version' => '1.0.0',
    'debug' => true,
    
    // Security & Session Settings
    'session' => [
        'name' => 'POSMYADMIN_SESSID',
        'lifetime' => 3600, // 1 hour idle timeout
        'cookie_secure' => false, // Set to true if running over HTTPS
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'encryption_cipher' => 'aes-256-gcm',
        // Secret key used to encrypt connection credentials in session
        'secret_key' => getenv('APP_SECRET_KEY') ?: '9f7d3a2c5b8e1f4a6d0c2e4b8a1f7d3c9b5a2e8d1c7f4a0b3e6d9c2f5a8b1e4',
    ],

    // Rate Limiting (Brute force protection on login)
    'rate_limit' => [
        'max_attempts' => 5,
        'lockout_time' => 900, // 15 minutes lockout
    ],

    // Default PostgreSQL Connection settings
    'defaults' => [
        'host' => '127.0.0.1',
        'port' => 5432,
        'database' => 'postgres',
        'username' => 'postgres',
        'sslmode' => 'prefer',
    ],

    // System schemas to hide by default
    'hidden_schemas' => [
        'pg_toast',
        'pg_temp_1',
        'pg_toast_temp_1',
        'information_schema',
    ],

    // Error Logging Configuration
    'logging' => [
        'enabled' => true,
        'path' => dirname(__DIR__) . '/storage/logs/error.log',
        'max_file_size' => 10 * 1024 * 1024, // 10MB auto-rotation limit
    ],

    // Reverse Proxy & Cloudflare Configuration
    'trusted_proxies' => [
        // Set to true if the application runs behind Cloudflare or another reverse proxy
        'behind_cloudflare' => true,
        // Also support X-Forwarded-For and X-Real-IP headers (Nginx/Traefik/HAProxy)
        'trust_x_forwarded' => true,
    ],
];
