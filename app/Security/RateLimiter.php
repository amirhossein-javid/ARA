<?php

declare(strict_types=1);

namespace Ara\Security;

use RuntimeException;

final class RateLimiter
{
    public function __construct(
        private readonly string $directory,
        private readonly string $applicationKey,
        private readonly int $maximumAttempts,
        private readonly int $windowSeconds
    ) {
    }

    public function consume(string $identity): ?int
    {
        $this->assertStorageIsAvailable();
        $key = hash_hmac('sha256', $identity, $this->applicationKey);
        $path = $this->directory . DIRECTORY_SEPARATOR . substr($key, 0, 2) . '.json';
        $handle = fopen($path, 'c+');

        if ($handle === false) {
            throw new RuntimeException('Rate-limit storage could not be opened.');
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Rate-limit storage could not be locked.');
            }

            $now = time();
            rewind($handle);
            $stored = stream_get_contents($handle);
            $states = is_string($stored) && $stored !== '' ? json_decode($stored, true) : [];
            $states = is_array($states) ? $states : [];

            foreach ($states as $storedKey => $storedState) {
                $storedStart = is_array($storedState) && isset($storedState['started_at'])
                    ? (int) $storedState['started_at']
                    : 0;

                if ($storedStart <= 0 || $storedStart > $now || ($now - $storedStart) >= $this->windowSeconds) {
                    unset($states[$storedKey]);
                }
            }

            $state = $states[$key] ?? null;
            $startedAt = is_array($state) && isset($state['started_at']) ? (int) $state['started_at'] : $now;
            $attempts = is_array($state) && isset($state['attempts']) ? (int) $state['attempts'] : 0;

            if ($startedAt > $now || ($now - $startedAt) >= $this->windowSeconds) {
                $startedAt = $now;
                $attempts = 0;
            }

            $retryAfter = null;

            if ($attempts >= $this->maximumAttempts) {
                $retryAfter = max(1, $this->windowSeconds - ($now - $startedAt));
            } else {
                $attempts++;
            }

            $states[$key] = ['started_at' => $startedAt, 'attempts' => $attempts];

            if (count($states) > 500) {
                uasort($states, static fn (array $left, array $right): int =>
                    ((int) ($right['started_at'] ?? 0)) <=> ((int) ($left['started_at'] ?? 0))
                );
                $states = array_slice($states, 0, 500, true);
            }

            $encoded = json_encode(
                $states,
                JSON_THROW_ON_ERROR
            );
            rewind($handle);
            ftruncate($handle, 0);

            if (fwrite($handle, $encoded) === false) {
                throw new RuntimeException('Rate-limit storage could not be written.');
            }

            fflush($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }

        return $retryAfter;
    }

    private function assertStorageIsAvailable(): void
    {
        if (!is_dir($this->directory) || !is_writable($this->directory)) {
            throw new RuntimeException('Rate-limit storage is unavailable.');
        }
    }

}
