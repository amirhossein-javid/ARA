-- Consultation requests for ARA Studio Phase 2.
-- Import manually after 0000_database_foundation.sql. Never run migrations
-- automatically during a web request or deployment.

CREATE TABLE consultation_requests (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    contact VARCHAR(190) NOT NULL,
    service VARCHAR(80) NOT NULL,
    message TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'new',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    INDEX idx_consultation_status_created (status, created_at),
    INDEX idx_consultation_created (created_at)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
