<?php

declare(strict_types=1);

namespace Ara\Auth;

use DomainException;
use PDO;
use Throwable;

final class AdminAccountService
{
    public function __construct(
        private readonly PDO $connection,
        private readonly AdminRepository $admins,
        private readonly int $minimumPasswordLength
    ) {
    }

    /** @param list<array{username: mixed, password: mixed}> $accounts */
    public function provisionExactlyTwo(array $accounts): void
    {
        if (count($accounts) !== 2) {
            throw new DomainException('Provisioning requires exactly two administrator accounts.');
        }

        $validated = [];

        foreach ($accounts as $account) {
            if (
                !is_array($account)
                || array_diff(array_keys($account), ['password', 'username']) !== []
                || !array_key_exists('username', $account)
                || !array_key_exists('password', $account)
            ) {
                throw new DomainException('Administrator account data is invalid.');
            }

            $username = $this->normalizeUsername($account['username'] ?? null);
            $password = $this->validatePassword($account['password'] ?? null, $username);
            $validated[] = ['username' => $username, 'password' => $password];
        }

        if ($validated[0]['username'] === $validated[1]['username']) {
            throw new DomainException('Administrator usernames must be different.');
        }

        $lockStatement = $this->connection->prepare(
            "SELECT GET_LOCK(CONCAT(DATABASE(), ':ara_admin_provisioning_v1'), 10)"
        );
        $lockStatement->execute();

        if ((int) $lockStatement->fetchColumn() !== 1) {
            throw new DomainException('Administrator provisioning is already running.');
        }

        try {
            $this->connection->beginTransaction();

            try {
                if ($this->admins->countAll() !== 0) {
                    throw new DomainException('Provisioning refused because administrator accounts already exist.');
                }

                foreach ($validated as $account) {
                    $this->admins->create(
                        $account['username'],
                        password_hash($account['password'], PASSWORD_DEFAULT)
                    );
                }

                if ($this->admins->countAll() !== 2) {
                    throw new DomainException('Provisioning did not create exactly two administrator accounts.');
                }

                $this->connection->commit();
            } catch (Throwable $throwable) {
                if ($this->connection->inTransaction()) {
                    $this->connection->rollBack();
                }

                throw $throwable;
            }
        } finally {
            try {
                $releaseStatement = $this->connection->prepare(
                    "SELECT RELEASE_LOCK(CONCAT(DATABASE(), ':ara_admin_provisioning_v1'))"
                );
                $releaseStatement->execute();
            } catch (Throwable) {
                // MySQL releases advisory locks automatically when the connection closes.
            }
        }
    }

    public function rotatePassword(string $username, string $currentPassword, string $newPassword): void
    {
        $username = $this->normalizeUsername($username);
        $newPassword = $this->validatePassword($newPassword, $username);
        $admin = $this->admins->findByUsername($username);

        if (
            $admin === null
            || $admin['is_active'] !== 1
            || !password_verify($currentPassword, $admin['password_hash'])
            || password_verify($newPassword, $admin['password_hash'])
        ) {
            throw new DomainException('Password rotation was not authorized.');
        }

        $this->admins->updatePasswordHash(
            $admin['id'],
            password_hash($newPassword, PASSWORD_DEFAULT)
        );
    }

    private function normalizeUsername(mixed $username): string
    {
        if (!is_string($username)) {
            throw new DomainException('Administrator account data is invalid.');
        }

        $username = strtolower(trim($username));

        if (!preg_match('/^[a-z0-9][a-z0-9._-]{2,63}$/', $username)) {
            throw new DomainException('Administrator usernames must use 3-64 lowercase ASCII characters.');
        }

        return $username;
    }

    private function validatePassword(mixed $password, string $username): string
    {
        if (!is_string($password)) {
            throw new DomainException('Administrator account data is invalid.');
        }

        if (strlen($password) < $this->minimumPasswordLength || strlen($password) > 4096) {
            throw new DomainException(sprintf(
                'Administrator passwords must contain at least %d characters.',
                $this->minimumPasswordLength
            ));
        }

        if (hash_equals(strtolower($password), $username)) {
            throw new DomainException('An administrator password cannot equal its username.');
        }

        return $password;
    }
}
