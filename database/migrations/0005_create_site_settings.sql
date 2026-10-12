-- ARA Studio Phase 4C: allow-listed public site settings.
-- Import manually after 0004_create_portfolio_projects.sql.

CREATE TABLE site_settings (
    setting_key VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    setting_value VARCHAR(500) NOT NULL,
    updated_by_admin_id BIGINT UNSIGNED NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (setting_key),
    KEY idx_site_settings_updated_by (updated_by_admin_id),
    CONSTRAINT fk_site_settings_updated_by
        FOREIGN KEY (updated_by_admin_id) REFERENCES admins (id)
        ON UPDATE RESTRICT ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO site_settings (setting_key, setting_value) VALUES
    ('brand_name', 'ARA Studio'),
    ('website_url', 'https://ara-studio.ir'),
    ('telegram_url', 'https://t.me/ARA_STUDIO_TM'),
    ('contact_email', 'info@ara-studio.ir'),
    ('contact_phone', '');
