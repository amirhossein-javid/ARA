-- ARA Studio Phase 4D: strict, single-row homepage SEO configuration.
-- Import manually after 0006_add_admin_session_version.sql.

CREATE TABLE seo_settings (
    id TINYINT UNSIGNED NOT NULL,
    homepage_title VARCHAR(70) NOT NULL,
    meta_description VARCHAR(170) NOT NULL,
    canonical_url VARCHAR(255) NOT NULL,
    og_title VARCHAR(95) NOT NULL,
    og_description VARCHAR(200) NOT NULL,
    twitter_card VARCHAR(24) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    twitter_title VARCHAR(70) NOT NULL,
    twitter_description VARCHAR(200) NOT NULL,
    robots_index TINYINT(1) NOT NULL DEFAULT 1,
    robots_follow TINYINT(1) NOT NULL DEFAULT 1,
    google_verification VARCHAR(255) CHARACTER SET ascii COLLATE ascii_bin NULL,
    bing_verification VARCHAR(255) CHARACTER SET ascii COLLATE ascii_bin NULL,
    social_image_path VARCHAR(255) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    updated_by_admin_id BIGINT UNSIGNED NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_seo_settings_updated_by (updated_by_admin_id),
    CONSTRAINT chk_seo_singleton CHECK (id = 1),
    CONSTRAINT chk_seo_twitter_card CHECK (twitter_card IN ('summary', 'summary_large_image')),
    CONSTRAINT chk_seo_robots_index CHECK (robots_index IN (0, 1)),
    CONSTRAINT chk_seo_robots_follow CHECK (robots_follow IN (0, 1)),
    CONSTRAINT fk_seo_settings_updated_by
        FOREIGN KEY (updated_by_admin_id) REFERENCES admins (id)
        ON UPDATE RESTRICT ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO seo_settings (
    id, homepage_title, meta_description, canonical_url,
    og_title, og_description, twitter_card, twitter_title,
    twitter_description, robots_index, robots_follow,
    google_verification, bing_verification, social_image_path
) VALUES (
    1,
    'ARA Studio | طراحی تجربه‌های دیجیتال متمایز',
    'ARA Studio؛ طراحی وب‌سایت، رابط کاربری و هویت بصری با رویکرد مشاوره‌محور.',
    'https://ara-studio.ir/',
    'ARA Studio | طراحی تجربه‌های دیجیتال متمایز',
    'طراحی وب‌سایت، رابط کاربری و هویت بصری با رویکرد مشاوره‌محور.',
    'summary_large_image',
    'ARA Studio | طراحی تجربه‌های دیجیتال متمایز',
    'طراحی وب‌سایت، رابط کاربری و هویت بصری با رویکرد مشاوره‌محور.',
    1, 1, NULL, NULL, 'assets/ara-logo.png'
);
