-- Safe testing mode for Grade Email Alerts

ALTER TABLE grade_email_settings
    ADD COLUMN test_mode TINYINT(1) NOT NULL DEFAULT 1 AFTER global_recipients,
    ADD COLUMN test_recipient VARCHAR(190) NULL AFTER test_mode;

UPDATE grade_email_settings SET test_mode=1 WHERE id=1;
