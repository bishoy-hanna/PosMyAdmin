<?php

declare(strict_types=1);

namespace PosMyAdmin\Auth;

class CSRF
{
    private const SESSION_KEY = '_csrf_tokens';

    /**
     * Generate or get active CSRF token
     */
    public static function token(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return '';
        }

        if (empty($_SESSION[self::SESSION_KEY]) || !is_array($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = [];
        }

        // Keep at most 10 active tokens to prevent exhaustion
        if (count($_SESSION[self::SESSION_KEY]) > 10) {
            array_shift($_SESSION[self::SESSION_KEY]);
        }

        $token = bin2hex(random_bytes(32));
        $_SESSION[self::SESSION_KEY][$token] = time();

        return $token;
    }

    /**
     * Get the latest current token or generate a new one
     */
    public static function current(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return '';
        }

        if (!empty($_SESSION[self::SESSION_KEY]) && is_array($_SESSION[self::SESSION_KEY])) {
            $tokens = array_keys($_SESSION[self::SESSION_KEY]);
            return end($tokens) ?: self::token();
        }

        return self::token();
    }

    /**
     * Validate CSRF token from POST/header
     */
    public static function validate(?string $token): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE || empty($token)) {
            return false;
        }

        if (!empty($_SESSION[self::SESSION_KEY][$token])) {
            // Token is single-use or time-bounded (valid for 2 hours)
            $created = $_SESSION[self::SESSION_KEY][$token];
            unset($_SESSION[self::SESSION_KEY][$token]);

            return (time() - $created) < 7200;
        }

        return false;
    }

    /**
     * Output a hidden HTML input field
     */
    public static function field(): string
    {
        $token = htmlspecialchars(self::current(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="_csrf_token" value="' . $token . '">';
    }
}
