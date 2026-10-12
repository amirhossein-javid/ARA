<?php

declare(strict_types=1);

namespace Ara\Settings;

use PDO;
use Throwable;

final class SiteSettingsRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    /** @return array<string, string> */
    public function all(): array
    {
        $keys = array_keys(SiteSettings::DEFAULTS);
        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $statement = $this->connection->prepare(
            'SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN (' . $placeholders . ')'
        );
        $statement->execute($keys);
        $settings = [];

        foreach ($statement->fetchAll() as $row) {
            $settings[(string) $row['setting_key']] = (string) $row['setting_value'];
        }

        return $settings;
    }

    /** @param array<string, string> $settings */
    public function save(array $settings, int $adminId): void
    {
        $this->connection->beginTransaction();

        try {
            $statement = $this->connection->prepare(
                'INSERT INTO site_settings (setting_key, setting_value, updated_by_admin_id, updated_at) '
                . 'VALUES (:setting_key, :setting_value, :admin_id, UTC_TIMESTAMP()) '
                . 'ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), '
                . 'updated_by_admin_id = VALUES(updated_by_admin_id), updated_at = UTC_TIMESTAMP()'
            );

            foreach ($settings as $key => $value) {
                $statement->execute(['setting_key' => $key, 'setting_value' => $value, 'admin_id' => $adminId]);
            }

            $this->connection->commit();
        } catch (Throwable $throwable) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }
            throw $throwable;
        }
    }
}
