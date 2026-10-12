<?php

declare(strict_types=1);

namespace Ara\Auth;

final class Authenticator
{
    public function __construct(
        private readonly AdminRepository $admins,
        private readonly string $applicationKey
    ) {
    }

    /** @return array{id: int, username: string, credential_version: string}|null */
    public function attempt(string $username, string $password): ?array
    {
        $admin = $this->admins->findByUsername($username);
        $dummySecret = hash_hmac('sha256', 'admin-authentication-dummy', $this->applicationKey);
        $dummyHash = password_hash($dummySecret, PASSWORD_DEFAULT);
        $storedHash = $admin['password_hash'] ?? $dummyHash;
        $passwordMatches = password_verify($password, $storedHash);

        if ($admin === null || $admin['is_active'] !== 1 || !$passwordMatches) {
            return null;
        }

        if (password_needs_rehash($storedHash, PASSWORD_DEFAULT)) {
            $storedHash = password_hash($password, PASSWORD_DEFAULT);
            $admin['session_version'] = $this->admins->updatePasswordHash($admin['id'], $storedHash);
        }

        return [
            'id' => $admin['id'],
            'username' => $admin['username'],
            'credential_version' => AuthSession::credentialVersion(
                $storedHash,
                $admin['session_version'],
                $this->applicationKey
            ),
        ];
    }
}
