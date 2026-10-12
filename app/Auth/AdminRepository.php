<?php

declare(strict_types=1);

namespace Ara\Auth;

use PDO;

final class AdminRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    /** @return array{id: int, username: string, password_hash: string, is_active: int, session_version: int}|null */
    public function findByUsername(string $username): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT id, username, password_hash, is_active, session_version FROM admins WHERE username = :username LIMIT 1'
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
            'session_version' => (int) $admin['session_version'],
        ];
    }

    /** @return array{id: int, username: string, password_hash: string, session_version: int}|null */
    public function findActiveById(int $adminId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT id, username, password_hash, session_version FROM admins WHERE id = :id AND is_active = 1 LIMIT 1'
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
            'session_version' => (int) $admin['session_version'],
        ];
    }

    public function countAll(): int
    {
        $statement = $this->connection->prepare('SELECT COUNT(*) FROM admins');
        $statement->execute();

        return (int) $statement->fetchColumn();
    }

    /** @return list<array{id: int, username: string, is_active: int, created_at: string, updated_at: string}> */
    public function all(): array
    {
        $statement = $this->connection->prepare(
            'SELECT id, username, is_active, created_at, updated_at FROM admins ORDER BY username, id'
        );
        $statement->execute();

        return array_map(static fn (array $admin): array => [
            'id' => (int) $admin['id'],
            'username' => (string) $admin['username'],
            'is_active' => (int) $admin['is_active'],
            'created_at' => (string) $admin['created_at'],
            'updated_at' => (string) $admin['updated_at'],
        ], $statement->fetchAll());
    }

    /** @return array{id: int, username: string, password_hash: string, is_active: int, session_version: int, created_at: string, updated_at: string}|null */
    public function findById(int $adminId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT id, username, password_hash, is_active, session_version, created_at, updated_at FROM admins WHERE id = :id LIMIT 1'
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
            'is_active' => (int) $admin['is_active'],
            'session_version' => (int) $admin['session_version'],
            'created_at' => (string) $admin['created_at'],
            'updated_at' => (string) $admin['updated_at'],
        ];
    }

    /** @return list<int> */
    public function activeIdsForUpdate(): array
    {
        $statement = $this->connection->prepare('SELECT id FROM admins WHERE is_active = 1 ORDER BY id FOR UPDATE');
        $statement->execute();

        return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
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

    public function updatePasswordHash(int $adminId, string $passwordHash): int
    {
        $statement = $this->connection->prepare(
            'UPDATE admins SET password_hash = :password_hash, session_version = session_version + 1, '
            . 'updated_at = UTC_TIMESTAMP() WHERE id = :id'
        );
        $statement->execute([
            'password_hash' => $passwordHash,
            'id' => $adminId,
        ]);

        $admin = $this->findById($adminId);
        return $admin === null ? 0 : $admin['session_version'];
    }

    public function setActive(int $adminId, bool $active): bool
    {
        $statement = $this->connection->prepare(
            'UPDATE admins SET is_active = :is_active, session_version = session_version + 1, '
            . 'updated_at = UTC_TIMESTAMP() WHERE id = :id'
        );
        $statement->execute(['is_active' => $active ? 1 : 0, 'id' => $adminId]);

        return $statement->rowCount() === 1;
    }
}
