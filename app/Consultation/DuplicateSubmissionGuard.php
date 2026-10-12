<?php

declare(strict_types=1);

namespace Ara\Consultation;

use RuntimeException;

final class DuplicateSubmissionGuard
{
    public function __construct(
        private readonly string $directory,
        private readonly string $applicationKey
    ) {
    }

    /** @param array{name: string, contact: string, service: string, message: string} $request */
    public function synchronized(array $request, callable $callback): mixed
    {
        if (!is_dir($this->directory) || !is_writable($this->directory)) {
            throw new RuntimeException('Submission-lock storage is unavailable.');
        }

        $canonical = implode("\0", [
            $request['name'],
            $request['contact'],
            $request['service'],
            $request['message'],
        ]);
        $key = hash_hmac('sha256', $canonical, $this->applicationKey);
        $path = $this->directory . DIRECTORY_SEPARATOR . substr($key, 0, 2) . '.lock';
        $handle = fopen($path, 'c+');

        if ($handle === false) {
            throw new RuntimeException('Submission lock could not be opened.');
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Submission lock could not be acquired.');
            }

            return $callback();
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
