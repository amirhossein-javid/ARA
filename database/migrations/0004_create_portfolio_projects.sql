-- Portfolio CMS foundation for ARA Studio Phase 4B.
-- Import manually after 0003_extend_consultation_workflow.sql.
-- No sample or fictional client records are inserted by this migration.

CREATE TABLE portfolio_projects (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(160) NOT NULL,
    short_description VARCHAR(500) NOT NULL,
    image_path VARCHAR(255) NULL,
    telegram_url VARCHAR(500) NULL,
    display_order INT UNSIGNED NOT NULL DEFAULT 0,
    is_published TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    INDEX idx_portfolio_public_order (is_published, display_order, id),
    INDEX idx_portfolio_updated (updated_at),
    CONSTRAINT chk_portfolio_publication CHECK (is_published IN (0, 1)),
    CONSTRAINT chk_portfolio_display_order CHECK (display_order <= 1000000)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
