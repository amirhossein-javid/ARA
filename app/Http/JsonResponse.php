<?php

declare(strict_types=1);

namespace Ara\Http;

final class JsonResponse
{
    /**
     * @param array<string, mixed> $payload
     * @param array<string, string> $headers
     */
    public static function send(int $status, array $payload, array $headers = []): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');

        foreach ($headers as $name => $value) {
            header($name . ': ' . $value);
        }

        echo json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );
        exit;
    }
}
