CREATE DATABASE IF NOT EXISTS store_aging CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE store_aging;

SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS grade_email_alert_log;
DROP TABLE IF EXISTS grade_email_division_recipients;
DROP TABLE IF EXISTS grade_email_settings;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS market_return_division_mappings;
DROP TABLE IF EXISTS market_return_items;
DROP TABLE IF EXISTS market_return_imports;
DROP TABLE IF EXISTS stock_movements;
DROP TABLE IF EXISTS stock_batches;
DROP TABLE IF EXISTS materials;
DROP TABLE IF EXISTS division_grade_settings;
DROP TABLE IF EXISTS grade_rules;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS divisions;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE divisions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    division_id INT UNSIGNED NULL,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('super_admin','division_admin','user') NOT NULL DEFAULT 'user',
    avatar_path VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_division FOREIGN KEY (division_id) REFERENCES divisions(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_users_division (division_id),
    INDEX idx_users_role (role)
) ENGINE=InnoDB;

CREATE TABLE market_return_imports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    division_id INT UNSIGNED NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_type VARCHAR(20) NOT NULL,
    imported_by INT UNSIGNED NULL,
    row_count INT UNSIGNED NOT NULL DEFAULT 0,
    category_count INT UNSIGNED NOT NULL DEFAULT 0,
    replace_existing TINYINT(1) NOT NULL DEFAULT 1,
    status ENUM('processing','completed','failed') NOT NULL DEFAULT 'processing',
    summary_json JSON NULL,
    imported_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_market_return_import_division FOREIGN KEY (division_id) REFERENCES divisions(id) ON DELETE RESTRICT,
    CONSTRAINT fk_market_return_import_user FOREIGN KEY (imported_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_market_return_import_division (division_id, imported_at),
    INDEX idx_market_return_import_user (imported_by, imported_at)
) ENGINE=InnoDB;

CREATE TABLE market_return_division_mappings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    match_type ENUM('exact','contains') NOT NULL DEFAULT 'exact',
    match_text VARCHAR(255) NOT NULL,
    match_key VARCHAR(255) NOT NULL,
    division_id INT UNSIGNED NOT NULL,
    source_type ENUM('manual','file','login') NOT NULL DEFAULT 'manual',
    created_by INT UNSIGNED NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_mr_division_mapping_division FOREIGN KEY (division_id) REFERENCES divisions(id) ON DELETE CASCADE,
    CONSTRAINT fk_mr_division_mapping_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY uq_mr_mapping (match_type, match_key, division_id),
    INDEX idx_mr_mapping_key (match_type, match_key),
    INDEX idx_mr_mapping_division (division_id, is_active)
) ENGINE=InnoDB;

CREATE TABLE market_return_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    division_id INT UNSIGNED NOT NULL,
    category_code VARCHAR(40) NOT NULL,
    source_sheet VARCHAR(120) NOT NULL,
    section_name VARCHAR(255) NULL,
    serial_no VARCHAR(40) NULL,
    product_name VARCHAR(255) NOT NULL,
    quantity DECIMAL(18,3) NOT NULL DEFAULT 0,
    unit VARCHAR(80) NOT NULL DEFAULT 'Nos',
    expiry_raw VARCHAR(120) NULL,
    expiry_date DATE NULL,
    retail_price DECIMAL(18,2) NULL,
    total_amount DECIMAL(18,2) NULL,
    amount_text VARCHAR(120) NULL,
    remarks TEXT NULL,
    aux_label VARCHAR(80) NULL,
    aux_value VARCHAR(120) NULL,
    report_date DATE NULL,
    import_id BIGINT UNSIGNED NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_market_return_item_division FOREIGN KEY (division_id) REFERENCES divisions(id) ON DELETE RESTRICT,
    CONSTRAINT fk_market_return_item_import FOREIGN KEY (import_id) REFERENCES market_return_imports(id) ON DELETE SET NULL,
    CONSTRAINT fk_market_return_item_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_market_return_division_category (division_id, category_code),
    INDEX idx_market_return_product (product_name),
    INDEX idx_market_return_expiry (expiry_date),
    INDEX idx_market_return_report_date (report_date),
    INDEX idx_market_return_import (import_id)
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    actor_user_id INT UNSIGNED NULL,
    actor_name VARCHAR(120) NULL,
    actor_email VARCHAR(190) NULL,
    actor_role VARCHAR(40) NULL,
    division_id INT UNSIGNED NULL,
    division_code VARCHAR(20) NULL,
    action VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50) NULL,
    entity_id VARCHAR(80) NULL,
    description VARCHAR(500) NOT NULL,
    details_json JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_created (created_at),
    INDEX idx_audit_action (action, created_at),
    INDEX idx_audit_actor (actor_user_id, created_at),
    INDEX idx_audit_division (division_id, created_at),
    INDEX idx_audit_entity (entity_type, entity_id)
) ENGINE=InnoDB;

