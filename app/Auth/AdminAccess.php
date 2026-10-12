<?php

declare(strict_types=1);

namespace Ara\Auth;

use Ara\Database\Connection;
use PDO;

final class AdminAccess
{
    private ?PDO $connection = null;

    /**
     * @param array<string, mixed> $app
     * @param array<string, mixed> $database
     * @param array<string, mixed> $configuration
     */
    public function __construct(
        private readonly array $app,
        private readonly array $database,
        private readonly array $configuration
    ) {
    }

    /** @return array{id: int, username: string}|null */
    public function currentAdmin(): ?array
    {
        $adminId = AuthSession::adminId(
            $this->configuration['idle_timeout_seconds'],
            $this->configuration['absolute_timeout_seconds']
        );

        if ($adminId === null) {
            return null;
        }

        $admin = (new AdminRepository($this->connection()))->findActiveById($adminId);

        if (
            $admin === null
            || !AuthSession::credentialIsCurrent(
                $admin['password_hash'],
                $admin['session_version'],
                $this->app['key']
            )
        ) {
            AuthSession::logout();
            return null;
        }

        return [
            'id' => $admin['id'],
            'username' => $admin['username'],
        ];
    }

    public function connection(): PDO
    {
        if ($this->connection === null) {
            $this->connection = Connection::make($this->database);
        }

        return $this->connection;
    }
}
