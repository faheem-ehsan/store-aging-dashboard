-- Daily Stock Record Report system
-- One permanent closing snapshot per Division per Report Date.

CREATE TABLE IF NOT EXISTS daily_stock_reports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_date DATE NOT NULL,
    division_id INT UNSIGNED NOT NULL,
    total_batches INT UNSIGNED NOT NULL DEFAULT 0,
    total_quantity DECIMAL(20,3) NOT NULL DEFAULT 0,
    total_stock_value DECIMAL(20,2) NOT NULL DEFAULT 0,
    grade_summary_json JSON NULL,
    generation_type ENUM('manual','automatic') NOT NULL DEFAULT 'manual',
    generated_by INT UNSIGNED NULL,
    generated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    verified_by INT UNSIGNED NULL,
    verified_at DATETIME NULL,
    verification_note VARCHAR(500) NULL,
    CONSTRAINT fk_daily_stock_report_division
        FOREIGN KEY (division_id) REFERENCES divisions(id) ON DELETE RESTRICT,
    CONSTRAINT fk_daily_stock_report_generated_by
        FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_daily_stock_report_verified_by
        FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY uq_daily_stock_report_division_date (division_id, report_date),
    INDEX idx_daily_stock_report_date (report_date),
    INDEX idx_daily_stock_report_verified (verified_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS daily_stock_report_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_id BIGINT UNSIGNED NOT NULL,
    source_batch_id BIGINT UNSIGNED NULL,
    product_code VARCHAR(80) NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    batch_no VARCHAR(80) NOT NULL,
    current_qty DECIMAL(20,3) NOT NULL DEFAULT 0,
    uom VARCHAR(40) NOT NULL DEFAULT '',
    stock_value DECIMAL(20,2) NOT NULL DEFAULT 0,
    manufacture_date DATE NOT NULL,
    expiry_date DATE NOT NULL,
    grade_code VARCHAR(10) NOT NULL,
    grade_name VARCHAR(50) NOT NULL,
    stock_location VARCHAR(150) NULL,
    source_updated_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_daily_stock_item_report
        FOREIGN KEY (report_id) REFERENCES daily_stock_reports(id) ON DELETE CASCADE,
    INDEX idx_daily_stock_item_report (report_id),
    INDEX idx_daily_stock_item_product (report_id, product_code),
    INDEX idx_daily_stock_item_batch (batch_no),
    INDEX idx_daily_stock_item_grade (report_id, grade_code)
) ENGINE=InnoDB;
