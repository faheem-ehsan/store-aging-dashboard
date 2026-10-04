-- Grade transition email alert system (Super Admin controlled)

CREATE TABLE IF NOT EXISTS grade_email_settings (
    id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
    is_enabled TINYINT(1) NOT NULL DEFAULT 0,
    include_division_admins TINYINT(1) NOT NULL DEFAULT 1,
    smtp_host VARCHAR(190) NULL,
    smtp_port INT UNSIGNED NOT NULL DEFAULT 587,
    smtp_encryption ENUM('none','tls','ssl') NOT NULL DEFAULT 'tls',
    smtp_username VARCHAR(190) NULL,
    smtp_password_enc TEXT NULL,
    from_email VARCHAR(190) NULL,
    from_name VARCHAR(190) NOT NULL DEFAULT 'Qarshi Stock Aging Dashboard',
    global_recipients TEXT NULL,
    test_mode TINYINT(1) NOT NULL DEFAULT 1,
    test_recipient VARCHAR(190) NULL,
    last_run_at DATETIME NULL,
    last_run_status VARCHAR(30) NULL,
    last_run_summary VARCHAR(500) NULL,
    updated_by INT UNSIGNED NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_grade_email_settings_user FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS grade_email_division_recipients (
    division_id INT UNSIGNED NOT NULL PRIMARY KEY,
    recipient_emails TEXT NULL,
    updated_by INT UNSIGNED NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_grade_email_recipient_division FOREIGN KEY (division_id) REFERENCES divisions(id) ON DELETE CASCADE,
    CONSTRAINT fk_grade_email_recipient_user FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS grade_email_alert_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    alert_key VARCHAR(190) NOT NULL UNIQUE,
    batch_id BIGINT UNSIGNED NULL,
    division_id INT UNSIGNED NULL,
    product_code VARCHAR(80) NULL,
    product_name VARCHAR(255) NULL,
    batch_no VARCHAR(80) NULL,
    current_grade VARCHAR(10) NULL,
    next_grade VARCHAR(10) NULL,
    transition_date DATE NULL,
    alert_date DATE NULL,
    recipients TEXT NULL,
    subject VARCHAR(255) NULL,
    status ENUM('sent','failed') NOT NULL,
    error_message TEXT NULL,
    attempt_count INT UNSIGNED NOT NULL DEFAULT 1,
    sent_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_grade_email_alert_batch FOREIGN KEY (batch_id) REFERENCES stock_batches(id) ON DELETE SET NULL,
    CONSTRAINT fk_grade_email_alert_division FOREIGN KEY (division_id) REFERENCES divisions(id) ON DELETE SET NULL,
    INDEX idx_grade_email_alert_status (status, alert_date),
    INDEX idx_grade_email_alert_division (division_id, alert_date)
) ENGINE=InnoDB;

INSERT IGNORE INTO grade_email_settings (id) VALUES (1);
