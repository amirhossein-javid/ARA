<?php

declare(strict_types=1);

namespace Ara\Http;

final class SecurityHeaders
{
    public static function send(string $environment, string $scriptNonce): void
    {
        if (headers_sent()) {
            return;
        }

        header_remove('X-Powered-By');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header('Cross-Origin-Opener-Policy: same-origin');
        header('Cross-Origin-Resource-Policy: same-origin');
        header(
            "Content-Security-Policy: default-src 'self'; "
            . "base-uri 'self'; object-src 'none'; frame-ancestors 'self'; "
            . "form-action 'self'; img-src 'self' data:; font-src 'self'; "
            . "style-src 'self' 'unsafe-inline'; script-src 'self' 'nonce-" . $scriptNonce . "'; connect-src 'self'"
        );

        if ($environment === 'production' && self::isHttps()) {
            header('Strict-Transport-Security: max-age=31536000');
        }
    }

    private static function isHttps(): bool
    {
        $https = strtolower((string) ($_SERVER['HTTPS'] ?? ''));
        $serverPort = filter_var(
            $_SERVER['SERVER_PORT'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => 65535]]
        );

        return ($https !== '' && $https !== 'off' && $https !== '0') || $serverPort === 443;
    }
}
