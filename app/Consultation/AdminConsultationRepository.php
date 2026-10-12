<?php

declare(strict_types=1);

namespace Ara\Consultation;

use PDO;

final class AdminConsultationRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    /** @return array{total: int, new: int, follow_up: int, recent: int} */
    public function statistics(): array
    {
        $statement = $this->connection->prepare(
            "SELECT COUNT(*) AS total, "
            . "COALESCE(SUM(status = 'new'), 0) AS new_count, "
            . "COALESCE(SUM(status = 'in_progress'), 0) AS follow_up_count, "
            . 'COALESCE(SUM(created_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 7 DAY)), 0) AS recent_count '
            . 'FROM consultation_requests'
        );
        $statement->execute();
        $row = $statement->fetch();

        return [
            'total' => (int) ($row['total'] ?? 0),
            'new' => (int) ($row['new_count'] ?? 0),
            'follow_up' => (int) ($row['follow_up_count'] ?? 0),
            'recent' => (int) ($row['recent_count'] ?? 0),
        ];
    }

    /** @return list<array<string, mixed>> */
    public function recent(int $limit = 6): array
    {
        $limit = max(1, min($limit, 20));
        $statement = $this->connection->prepare(
            'SELECT id, name, contact, service, status, created_at, updated_at '
            . 'FROM consultation_requests ORDER BY created_at DESC, id DESC LIMIT :limit'
        );
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int}
     */
    public function paginate(string $search, ?string $status, int $page, int $perPage): array
    {
        $where = [];
        $parameters = [];

        if ($status !== null) {
            $where[] = 'status = :status';
            $parameters['status'] = $status;
        }

        if ($search !== '') {
            $where[] = '(LOCATE(:search_name, name) > 0 '
                . 'OR LOCATE(:search_contact, contact) > 0 '
                . 'OR LOCATE(:search_service, service) > 0 '
                . 'OR LOCATE(:search_message, message) > 0)';
            $parameters['search_name'] = $search;
            $parameters['search_contact'] = $search;
            $parameters['search_service'] = $search;
            $parameters['search_message'] = $search;
        }

        $whereSql = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);
        $countStatement = $this->connection->prepare(
            'SELECT COUNT(*) FROM consultation_requests' . $whereSql
        );
        $countStatement->execute($parameters);
        $total = (int) $countStatement->fetchColumn();
        $offset = max(0, ($page - 1) * $perPage);
        $statement = $this->connection->prepare(
            'SELECT id, name, contact, service, status, created_at, updated_at '
            . 'FROM consultation_requests' . $whereSql
            . ' ORDER BY created_at DESC, id DESC LIMIT :limit OFFSET :offset'
        );

        foreach ($parameters as $key => $value) {
            $statement->bindValue($key, $value, PDO::PARAM_STR);
        }

        $statement->bindValue('limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue('offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return ['items' => $statement->fetchAll(), 'total' => $total];
    }

    /** @return array<string, mixed>|null */
    public function find(int $requestId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT r.id, r.name, r.contact, r.service, r.message, r.status, r.admin_notes, '
            . 'r.created_at, r.updated_at, r.last_updated_by_admin_id, a.username AS updated_by_username '
            . 'FROM consultation_requests r '
            . 'LEFT JOIN admins a ON a.id = r.last_updated_by_admin_id '
            . 'WHERE r.id = :id LIMIT 1'
        );
        $statement->execute(['id' => $requestId]);
        $request = $statement->fetch();

        return is_array($request) ? $request : null;
    }

    public function updateStatus(int $requestId, string $status, int $adminId): bool
    {
        $statement = $this->connection->prepare(
            'UPDATE consultation_requests SET status = :status, last_updated_by_admin_id = :admin_id, '
            . 'updated_at = UTC_TIMESTAMP() WHERE id = :id'
        );
        $statement->execute([
            'status' => $status,
            'admin_id' => $adminId,
            'id' => $requestId,
        ]);

        return $statement->rowCount() > 0 || $this->exists($requestId);
    }

    public function updateNotes(int $requestId, string $notes, int $adminId): bool
    {
        $statement = $this->connection->prepare(
            'UPDATE consultation_requests SET admin_notes = :notes, last_updated_by_admin_id = :admin_id, '
            . 'updated_at = UTC_TIMESTAMP() WHERE id = :id'
        );
        $statement->execute([
            'notes' => $notes === '' ? null : $notes,
            'admin_id' => $adminId,
            'id' => $requestId,
        ]);

        return $statement->rowCount() > 0 || $this->exists($requestId);
    }

    public function delete(int $requestId): bool
    {
        $statement = $this->connection->prepare(
            'DELETE FROM consultation_requests WHERE id = :id'
        );
        $statement->execute(['id' => $requestId]);

        return $statement->rowCount() === 1;
    }

    private function exists(int $requestId): bool
    {
        $statement = $this->connection->prepare(
            'SELECT id FROM consultation_requests WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $requestId]);

        return $statement->fetchColumn() !== false;
    }
}