CREATE TABLE grade_rules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    division_id INT UNSIGNED NULL,
    grade_code VARCHAR(10) NOT NULL,
    grade_name VARCHAR(50) NOT NULL,
    min_days INT NULL,
    max_days INT NULL,
    color_class VARCHAR(20) NOT NULL DEFAULT 'gray',
    sort_order INT NOT NULL DEFAULT 100,
    CONSTRAINT fk_grade_division FOREIGN KEY (division_id) REFERENCES divisions(id) ON DELETE CASCADE,
    INDEX idx_grade_division (division_id, sort_order)
) ENGINE=InnoDB;

CREATE TABLE division_grade_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    division_id INT UNSIGNED NOT NULL,
    -- Legacy month columns retained for upgrade compatibility; the application uses day thresholds below.
    a_plus_months INT UNSIGNED NOT NULL DEFAULT 1,
    a_months INT UNSIGNED NOT NULL DEFAULT 2,
    b_months INT UNSIGNED NOT NULL DEFAULT 3,
    a_plus_days INT UNSIGNED NOT NULL DEFAULT 366,
    a_days INT UNSIGNED NOT NULL DEFAULT 365,
    b_days INT UNSIGNED NOT NULL DEFAULT 180,
    c_days INT UNSIGNED NOT NULL DEFAULT 90,
    -- Product-aware shelf-life grading thresholds: maximum % of shelf life used.
    a_plus_used_pct DECIMAL(5,2) NOT NULL DEFAULT 25.00,
    a_used_pct DECIMAL(5,2) NOT NULL DEFAULT 50.00,
    b_used_pct DECIMAL(5,2) NOT NULL DEFAULT 75.00,
    c_used_pct DECIMAL(5,2) NOT NULL DEFAULT 100.00,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_grade_settings_division FOREIGN KEY (division_id) REFERENCES divisions(id) ON DELETE CASCADE,
    UNIQUE KEY uq_grade_settings_division (division_id)
) ENGINE=InnoDB;

CREATE TABLE materials (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    division_id INT UNSIGNED NOT NULL,
    material_code VARCHAR(60) NOT NULL,
    description VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_material_division FOREIGN KEY (division_id) REFERENCES divisions(id) ON DELETE RESTRICT,
    UNIQUE KEY uq_material_division_code (division_id, material_code),
    INDEX idx_material_description (description)
) ENGINE=InnoDB;

CREATE TABLE stock_batches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    division_id INT UNSIGNED NOT NULL,
    material_id BIGINT UNSIGNED NOT NULL,
    batch_no VARCHAR(80) NOT NULL,
    manufacture_date DATE NOT NULL,
    expiry_date DATE NOT NULL,
    last_receipt_date DATE NULL,
    current_qty DECIMAL(18,3) NOT NULL DEFAULT 0,
    stock_value DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    uom VARCHAR(20) NOT NULL DEFAULT 'PCS',
    receiving_store VARCHAR(150) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_batch_division FOREIGN KEY (division_id) REFERENCES divisions(id) ON DELETE RESTRICT,
    CONSTRAINT fk_batch_material FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE RESTRICT,
    UNIQUE KEY uq_batch_per_material (division_id, material_id, batch_no),
    INDEX idx_batch_expiry (division_id, expiry_date),
    INDEX idx_batch_number (batch_no)
) ENGINE=InnoDB;

CREATE TABLE stock_movements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    batch_id BIGINT UNSIGNED NOT NULL,
    movement_type ENUM('receipt','issue','adjustment') NOT NULL,
    qty DECIMAL(18,3) NOT NULL,
    movement_date DATETIME NOT NULL,
    reference_no VARCHAR(100) NULL,
    notes VARCHAR(255) NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_move_batch FOREIGN KEY (batch_id) REFERENCES stock_batches(id) ON DELETE CASCADE,
    CONSTRAINT fk_move_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_move_batch_date (batch_id, movement_date)
) ENGINE=InnoDB;

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


INSERT INTO divisions (code,name) VALUES
('JSD','Jam-e-Shirin'),
('SNW','Springly Natural Water'),
('RTD','Ready To Drink');

