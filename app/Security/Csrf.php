<?php

declare(strict_types=1);

namespace Ara\Security;

final class Csrf
{
    private const SESSION_KEY = 'csrf_token';

    public static function token(): string
    {
        Session::start();
        $token = $_SESSION[self::SESSION_KEY] ?? null;

        if (!is_string($token) || strlen($token) !== 64) {
            $token = bin2hex(random_bytes(32));
            $_SESSION[self::SESSION_KEY] = $token;
        }

        return $token;
    }

    public static function verify(?string $providedToken): bool
    {
        $expectedToken = self::token();

        return is_string($providedToken)
            && strlen($providedToken) === 64
            && hash_equals($expectedToken, $providedToken);
    }

    public static function rotate(): string
    {
        Session::start();
        $token = bin2hex(random_bytes(32));
        $_SESSION[self::SESSION_KEY] = $token;

        return $token;
    }
}
