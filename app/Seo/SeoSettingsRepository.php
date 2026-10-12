<?php

declare(strict_types=1);

namespace Ara\Seo;

use PDO;

final class SeoSettingsRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    /** @return array<string, string> */
    public function get(): array
    {
        $statement = $this->connection->prepare(
            'SELECT homepage_title, meta_description, canonical_url, og_title, og_description, '
            . 'twitter_card, twitter_title, twitter_description, robots_index, robots_follow, '
            . 'google_verification, bing_verification, social_image_path FROM seo_settings WHERE id = 1 LIMIT 1'
        );
        $statement->execute();
        $row = $statement->fetch();

        if (!is_array($row)) {
            return [];
        }

        return array_map(static fn (mixed $value): string => $value === null ? '' : (string) $value, $row);
    }

    /** @param array<string, string> $settings */
    public function save(array $settings, int $adminId): void
    {
        $statement = $this->connection->prepare(
            'INSERT INTO seo_settings (id, homepage_title, meta_description, canonical_url, og_title, '
            . 'og_description, twitter_card, twitter_title, twitter_description, robots_index, robots_follow, '
            . 'google_verification, bing_verification, social_image_path, updated_by_admin_id, updated_at) '
            . 'VALUES (1, :homepage_title, :meta_description, :canonical_url, :og_title, :og_description, '
            . ':twitter_card, :twitter_title, :twitter_description, :robots_index, :robots_follow, '
            . 'NULLIF(:google_verification, \'\'), NULLIF(:bing_verification, \'\'), :social_image_path, :admin_id, UTC_TIMESTAMP()) '
            . 'ON DUPLICATE KEY UPDATE homepage_title = VALUES(homepage_title), meta_description = VALUES(meta_description), '
            . 'canonical_url = VALUES(canonical_url), og_title = VALUES(og_title), og_description = VALUES(og_description), '
            . 'twitter_card = VALUES(twitter_card), twitter_title = VALUES(twitter_title), '
            . 'twitter_description = VALUES(twitter_description), robots_index = VALUES(robots_index), '
            . 'robots_follow = VALUES(robots_follow), google_verification = VALUES(google_verification), '
            . 'bing_verification = VALUES(bing_verification), social_image_path = VALUES(social_image_path), '
            . 'updated_by_admin_id = VALUES(updated_by_admin_id), updated_at = UTC_TIMESTAMP()'
        );
        $statement->execute($settings + ['admin_id' => $adminId]);
    }
}
