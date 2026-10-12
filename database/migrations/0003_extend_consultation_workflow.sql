-- Administrative consultation workflow for ARA Studio Phase 4A.
-- Import manually after 0002_create_admins.sql. Existing request rows are kept.

ALTER TABLE consultation_requests
    ADD COLUMN admin_notes TEXT NULL AFTER status,
    ADD COLUMN last_updated_by_admin_id BIGINT UNSIGNED NULL AFTER admin_notes,
    ADD INDEX idx_consultation_updated (updated_at),
    ADD INDEX idx_consultation_last_admin (last_updated_by_admin_id),
    ADD CONSTRAINT chk_consultation_status
        CHECK (status IN ('new', 'in_progress', 'completed', 'archived')),
    ADD CONSTRAINT fk_consultation_last_admin
        FOREIGN KEY (last_updated_by_admin_id) REFERENCES admins (id)
        ON UPDATE RESTRICT ON DELETE SET NULL;
