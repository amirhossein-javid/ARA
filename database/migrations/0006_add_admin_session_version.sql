-- ARA Studio Phase 4C: durable server-side session invalidation.
-- Import manually after 0005_create_site_settings.sql.

ALTER TABLE admins
    ADD COLUMN session_version BIGINT UNSIGNED NOT NULL DEFAULT 1 AFTER is_active;
