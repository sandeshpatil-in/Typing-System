-- Run this only on existing installations that already have test_attempts
-- and are missing the newer guest-tracking columns.

ALTER TABLE test_attempts
    ADD COLUMN guest_session_id VARCHAR(64) DEFAULT NULL AFTER student_id,
    ADD COLUMN access_type ENUM('guest','paid') NOT NULL DEFAULT 'paid' AFTER typed_words;

CREATE INDEX idx_attempts_guest ON test_attempts (guest_session_id);