-- Legacy display table retained for compatibility. Current grading is calculated from
-- remaining days to Expiry Date + division_grade_settings.
INSERT INTO grade_rules (division_id,grade_code,grade_name,min_days,max_days,color_class,sort_order) VALUES
(NULL,'A+','A+ Grade',NULL,NULL,'blue',1),
(NULL,'A','A Grade',NULL,NULL,'green',2),
(NULL,'B','B Grade',NULL,NULL,'yellow',3),
(NULL,'C','C Grade',NULL,NULL,'red',4),
(NULL,'EX','Expired',NULL,NULL,'black',5);

-- Default expiry remaining-day criteria for every division.
-- A+, A, B and C values are stored independently and can be changed by Super Admin per division.
-- Expired always remains tied to the actual Expiry Date.
INSERT INTO division_grade_settings (division_id,a_plus_months,a_months,b_months,a_plus_days,a_days,b_days,c_days,a_plus_used_pct,a_used_pct,b_used_pct,c_used_pct)
SELECT id,1,2,3,366,365,180,90,25.00,50.00,75.00,100.00 FROM divisions;

-- Initial Super Admin. Change password after first login.
-- email: admin@example.com  password: Admin@123
INSERT INTO users (division_id,name,email,password_hash,role,is_active) VALUES
(NULL,'Super Admin','admin@example.com','$2y$12$bXlwjTwd/8IIEhpLOIV04.f5s7lnHqVj5pD5RozJU9NcpViDREimC','super_admin',1);

-- Representative JSD sample based on the supplied stock-aging layout.
INSERT INTO materials (division_id,material_code,description)
SELECT id,'100394','JAM-E-SHIRIN 800ML' FROM divisions WHERE code='JSD';
INSERT INTO materials (division_id,material_code,description)
SELECT id,'100396','JAM-E-SHIRIN 3000ML' FROM divisions WHERE code='JSD';
INSERT INTO materials (division_id,material_code,description)
SELECT id,'100397','JAM-E-SHIRIN 500ML' FROM divisions WHERE code='JSD';

INSERT INTO stock_batches (division_id,material_id,batch_no,manufacture_date,expiry_date,last_receipt_date,current_qty,uom)
SELECT d.id,m.id,'JS020926A','2026-09-02','2030-09-01','2026-09-11',2150.000,'PCS' FROM divisions d JOIN materials m ON m.division_id=d.id AND m.material_code='100394' WHERE d.code='JSD';
INSERT INTO stock_batches (division_id,material_id,batch_no,manufacture_date,expiry_date,last_receipt_date,current_qty,uom)
SELECT d.id,m.id,'JS020926B','2026-09-02','2030-09-01','2026-09-12',2732.000,'PCS' FROM divisions d JOIN materials m ON m.division_id=d.id AND m.material_code='100394' WHERE d.code='JSD';
INSERT INTO stock_batches (division_id,material_id,batch_no,manufacture_date,expiry_date,last_receipt_date,current_qty,uom)
SELECT d.id,m.id,'JS210826B','2026-08-21','2030-08-20','2026-08-29',1849.000,'PCS' FROM divisions d JOIN materials m ON m.division_id=d.id AND m.material_code='100396' WHERE d.code='JSD';
INSERT INTO stock_batches (division_id,material_id,batch_no,manufacture_date,expiry_date,last_receipt_date,current_qty,uom)
SELECT d.id,m.id,'JS220726A','2026-07-22','2030-07-21','2026-08-13',1958.000,'PCS' FROM divisions d JOIN materials m ON m.division_id=d.id AND m.material_code='100396' WHERE d.code='JSD';
INSERT INTO stock_batches (division_id,material_id,batch_no,manufacture_date,expiry_date,last_receipt_date,current_qty,uom)
SELECT d.id,m.id,'JS200726B','2026-07-20','2030-07-19','2026-08-12',2380.000,'PCS' FROM divisions d JOIN materials m ON m.division_id=d.id AND m.material_code='100397' WHERE d.code='JSD';
INSERT INTO stock_batches (division_id,material_id,batch_no,manufacture_date,expiry_date,last_receipt_date,current_qty,uom)
SELECT d.id,m.id,'JS220726B','2026-07-22','2030-07-21','2026-08-19',962.000,'PCS' FROM divisions d JOIN materials m ON m.division_id=d.id AND m.material_code='100397' WHERE d.code='JSD';
