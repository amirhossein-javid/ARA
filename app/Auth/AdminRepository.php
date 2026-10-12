<?php

declare(strict_types=1);

namespace Ara\Auth;

use PDO;

final class AdminRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    /** @return array{id: int, username: string, password_hash: string, is_active: int}|null */
    public function findByUsername(string $username): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT id, username, password_hash, is_active FROM admins WHERE username = :username LIMIT 1'
        );
        $statement->execute(['username' => $username]);
        $admin = $statement->fetch();

        if (!is_array($admin)) {
            return null;
        }

        return [
            'id' => (int) $admin['id'],
            'username' => (string) $admin['username'],
            'password_hash' => (string) $admin['password_hash'],
            'is_active' => (int) $admin['is_active'],
        ];
    }

    /** @return array{id: int, username: string, password_hash: string}|null */
    public function findActiveById(int $adminId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT id, username, password_hash FROM admins WHERE id = :id AND is_active = 1 LIMIT 1'
        );
        $statement->execute(['id' => $adminId]);
        $admin = $statement->fetch();

        if (!is_array($admin)) {
            return null;
        }

        return [
            'id' => (int) $admin['id'],
            'username' => (string) $admin['username'],
            'password_hash' => (string) $admin['password_hash'],
        ];
    }

    public function countAll(): int
    {
        $statement = $this->connection->prepare('SELECT COUNT(*) FROM admins');
        $statement->execute();

        return (int) $statement->fetchColumn();
    }

    public function create(string $username, string $passwordHash): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO admins (username, password_hash, is_active, created_at, updated_at) '
            . 'VALUES (:username, :password_hash, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP())'
        );
        $statement->execute([
            'username' => $username,
            'password_hash' => $passwordHash,
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function updatePasswordHash(int $adminId, string $passwordHash): void
    {
        $statement = $this->connection->prepare(
            'UPDATE admins SET password_hash = :password_hash, updated_at = UTC_TIMESTAMP() WHERE id = :id'
        );
        $statement->execute([
            'password_hash' => $passwordHash,
            'id' => $adminId,
        ]);
    }
}
