<?php

declare(strict_types=1);

namespace Ara\Portfolio;

use PDO;

final class PortfolioRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    /** @return list<array<string, mixed>> */
    public function published(): array
    {
        $statement = $this->connection->prepare(
            'SELECT id, title, short_description, image_path, telegram_url, display_order '
            . 'FROM portfolio_projects WHERE is_published = 1 '
            . 'ORDER BY display_order ASC, id ASC'
        );
        $statement->execute();

        return $statement->fetchAll();
    }

    public function hasAny(): bool
    {
        $statement = $this->connection->prepare('SELECT id FROM portfolio_projects LIMIT 1');
        $statement->execute();

        return $statement->fetchColumn() !== false;
    }

    /** @return array{items: list<array<string, mixed>>, total: int} */
    public function paginate(?bool $published, int $page, int $perPage): array
    {
        $where = $published === null ? '' : ' WHERE is_published = :published';
        $parameters = $published === null ? [] : ['published' => $published ? 1 : 0];
        $count = $this->connection->prepare('SELECT COUNT(*) FROM portfolio_projects' . $where);
        $count->execute($parameters);
        $total = (int) $count->fetchColumn();
        $statement = $this->connection->prepare(
            'SELECT id, title, short_description, image_path, telegram_url, display_order, '
            . 'is_published, created_at, updated_at FROM portfolio_projects' . $where
            . ' ORDER BY display_order ASC, id ASC LIMIT :limit OFFSET :offset'
        );

        if ($published !== null) {
            $statement->bindValue('published', $published ? 1 : 0, PDO::PARAM_INT);
        }

        $statement->bindValue('limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue('offset', max(0, ($page - 1) * $perPage), PDO::PARAM_INT);
        $statement->execute();

        return ['items' => $statement->fetchAll(), 'total' => $total];
    }

    /** @return array<string, mixed>|null */
    public function find(int $projectId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT id, title, short_description, image_path, telegram_url, display_order, '
            . 'is_published, created_at, updated_at FROM portfolio_projects WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $projectId]);
        $project = $statement->fetch();

        return is_array($project) ? $project : null;
    }

    /** @param array{title: string, short_description: string, telegram_url: ?string, display_order: int, is_published: int} $data */
    public function create(array $data, ?string $imagePath): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO portfolio_projects '
            . '(title, short_description, image_path, telegram_url, display_order, is_published, created_at, updated_at) '
            . 'VALUES (:title, :short_description, :image_path, :telegram_url, :display_order, :is_published, UTC_TIMESTAMP(), UTC_TIMESTAMP())'
        );
        $statement->execute([
            'title' => $data['title'],
            'short_description' => $data['short_description'],
            'image_path' => $imagePath,
            'telegram_url' => $data['telegram_url'],
            'display_order' => $data['display_order'],
            'is_published' => $data['is_published'],
        ]);

        return (int) $this->connection->lastInsertId();
    }

    /** @param array{title: string, short_description: string, telegram_url: ?string, display_order: int, is_published: int} $data */
    public function update(int $projectId, array $data, ?string $imagePath): bool
    {
        $statement = $this->connection->prepare(
            'UPDATE portfolio_projects SET title = :title, short_description = :short_description, '
            . 'image_path = :image_path, telegram_url = :telegram_url, display_order = :display_order, '
            . 'is_published = :is_published, updated_at = UTC_TIMESTAMP() WHERE id = :id'
        );
        $statement->execute([
            'title' => $data['title'],
            'short_description' => $data['short_description'],
            'image_path' => $imagePath,
            'telegram_url' => $data['telegram_url'],
            'display_order' => $data['display_order'],
            'is_published' => $data['is_published'],
            'id' => $projectId,
        ]);

        return $statement->rowCount() > 0 || $this->find($projectId) !== null;
    }

    public function setPublication(int $projectId, bool $published): bool
    {
        $statement = $this->connection->prepare(
            'UPDATE portfolio_projects SET is_published = :published, updated_at = UTC_TIMESTAMP() WHERE id = :id'
        );
        $statement->execute(['published' => $published ? 1 : 0, 'id' => $projectId]);

        return $statement->rowCount() > 0 || $this->find($projectId) !== null;
    }

    public function delete(int $projectId): bool
    {
        $statement = $this->connection->prepare('DELETE FROM portfolio_projects WHERE id = :id');
        $statement->execute(['id' => $projectId]);

        return $statement->rowCount() === 1;
    }
}
