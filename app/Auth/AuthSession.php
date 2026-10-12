<?php

declare(strict_types=1);

namespace Ara\Auth;

use Ara\Security\Csrf;
use Ara\Security\Session;
use RuntimeException;

final class AuthSession
{
    private const ADMIN_ID = 'admin_id';
    private const AUTHENTICATED_AT = 'admin_authenticated_at';
    private const LAST_ACTIVITY_AT = 'admin_last_activity_at';
    private const CREDENTIAL_VERSION = 'admin_credential_version';

    public static function login(int $adminId, string $credentialVersion): void
    {
        Session::start();

        if (!session_regenerate_id(true)) {
            throw new RuntimeException('The authenticated session could not be regenerated.');
        }

        $now = time();
        $_SESSION[self::ADMIN_ID] = $adminId;
        $_SESSION[self::AUTHENTICATED_AT] = $now;
        $_SESSION[self::LAST_ACTIVITY_AT] = $now;
        $_SESSION[self::CREDENTIAL_VERSION] = $credentialVersion;
        Csrf::rotate();
    }

    public static function adminId(int $idleTimeout, int $absoluteTimeout): ?int
    {
        Session::start();
        $adminId = $_SESSION[self::ADMIN_ID] ?? null;
        $authenticatedAt = $_SESSION[self::AUTHENTICATED_AT] ?? null;
        $lastActivityAt = $_SESSION[self::LAST_ACTIVITY_AT] ?? null;
        $credentialVersion = $_SESSION[self::CREDENTIAL_VERSION] ?? null;

        if (
            !is_int($adminId)
            || !is_int($authenticatedAt)
            || !is_int($lastActivityAt)
            || !is_string($credentialVersion)
            || strlen($credentialVersion) !== 64
        ) {
            self::clear(false);
            return null;
        }

        $now = time();
        $invalidClock = $authenticatedAt > $now || $lastActivityAt > $now;
        $idleExpired = ($now - $lastActivityAt) >= $idleTimeout;
        $absoluteExpired = ($now - $authenticatedAt) >= $absoluteTimeout;

        if ($invalidClock || $idleExpired || $absoluteExpired) {
            self::clear(true);
            return null;
        }

        $_SESSION[self::LAST_ACTIVITY_AT] = $now;

        return $adminId;
    }

    public static function credentialIsCurrent(string $passwordHash, string $applicationKey): bool
    {
        Session::start();
        $storedVersion = $_SESSION[self::CREDENTIAL_VERSION] ?? null;
        $currentVersion = hash_hmac('sha256', $passwordHash, $applicationKey);

        return is_string($storedVersion)
            && strlen($storedVersion) === 64
            && hash_equals($storedVersion, $currentVersion);
    }

    public static function logout(): void
    {
        Session::start();
        self::clear(true);
    }

    private static function clear(bool $regenerate): void
    {
        unset(
            $_SESSION[self::ADMIN_ID],
            $_SESSION[self::AUTHENTICATED_AT],
            $_SESSION[self::LAST_ACTIVITY_AT],
            $_SESSION[self::CREDENTIAL_VERSION]
        );

        if ($regenerate && !session_regenerate_id(true)) {
            throw new RuntimeException('The anonymous session could not be regenerated.');
        }

        if ($regenerate) {
            Csrf::rotate();
        }
    }
}
