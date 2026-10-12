<?php

declare(strict_types=1);

namespace Ara\Http;

use RuntimeException;

final class HttpException extends RuntimeException
{
    /**
     * @param array<string, string> $errors
     * @param array<string, string> $headers
     */
    public function __construct(
        public readonly int $status,
        public readonly string $errorCode,
        string $message,
        public readonly array $errors = [],
        public readonly array $headers = []
    ) {
        parent::__construct($message);
    }
}
