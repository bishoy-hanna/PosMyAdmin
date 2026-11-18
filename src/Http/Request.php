<?php

declare(strict_types=1);

namespace PosMyAdmin\Http;

class Request
{
    private array $get;
    private array $post;
    private array $server;
    private ?array $jsonBody = null;

    public function __construct(array $get = [], array $post = [], array $server = [])
    {
        $this->get = $get ?: $_GET;
        $this->post = $post ?: $_POST;
        $this->server = $server ?: $_SERVER;
    }

    public static function createFromGlobals(): self
    {
        return new self($_GET, $_POST, $_SERVER);
    }

    public function method(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    public function uri(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        $pos = strpos($uri, '?');
        if ($pos !== false) {
            $uri = substr($uri, 0, $pos);
        }
        return '/' . trim($uri, '/');
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->get[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->get, $this->post, $this->json());
    }

    public function json(): array
    {
        if ($this->jsonBody !== null) {
            return $this->jsonBody;
        }

        $input = file_get_contents('php://input');
        if (!empty($input)) {
            $decoded = json_decode($input, true);
            if (is_array($decoded)) {
                $this->jsonBody = $decoded;
                return $this->jsonBody;
            }
        }

        $this->jsonBody = [];
        return $this->jsonBody;
    }

    /**
     * Resolves the real client IP address, supporting Cloudflare and reverse proxies with strict validation.
     */
    public static function getClientIp(?array $server = null): string
    {
        $server ??= $_SERVER;

        // 1. Cloudflare CF-Connecting-IP
        if (!empty($server['HTTP_CF_CONNECTING_IP'])) {
            $cfIp = trim($server['HTTP_CF_CONNECTING_IP']);
            if (filter_var($cfIp, FILTER_VALIDATE_IP)) {
                return $cfIp;
            }
        }

        // 2. Cloudflare Enterprise True-Client-IP
        if (!empty($server['HTTP_TRUE_CLIENT_IP'])) {
            $trueIp = trim($server['HTTP_TRUE_CLIENT_IP']);
            if (filter_var($trueIp, FILTER_VALIDATE_IP)) {
                return $trueIp;
            }
        }

        // 3. Standard X-Forwarded-For (handles multi-hop proxies: "client, proxy1, proxy2")
        if (!empty($server['HTTP_X_FORWARDED_FOR'])) {
            $parts = explode(',', $server['HTTP_X_FORWARDED_FOR']);
            foreach ($parts as $part) {
                $candidate = trim($part);
                if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                    return $candidate;
                }
            }
        }

        // 4. X-Real-IP (common in Nginx / Traefik reverse proxies)
        if (!empty($server['HTTP_X_REAL_IP'])) {
            $realIp = trim($server['HTTP_X_REAL_IP']);
            if (filter_var($realIp, FILTER_VALIDATE_IP)) {
                return $realIp;
            }
        }

        // 5. Fallback to direct REMOTE_ADDR
        $remoteAddr = $server['REMOTE_ADDR'] ?? '127.0.0.1';
        return filter_var($remoteAddr, FILTER_VALIDATE_IP) ? $remoteAddr : '127.0.0.1';
    }

    public function ip(): string
    {
        return self::getClientIp($this->server);
    }

    /**
     * Get visitor's country code if behind Cloudflare (e.g. 'EG', 'US', 'DE')
     */
    public function country(): ?string
    {
        return !empty($this->server['HTTP_CF_IPCOUNTRY']) ? strtoupper(trim($this->server['HTTP_CF_IPCOUNTRY'])) : null;
    }

    /**
     * Get Cloudflare Ray ID if available
     */
    public function cfRay(): ?string
    {
        return $this->server['HTTP_CF_RAY'] ?? null;
    }

    /**
     * Check if current request passed through Cloudflare
     */
    public static function isCloudflare(?array $server = null): bool
    {
        $server ??= $_SERVER;
        return !empty($server['HTTP_CF_CONNECTING_IP']) || !empty($server['HTTP_CF_RAY']);
    }

    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    public function isAjax(): bool
    {
        return (!empty($this->server['HTTP_X_REQUESTED_WITH']) &&
            strtolower($this->server['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
            (isset($this->server['HTTP_ACCEPT']) && str_contains($this->server['HTTP_ACCEPT'], 'application/json'));
    }

    public function csrfToken(): ?string
    {
        return $this->post('_csrf_token') ?? $this->server['HTTP_X_CSRF_TOKEN'] ?? null;
    }
}
