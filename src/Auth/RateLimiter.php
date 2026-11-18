<?php

declare(strict_types=1);

namespace PosMyAdmin\Auth;

class RateLimiter
{
    private string $storageDir;
    private int $maxAttempts;
    private int $lockoutTime;

    public function __construct(int $maxAttempts = 5, int $lockoutTime = 900)
    {
        $this->maxAttempts = $maxAttempts;
        $this->lockoutTime = $lockoutTime;
        $this->storageDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'posmyadmin_ratelimit';
        
        if (!is_dir($this->storageDir)) {
            @mkdir($this->storageDir, 0700, true);
        }
    }

    private function getFilePath(string $ip): string
    {
        $hash = hash('sha256', $ip . '_login');
        return $this->storageDir . DIRECTORY_SEPARATOR . $hash . '.json';
    }

    public function isLocked(string $ip): bool
    {
        $file = $this->getFilePath($ip);
        if (!file_exists($file)) {
            return false;
        }

        $data = json_decode((string)file_get_contents($file), true);
        if (!is_array($data)) {
            return false;
        }

        if (($data['attempts'] ?? 0) >= $this->maxAttempts) {
            $lockoutRemaining = ($data['last_attempt'] ?? 0) + $this->lockoutTime - time();
            if ($lockoutRemaining > 0) {
                return true;
            }
            // Lockout period expired
            @unlink($file);
        }

        return false;
    }

    public function getLockoutRemaining(string $ip): int
    {
        $file = $this->getFilePath($ip);
        if (!file_exists($file)) {
            return 0;
        }

        $data = json_decode((string)file_get_contents($file), true);
        if (!is_array($data)) {
            return 0;
        }

        $remaining = ($data['last_attempt'] ?? 0) + $this->lockoutTime - time();
        return max(0, $remaining);
    }

    public function hit(string $ip): void
    {
        $file = $this->getFilePath($ip);
        $data = ['attempts' => 1, 'last_attempt' => time()];

        if (file_exists($file)) {
            $existing = json_decode((string)file_get_contents($file), true);
            if (is_array($existing)) {
                $data['attempts'] = ($existing['attempts'] ?? 0) + 1;
            }
        }

        file_put_contents($file, json_encode($data));
    }

    public function getAttempts(string $ip): int
    {
        $file = $this->getFilePath($ip);
        if (!file_exists($file)) {
            return 0;
        }
        $data = json_decode((string)file_get_contents($file), true);
        return is_array($data) ? (int)($data['attempts'] ?? 0) : 0;
    }

    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    public function clear(string $ip): void
    {
        $file = $this->getFilePath($ip);
        if (file_exists($file)) {
            @unlink($file);
        }
    }
}
