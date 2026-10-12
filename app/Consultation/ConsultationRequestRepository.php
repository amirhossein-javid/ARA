<?php

declare(strict_types=1);

namespace Ara\Consultation;

use PDO;

final class ConsultationRequestRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    /** @param array{name: string, contact: string, service: string, message: string} $request */
    public function existsRecently(array $request, int $windowSeconds): bool
    {
        $windowSeconds = max(1, min($windowSeconds, 3600));
        $sql = sprintf(
            'SELECT id FROM consultation_requests '
            . 'WHERE name = :name AND contact = :contact AND service = :service AND message = :message '
            . 'AND created_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL %d SECOND) LIMIT 1',
            $windowSeconds
        );
        $statement = $this->connection->prepare($sql);
        $statement->execute([
            'name' => $request['name'],
            'contact' => $request['contact'],
            'service' => $request['service'],
            'message' => $request['message'],
        ]);

        return $statement->fetchColumn() !== false;
    }

    /** @param array{name: string, contact: string, service: string, message: string} $request */
    public function create(array $request): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO consultation_requests '
            . '(name, contact, service, message, status, created_at, updated_at) '
            . "VALUES (:name, :contact, :service, :message, 'new', UTC_TIMESTAMP(), UTC_TIMESTAMP())"
        );
        $statement->execute([
            'name' => $request['name'],
            'contact' => $request['contact'],
            'service' => $request['service'],
            'message' => $request['message'],
        ]);

        return (int) $this->connection->lastInsertId();
    }
}
