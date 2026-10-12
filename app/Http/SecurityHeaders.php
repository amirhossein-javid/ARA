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
        $forwardedProto = strtolower(trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));

        return ($https !== '' && $https !== 'off') || $forwardedProto === 'https';
    }
}
